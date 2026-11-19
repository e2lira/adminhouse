<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Authorization\AccessControl;
use App\Authorization\DevelopmentScope;
use App\Controller\Component\DevelopmentScopeComponent;
use App\Test\Support\DatabaseTestCase;
use Authentication\Identity;
use Cake\Controller\Controller;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The scope a controller works under (AD5): resolved once per identity and permission, and a development outside it
 * answers the same 404 as one that does not exist.
 *
 * The tests that prove the scope's own filtering open a super-administrator session and inject the scope through a
 * stub AccessControl, so row-level security hides nothing and every miss is the scope's doing.
 */
class DevelopmentScopeComponentTest extends DatabaseTestCase
{
    /**
     * @param int|null $userId Signed-in user; null for no identity.
     * @param \App\Authorization\AccessControl|null $access Scope resolver; null uses the real one.
     * @return \App\Controller\Component\DevelopmentScopeComponent
     */
    private function component(?int $userId, ?AccessControl $access = null): DevelopmentScopeComponent
    {
        $request = new ServerRequest();
        if ($userId !== null) {
            $request = $request->withAttribute('identity', new Identity(['id' => $userId]));
        }
        $controller = new Controller($request);
        /** @var \App\Controller\Component\DevelopmentScopeComponent $component */
        $component = $controller->loadComponent('DevelopmentScope', ['access' => $access]);

        return $component;
    }

    /**
     * @return \App\Authorization\AccessControl Resolver that counts the questions it is asked.
     */
    private function countingAccess(): AccessControl
    {
        return new class extends AccessControl {
            /**
             * @var list<string>
             */
            public array $asked = [];

            /**
             * @inheritDoc
             */
            public function allowedFor(int $userId, string $permission): DevelopmentScope
            {
                $this->asked[] = $userId . ':' . $permission;

                return parent::allowedFor($userId, $permission);
            }
        };
    }

    /**
     * @param \App\Authorization\DevelopmentScope $scope Scope to answer with.
     * @return \App\Authorization\AccessControl Resolver that always answers `$scope` and counts the questions.
     */
    private function accessGranting(DevelopmentScope $scope): AccessControl
    {
        return new class ($scope) extends AccessControl {
            /**
             * @var list<string>
             */
            public array $asked = [];

            public function __construct(private DevelopmentScope $scope)
            {
                parent::__construct();
            }

            /**
             * @inheritDoc
             */
            public function allowedFor(int $userId, string $permission): DevelopmentScope
            {
                $this->asked[] = $userId . ':' . $permission;

                return $this->scope;
            }
        };
    }

    /**
     * @return int Id of a super-administrator whose session is open.
     */
    private function actAsSuperAdministrator(): int
    {
        $root = $this->createUser('root');
        $this->assignRole($root, 'super_administrator', null);
        $this->actAs($root);

        return $root;
    }

    public function testForReturnsTheScopeThatAccessControlGrants(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $south = $this->createDevelopment('Residencial Sur');
        $ann = $this->createUser('ann');
        $this->assignRole($ann, 'administrator', $north);
        $this->actAs($ann);

        $scope = $this->component($ann)->for('developments.view');

        $this->assertFalse($scope->isAll());
        $this->assertSame([$north], $scope->ids());
        $this->assertFalse($scope->allows($south));
    }

    public function testForGivesASuperAdministratorEveryDevelopment(): void
    {
        $root = $this->actAsSuperAdministrator();

        $this->assertTrue($this->component($root)->for('developments.view')->isAll());
    }

    public function testForIsEmptyWithoutAnIdentity(): void
    {
        $this->assertTrue($this->component(null)->for('developments.view')->isEmpty());
    }

    public function testForAsksAccessControlOncePerPermission(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $ann = $this->createUser('ann');
        $this->assignRole($ann, 'administrator', $north);
        $this->actAs($ann);
        $access = $this->countingAccess();
        $component = $this->component($ann, $access);

        $first = $component->for('developments.view');
        $second = $component->for('developments.view');
        $other = $component->for('role_assignments.manage');

        $this->assertSame($first, $second);
        $this->assertNotSame($first, $other);
        $this->assertSame([$ann . ':developments.view', $ann . ':role_assignments.manage'], $access->asked);
    }

    public function testTheMemoNeverHoldsTheNoIdentityCase(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $ann = $this->createUser('ann');
        $this->assignRole($ann, 'administrator', $north);
        $this->actAs($ann);
        $access = $this->countingAccess();
        $controller = new Controller(new ServerRequest());
        /** @var \App\Controller\Component\DevelopmentScopeComponent $component */
        $component = $controller->loadComponent('DevelopmentScope', ['access' => $access]);

        $before = $component->for('developments.view');
        $controller->setRequest($controller->getRequest()->withAttribute('identity', new Identity(['id' => $ann])));
        $after = $component->for('developments.view');

        $this->assertTrue($before->isEmpty());
        $this->assertSame([$north], $after->ids());
        $this->assertSame([$ann . ':developments.view'], $access->asked);
    }

