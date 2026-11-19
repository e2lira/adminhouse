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

/**
 * The scope a controller works under (AD5): resolved once per permission, and a development outside it answers the
 * same 404 as one that does not exist.
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
        $root = $this->createUser('root');
        $this->assignRole($root, 'super_administrator', null);
        $this->actAs($root);

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

    public function testDevelopmentReturnsTheIdOfOneInsideTheScope(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $this->createDevelopment('Residencial Sur');
        $ann = $this->createUser('ann');
        $this->assignRole($ann, 'administrator', $north);
        $this->actAs($ann);

        $this->assertSame($north, $this->component($ann)->development((string)$north, 'developments.view'));
    }

    public function testDevelopmentOutsideTheScopeIsNotFound(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $south = $this->createDevelopment('Residencial Sur');
        $ann = $this->createUser('ann');
        $this->assignRole($ann, 'administrator', $north);
        $this->actAs($ann);

        $this->expectException(NotFoundException::class);

        $this->component($ann)->development($south, 'developments.view');
    }

    public function testNonexistentAndOutOfScopeDevelopmentsAnswerTheSame(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $south = $this->createDevelopment('Residencial Sur');
        $ann = $this->createUser('ann');
        $this->assignRole($ann, 'administrator', $north);
        $this->actAs($ann);
        $component = $this->component($ann);

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
        $root = $this->createUser('root');
        $this->assignRole($root, 'super_administrator', null);
        $this->actAs($root);
        $component = $this->component($root);

        $this->assertSame($north, $component->development($north, 'developments.view'));

        $this->expectException(NotFoundException::class);

        $component->development(999999, 'developments.view');
    }
}
