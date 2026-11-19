<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Authorization\AccessControl;
use App\Authorization\DevelopmentScope;
use App\Authorization\DevelopmentScopeStack;
use App\Model\Entity\Development;
use App\Model\Exception\DevelopmentScopeViolation;
use App\Model\Exception\MissingDevelopmentScopeException;
use App\Model\Table\DevelopmentsTable;
use App\Policy\DevelopmentsTablePolicy;
use App\Test\Support\DatabaseTestCase;
use Authentication\Identity;
use Authorization\AuthorizationServiceInterface;
use Authorization\IdentityDecorator;
use Authorization\IdentityInterface;
use Closure;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Developments are scoped by their own id (AD3): a read without a scope fails, and the scope narrows what is listed.
 *
 * The session of the reading user is a super-administrator's in the tests that prove the application layer, so that
 * row-level security hides nothing and every filtered row is the scope's doing.
 */
class DevelopmentsTableTest extends DatabaseTestCase
{
    private DevelopmentsTable $developments;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getTableLocator()->clear();
        $this->developments = $this->getTableLocator()->get('Developments');
    }

    protected function tearDown(): void
    {
        $this->getTableLocator()->clear();
        parent::tearDown();
    }

    /**
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query.
     * @return list<string> Sorted names.
     */
    private function names($query): array
    {
        $names = $query->all()->extract('name')->toList();
        sort($names);

        return $names;
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

    public function testAFindWithoutAScopeThrows(): void
    {
        $this->createDevelopment('Residencial Norte');
        $this->actAsSuperAdministrator();

        $this->expectException(MissingDevelopmentScopeException::class);

        $this->developments->find()->all();
    }

    public function testAUserOfOneDevelopmentListsOnlyThatOne(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $this->createDevelopment('Residencial Sur');
        $this->actAsSuperAdministrator();

        $names = $this->names($this->developments->find('all', developmentScope: DevelopmentScope::only([$north])));

        $this->assertSame(['Residencial Norte'], $names);
    }

    public function testASuperAdministratorSeesEveryDevelopmentIncludingOneCreatedLater(): void
    {
        $this->createDevelopment('Residencial Norte');
        $root = $this->createUser('root');
        $this->assignRole($root, 'super_administrator', null);
        $this->createDevelopment('Residencial Sur');
        $this->actAs($root);

        $scope = (new AccessControl())->allowedFor($root, 'developments.view');
        $names = $this->names($this->developments->find('all', developmentScope: $scope));

        $this->assertTrue($scope->isAll());
        $this->assertSame(['Residencial Norte', 'Residencial Sur'], $names);
    }

    public function testAnEmptyScopeListsNothingEvenWhenDevelopmentsExist(): void
    {
        $this->createDevelopment('Residencial Norte');
        $this->actAsSuperAdministrator();

        $found = $this->developments->find('all', developmentScope: DevelopmentScope::none())->count();

        $this->assertSame(0, $found);
        $this->assertSame(1, $this->developments->find('all', developmentScope: DevelopmentScope::all())->count());
    }

    /**
     * @return array<string, array{\Closure(\App\Model\Table\DevelopmentsTable): mixed}>
     */
    public static function bulkStatements(): array
    {
        return [
            'updateAll' => [fn(DevelopmentsTable $table) => $table->updateAll(['name' => 'x'], ['id >' => 0])],
            'deleteAll' => [fn(DevelopmentsTable $table) => $table->deleteAll(['id >' => 0])],
        ];
    }

    #[DataProvider('bulkStatements')]
    public function testTheTableRefusesBulkStatements(Closure $statement): void
    {
        $this->createDevelopment('Residencial Norte');
        $this->actAsSuperAdministrator();

        try {
            $statement($this->developments);
            $this->fail('A bulk statement on developments must be refused.');
        } catch (LogicException) {
            $this->assertSame(1, $this->developments->find('all', developmentScope: DevelopmentScope::all())->count());
        }
    }

    /**
     * @return \App\Model\Entity\Development
     */
    private function newDevelopment(): Development
    {
        /** @var \App\Model\Entity\Development $development */
        $development = $this->developments->newEntity([
            'type' => 'condominium',
            'name' => 'Residencial Nuevo',
            'section_label' => 'Torre',
            'number_label' => 'Departamento',
        ]);

        return $development;
    }

    public function testASaveOfANewDevelopmentUnderASingleDevelopmentScopeIsRefused(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $this->actAsSuperAdministrator();

        try {
            DevelopmentScopeStack::run(
                DevelopmentScope::only([$north]),
                fn() => $this->developments->save($this->newDevelopment()),
            );
            $this->fail('Only an all-development scope may create a development.');
        } catch (DevelopmentScopeViolation) {
            $this->assertSame(1, $this->developments->find('all', developmentScope: DevelopmentScope::all())->count());
        }
    }

    public function testAnAllDevelopmentScopeMayCreateOne(): void
    {
        $this->actAsSuperAdministrator();

        $saved = DevelopmentScopeStack::run(
            DevelopmentScope::all(),
            fn() => $this->developments->save($this->newDevelopment()),
        );

        $this->assertInstanceOf(Development::class, $saved);
        $this->assertSame(
            ['Residencial Nuevo'],
            $this->names($this->developments->find('all', developmentScope: DevelopmentScope::all())),
        );
    }

    public function testTheIndexPolicyNarrowsAQueryToTheScopeOfTheUser(): void
    {
        $north = $this->createDevelopment('Residencial Norte');
        $this->createDevelopment('Residencial Sur');
        $this->actAsSuperAdministrator();
        $policy = new DevelopmentsTablePolicy($this->accessGranting(DevelopmentScope::only([$north])));

        $query = $policy->scopeIndex($this->identity(), $this->developments->find());

        $this->assertSame(['Residencial Norte'], $this->names($query));
    }

    public function testTheIndexPolicyKeepsEveryDevelopmentForAnAllScope(): void
    {
        $this->createDevelopment('Residencial Norte');
        $this->createDevelopment('Residencial Sur');
        $this->actAsSuperAdministrator();
        $policy = new DevelopmentsTablePolicy($this->accessGranting(DevelopmentScope::all()));

        $query = $policy->scopeIndex($this->identity(), $this->developments->find());

        $this->assertSame(['Residencial Norte', 'Residencial Sur'], $this->names($query));
    }

    public function testTheIndexPolicyListsNothingForAnEmptyScopeAndRefusesIt(): void
    {
        $this->createDevelopment('Residencial Norte');
        $this->actAsSuperAdministrator();
        $policy = new DevelopmentsTablePolicy($this->accessGranting(DevelopmentScope::none()));
        $identity = $this->identity();

        $query = $policy->scopeIndex($identity, $this->developments->find());

        $this->assertSame(0, $query->count());
        $this->assertFalse($policy->canIndex($identity, $this->developments));
    }

    /**
     * @return \Authorization\IdentityInterface An identity with a fixed id: the stubbed AccessControl ignores it.
     */
    private function identity(): IdentityInterface
    {
        return new IdentityDecorator($this->createStub(AuthorizationServiceInterface::class), new Identity(['id' => 1]));
    }

    /**
     * @param \App\Authorization\DevelopmentScope $scope Scope to answer with.
     * @return \App\Authorization\AccessControl
     */
    private function accessGranting(DevelopmentScope $scope): AccessControl
    {
        return new class ($scope) extends AccessControl {
            public function __construct(private DevelopmentScope $scope)
            {
                parent::__construct();
            }

            /**
             * @inheritDoc
             */
            public function allowedFor(int $userId, string $permission): DevelopmentScope
            {
                return $this->scope;
            }
        };
    }
}