    public function testAnotherIdentityOnTheSameComponentAsksAgain(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $south = $this->createDevelopment('Residencial Sur');
        $ann = $this->createUser('ann');
        $this->assignRole($ann, 'administrator', $north);
        $bob = $this->createUser('bob');
        $this->assignRole($bob, 'administrator', $south);
        $this->actAsSuperAdministrator();
        $access = $this->countingAccess();
        $controller = new Controller((new ServerRequest())->withAttribute('identity', new Identity(['id' => $ann])));
        /** @var \App\Controller\Component\DevelopmentScopeComponent $component */
        $component = $controller->loadComponent('DevelopmentScope', ['access' => $access]);

        $first = $component->for('developments.view');
        $controller->setRequest($controller->getRequest()->withAttribute('identity', new Identity(['id' => $bob])));
        $second = $component->for('developments.view');

        $this->assertSame([$north], $first->ids());
        $this->assertSame([$south], $second->ids());
        $this->assertSame([$ann . ':developments.view', $bob . ':developments.view'], $access->asked);
    }

    public function testDevelopmentThenForAsksAccessControlOnce(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $root = $this->actAsSuperAdministrator();
        $access = $this->accessGranting(DevelopmentScope::only([$north]));
        $component = $this->component($root, $access);

        $component->development($north, 'developments.view');
        $component->for('developments.view');

        $this->assertSame([$root . ':developments.view'], $access->asked);
    }

    public function testAReadOnlyAssignmentKeepsViewButNotManage(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $ann = $this->createUser('ann');
        $this->assignRole($ann, 'administrator', $north, -30, null, true);
        $this->actAs($ann);
        $component = $this->component($ann);

        $this->assertSame([$north], $component->for('developments.view')->ids());
        $this->assertTrue($component->for('role_assignments.manage')->isEmpty());
    }

    public function testAnExpiredAssignmentGivesAnEmptyScope(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $ann = $this->createUser('ann');
        $this->assignRole($ann, 'administrator', $north, -60, -1);
        $this->actAs($ann);

        $this->assertTrue($this->component($ann)->for('developments.view')->isEmpty());
    }

    public function testDevelopmentReturnsTheIdOfOneInsideTheScope(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $this->createDevelopment('Residencial Sur');
        $root = $this->actAsSuperAdministrator();
        $component = $this->component($root, $this->accessGranting(DevelopmentScope::only([$north])));

        $this->assertSame($north, $component->development((string)$north, 'developments.view'));
    }

    public function testDevelopmentOutsideTheScopeIsNotFound(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $south = $this->createDevelopment('Residencial Sur');
        $root = $this->actAsSuperAdministrator();
        $component = $this->component($root, $this->accessGranting(DevelopmentScope::only([$north])));

        $this->expectException(NotFoundException::class);

        $component->development($south, 'developments.view');
    }

    public function testAnEmptyScopeFindsNothing(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $root = $this->actAsSuperAdministrator();

        $this->expectException(NotFoundException::class);

        $this->component($root, $this->accessGranting(DevelopmentScope::none()))
            ->development($north, 'developments.view');
    }

    public function testNonexistentAndOutOfScopeDevelopmentsAnswerTheSame(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $south = $this->createDevelopment('Residencial Sur');
        $root = $this->actAsSuperAdministrator();
        $component = $this->component($root, $this->accessGranting(DevelopmentScope::only([$north])));

        $this->assertSame($north, $component->development($north, 'developments.view'));
        $answers = [];
        foreach ([$south, 999999] as $id) {
            try {
                $component->development($id, 'developments.view');
                $this->fail('A development outside the scope must not be found.');
            } catch (NotFoundException $e) {
                $answers[] = [$e::class, $e->getCode(), $e->getMessage()];
            }
        }

        $this->assertSame($answers[0], $answers[1]);
    }

    public function testASuperAdministratorStillGetsNotFoundForAMissingDevelopment(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $root = $this->actAsSuperAdministrator();
        $component = $this->component($root);

        $this->assertSame($north, $component->development($north, 'developments.view'));

        $this->expectException(NotFoundException::class);

        $component->development(999999, 'developments.view');
    }

    public function testDevelopmentWithoutAnIdentityIsNotFound(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $this->actAsSuperAdministrator();

        $this->expectException(NotFoundException::class);

        $this->component(null)->development($north, 'developments.view');
    }

    /**
     * @return array<string, array{string|int}>
     */
    public static function malformedIds(): array
    {
        return [
            'letters' => ['abc'],
            'negative' => ['-1'],
            'zero' => ['0'],
            'zero int' => [0],
            'negative int' => [-5],
            'beyond bigint' => ['99999999999999999999'],
            'empty' => [''],
            'decimal' => ['1.5'],
            'leading zero' => ['01'],
        ];
    }

    #[DataProvider('malformedIds')]
    public function testAMalformedIdIsTheSameNotFound(string|int $id): void
    {
        $this->createDevelopment('Residencial Norte');
        $root = $this->actAsSuperAdministrator();
        $component = $this->component($root);

        try {
            $component->development($id, 'developments.view');
            $this->fail('A malformed id must not be found.');
        } catch (NotFoundException $e) {
            $this->assertSame(404, $e->getCode());
            $this->assertSame((new NotFoundException())->getMessage(), $e->getMessage());
        }
    }
}
