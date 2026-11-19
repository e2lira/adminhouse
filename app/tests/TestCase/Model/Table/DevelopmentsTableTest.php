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
            'timezone' => 'America/Mexico_City',
        ]);

        return $development;
    }

    /**
     * @return array<string, mixed> A valid payload with every required field and none of the optional ones.
     */
    private static function validPayload(): array
    {
        return [
            'type' => 'subdivision',
            'name' => 'Fraccionamiento Sur',
            'section_label' => 'Privada',
            'number_label' => 'Casa',
            'timezone' => 'America/Mexico_City',
        ];
    }

    public function testTheFiveProfileFieldsMayBeLeftOutOrEmpty(): void
    {
        $omitted = $this->developments->newEntity(self::validPayload());
        $empty = $this->developments->newEntity(self::validPayload() + [
            'address' => '', 'phone' => '', 'rfc' => '', 'bank_name' => '', 'bank_account' => '',
        ]);

        $this->assertSame([], $omitted->getErrors());
        $this->assertSame([], $empty->getErrors());
    }

    public function testTheProfileFieldsAreKeptWhenGiven(): void
    {
        $development = $this->developments->newEntity(self::validPayload() + [
            'address' => 'Calle 1, Col. Centro',
            'phone' => '555 0101',
            'rfc' => 'ABC010101AB1',
            'bank_name' => 'Banco Demo',
            'bank_account' => '012345678901234567',
        ]);

        $this->assertSame([], $development->getErrors());
        $this->assertSame('Calle 1, Col. Centro', $development->get('address'));
        $this->assertSame('012345678901234567', $development->get('bank_account'));
    }

    /**
     * @return array<string, array{string, mixed, bool}>
     */
    public static function badRequiredValues(): array
    {
        $cases = [];
        foreach (['type', 'name', 'section_label', 'number_label', 'timezone'] as $field) {
            $cases["{$field} missing"] = [$field, null, true];
            $cases["{$field} null"] = [$field, null, false];
            $cases["{$field} empty"] = [$field, '', false];
            $cases["{$field} blank"] = [$field, '   ', false];
            $cases["{$field} array"] = [$field, [], false];
        }

        return $cases;
    }

    #[DataProvider('badRequiredValues')]
    public function testEachRequiredFieldRejectsMissingNullEmptyBlankAndArrayValues(
        string $field,
        mixed $value,
        bool $omit,
    ): void {
        $payload = self::validPayload();
        if ($omit) {
            unset($payload[$field]);
        } else {
            $payload[$field] = $value;
        }

        $errors = $this->developments->newEntity($payload)->getErrors();

        $this->assertSame([$field], array_keys($errors));
    }

    public function testTheTypeMustBeCondominiumOrSubdivision(): void
    {
        $this->assertSame([], $this->developments->newEntity(['type' => 'condominium'] + self::validPayload())->getErrors());
        $this->assertSame([], $this->developments->newEntity(['type' => 'subdivision'] + self::validPayload())->getErrors());
        foreach (['tower', '', 'Condominium', 'SUBDIVISION'] as $bad) {
            $this->assertSame(
                ['type'],
                array_keys($this->developments->newEntity(['type' => $bad] + self::validPayload())->getErrors()),
                $bad,
            );
        }
    }

    public function testTheTimezoneMustBeAKnownZone(): void
    {
        $this->assertSame([], $this->developments->newEntity(['timezone' => 'America/Cancun'] + self::validPayload())->getErrors());
        $this->assertArrayHasKey(
            'timezone',
            $this->developments->newEntity(['timezone' => 'Mars/Olympus'] + self::validPayload())->getErrors(),
        );
    }

    public function testPatchingAnExistingDevelopmentCannotChangeIdArchiveStateOrTimestamps(): void
    {
        $id = $this->createDevelopment('Residencial Norte');
        $this->actAsSuperAdministrator();
        /** @var \App\Model\Entity\Development $existing */
        $existing = $this->developments->get($id, developmentScope: DevelopmentScope::all());
        $created = $existing->created;
        $modified = $existing->modified;

        $patched = $this->developments->patchEntity($existing, [
            'name' => 'Renamed',
            'id' => 999,
            'archived_at' => '2026-01-01 00:00:00',
            'created' => '2000-01-01 00:00:00',
            'modified' => '2000-01-01 00:00:00',
            'unknown' => 'x',
        ]);

        $this->assertSame('Renamed', $patched->name);
        $this->assertSame($id, $patched->id);
        $this->assertNull($patched->archived_at);
        $this->assertEquals($created, $patched->created);
        $this->assertEquals($modified, $patched->modified);
        $this->assertNull($patched->get('unknown'));
    }

    public function testEveryEditableFieldIsAssignable(): void
    {
        $payload = self::validPayload() + [
            'address' => 'a', 'phone' => 'b', 'rfc' => 'c', 'bank_name' => 'd', 'bank_account' => 'e',
        ];

        $development = $this->developments->newEntity($payload);

        foreach ($payload as $field => $value) {
            $this->assertSame($value, $development->get($field), $field);
        }
    }

    public function testOptionalProfileFieldsThatAreEmptyOrBlankBecomeNull(): void
    {
        foreach (['', '   ', "\t\n"] as $empty) {
            $development = $this->developments->newEntity(self::validPayload() + [
                'address' => $empty, 'phone' => $empty, 'rfc' => $empty, 'bank_name' => $empty, 'bank_account' => $empty,
            ]);
            foreach (['address', 'phone', 'rfc', 'bank_name', 'bank_account'] as $field) {
                $this->assertNull($development->get($field), $field);
            }
        }
    }

    public function testTextFieldsAreTrimmed(): void
    {
        $fields = ['name', 'section_label', 'number_label', 'address', 'phone', 'rfc', 'bank_name', 'bank_account'];
        $development = $this->developments->newEntity([
            'name' => '  Residencial Sur ', 'section_label' => ' Torre ', 'number_label' => ' Depto  ',
            'address' => ' Calle 1 ', 'phone' => ' 555 ', 'rfc' => ' ABC ', 'bank_name' => ' B ', 'bank_account' => ' 12 ',
            'type' => 'condominium', 'timezone' => 'America/Mexico_City',
        ]);

        $this->assertSame([], $development->getErrors());
        $this->assertSame(
            ['Residencial Sur', 'Torre', 'Depto', 'Calle 1', '555', 'ABC', 'B', '12'],
            array_map(fn(string $f) => $development->get($f), $fields),
        );
    }

    public function testNormalizedValuesSurviveASaveAndReload(): void
    {
        $this->actAsSuperAdministrator();
        $payload = [
            'name' => '  Persistida ', 'type' => 'subdivision', 'section_label' => ' Privada ',
            'number_label' => 'Casa', 'timezone' => 'America/Mexico_City',
            'address' => '   ', 'phone' => '', 'rfc' => ' ABC010101AB1 ', 'bank_name' => "\t", 'bank_account' => null,
        ];

        $saved = DevelopmentScopeStack::run(
            DevelopmentScope::all(),
            fn() => $this->developments->save($this->developments->newEntity($payload)),
        );
        $this->assertInstanceOf(Development::class, $saved);
        $this->commitSession();
        $row = $this->db()->execute(
            'SELECT name, section_label, address, phone, rfc, bank_name, bank_account, archived_at
             FROM developments WHERE id = ?',
            [$saved->id],
        )->fetch('assoc');

        $this->assertSame([
            'name' => 'Persistida', 'section_label' => 'Privada', 'address' => null, 'phone' => null,
            'rfc' => 'ABC010101AB1', 'bank_name' => null, 'bank_account' => null, 'archived_at' => null,
        ], $row);
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function lengthLimits(): array
    {
        return [
            'name' => ['name', 150], 'section_label' => ['section_label', 150], 'number_label' => ['number_label', 150],
            'address' => ['address', 255], 'bank_name' => ['bank_name', 255],
            'phone' => ['phone', 32], 'rfc' => ['rfc', 32], 'bank_account' => ['bank_account', 32],
        ];
    }

    #[DataProvider('lengthLimits')]
    public function testTextFieldsHaveALengthLimit(string $field, int $limit): void
    {
        $atLimit = $this->developments->newEntity([$field => str_repeat('a', $limit)] + self::validPayload());
        $over = $this->developments->newEntity([$field => str_repeat('a', $limit + 1)] + self::validPayload());

        $this->assertSame([], $atLimit->getErrors());
        $this->assertSame([$field], array_keys($over->getErrors()));
    }

    public function testAnArrayInAnOptionalFieldIsRejected(): void
    {
        $errors = $this->developments->newEntity(['phone' => ['1']] + self::validPayload())->getErrors();

        $this->assertSame(['phone'], array_keys($errors));
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
