<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Authorization\AccessControl;
use App\Authorization\DevelopmentScope;
use App\Model\Entity\Person;
use App\Model\Exception\DevelopmentScopeViolation;
use App\Model\Exception\MissingDevelopmentScopeException;
use App\Model\Table\PeopleTable;
use App\Test\Support\DatabaseTestCase;
use Closure;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * People under layer 1: scoped through the development links, no bulk statements, no ORM insert, creation only through
 * `person_create`, and every write preceded by a read under a scope (task 11.8, the S2 carry-over of PR 1a-i).
 *
 * Most tests use a super-administrator session, so row-level security hides nothing and every filtered or refused row
 * is the application layer's doing. The creation tests also run as real roles, where the database answers too.
 */
class PeopleTableTest extends DatabaseTestCase
{
    private PeopleTable $people;

    private int $devA;

    private int $devB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getTableLocator()->clear();
        $this->people = $this->getTableLocator()->get('People');
        $this->devA = $this->createDevelopment('Norte');
        $this->devB = $this->createDevelopment('Sur');
    }

    protected function tearDown(): void
    {
        $this->getTableLocator()->clear();
        parent::tearDown();
    }

    private function actAsSuperAdministrator(): void
    {
        $root = $this->createUser('root');
        $this->assignRole($root, 'super_administrator', null);
        $this->actAs($root);
    }

    /**
     * @return \App\Authorization\DevelopmentScope The `people.manage` scope of the user the session runs as.
     */
    private function actAsRole(string $role, bool $readOnly = false): DevelopmentScope
    {
        $user = $this->createUser('ann');
        $this->assignRole($user, $role, $this->devA, readOnly: $readOnly);
        $this->actAs($user);

        return (new AccessControl())->allowedFor($user, 'people.manage');
    }

    private function readIn(int $person, DevelopmentScope $scope): Person
    {
        /** @var \App\Model\Entity\Person */
        return $this->people->find('all', developmentScope: $scope)->where(['People.id' => $person])->firstOrFail();
    }

    private function peopleCount(): int
    {
        return (int)$this->db()->execute('SELECT COUNT(*) FROM people')->fetch()[0];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function personRow(int $id): ?array
    {
        $row = $this->db()->execute(
            'SELECT name, email, phone, rfc FROM people WHERE id = ?',
            [$id],
        )->fetch('assoc');

        return $row === false ? null : $row;
    }

    // --- scoping through the links --------------------------------------------------------------------------------

    public function testAFindWithoutAScopeThrows(): void
    {
        $this->createPerson('Ana', [$this->devA]);
        $this->actAsSuperAdministrator();

        $this->expectException(MissingDevelopmentScopeException::class);

        $this->people->find()->all();
    }

    public function testAScopeNarrowsTheListToThePeopleLinkedToItsDevelopments(): void
    {
        $this->createPerson('Only A', [$this->devA]);
        $this->createPerson('Only B', [$this->devB]);
        $this->createPerson('Both', [$this->devA, $this->devB]);
        $this->createPerson('Nowhere', []);
        $this->actAsSuperAdministrator();

        $inA = $this->people->find('all', developmentScope: DevelopmentScope::only([$this->devA]))
            ->orderBy(['People.name' => 'ASC'])->all()->extract('name')->toList();
        $inB = $this->people->find('all', developmentScope: DevelopmentScope::only([$this->devB]))
            ->orderBy(['People.name' => 'ASC'])->all()->extract('name')->toList();

        $this->assertSame(['Both', 'Only A'], $inA);
        $this->assertSame(['Both', 'Only B'], $inB);
    }

    public function testAnEmptyScopeFindsNobodyAndTheFullScopeFindsEveryone(): void
    {
        $this->createPerson('Only A', [$this->devA]);
        $this->createPerson('Nowhere', []);
        $this->actAsSuperAdministrator();

        $none = $this->people->find('all', developmentScope: DevelopmentScope::none())->count();
        $all = $this->people->find('all', developmentScope: DevelopmentScope::all())->count();

        $this->assertSame(0, $none);
        $this->assertSame(2, $all);
    }

    /**
     * @return array<string, array{\Closure(\App\Model\Table\PeopleTable): mixed}>
     */
    public static function bulkStatements(): array
    {
        return [
            'updateAll' => [fn(PeopleTable $table) => $table->updateAll(['name' => 'x'], ['id >' => 0])],
            'deleteAll' => [fn(PeopleTable $table) => $table->deleteAll(['id >' => 0])],
        ];
    }

    #[DataProvider('bulkStatements')]
    public function testTheTableRefusesBulkStatements(Closure $statement): void
    {
        $this->createPerson('Ana', [$this->devA]);
        $this->actAsSuperAdministrator();

        $this->expectException(LogicException::class);

        $statement($this->people);
    }

    // --- no ORM insert ----------------------------------------------------------------------------------------------

    public function testSavingANewEntityThrowsAndStoresNothing(): void
    {
        $this->actAsSuperAdministrator();
        $person = $this->people->newEntity(['name' => 'Ana']);

        try {
            $this->people->save($person, ['developmentScope' => DevelopmentScope::all()]);
            $this->fail('A new person must never be saved through the ORM.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('createFor', $e->getMessage());
        }
        $this->commitSession();
        $this->assertSame(0, $this->peopleCount());
    }

    // --- createFor --------------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{string}>
     */
    public static function creators(): array
    {
        return ['administrator' => ['administrator'], 'data_entry' => ['data_entry']];
    }

    #[DataProvider('creators')]
    public function testCreateForCallsPersonCreateAndReturnsThePersonLinkedToTheDevelopment(string $role): void
    {
        $scope = $this->actAsRole($role);
        $users = (int)$this->db()->execute('SELECT COUNT(*) FROM users')->fetch()[0];
        $assignments = (int)$this->db()->execute('SELECT COUNT(*) FROM role_assignments')->fetch()[0];

        $person = $this->people->createFor($this->devA, [
            'name' => ' Ana Pérez ', 'email' => 'ana@example.test', 'phone' => '555 0100', 'rfc' => ' xaxx010101000 ',
        ], $scope);
        $this->commitSession();

        $this->assertInstanceOf(Person::class, $person);
        $this->assertSame([], $person->getErrors());
        $this->assertFalse($person->isNew());
        $this->assertSame([
            'name' => 'Ana Pérez', 'email' => 'ana@example.test', 'phone' => '555 0100', 'rfc' => 'XAXX010101000',
        ], $this->personRow((int)$person->id));
        $this->assertSame(
            [[(int)$person->id, $this->devA]],
            $this->db()->execute('SELECT person_id, development_id FROM person_developments')->fetchAll(),
        );
        $this->assertSame($users, (int)$this->db()->execute('SELECT COUNT(*) FROM users')->fetch()[0]);
        $this->assertSame(
            $assignments,
            (int)$this->db()->execute('SELECT COUNT(*) FROM role_assignments')->fetch()[0],
        );
    }

    public function testOptionalFieldsMayBeLeftOutOrBlankAndAreStoredAsNull(): void
    {
        $scope = $this->actAsRole('data_entry');

        $person = $this->people->createFor($this->devA, ['name' => 'Luis', 'email' => '  ', 'phone' => ''], $scope);
        $this->commitSession();

        $this->assertSame(
            ['name' => 'Luis', 'email' => null, 'phone' => null, 'rfc' => null],
            $this->personRow((int)$person->id),
        );
    }

    public function testAMissingOrBlankNameIsRejectedAndNothingIsStored(): void
    {
        $scope = $this->actAsRole('administrator');

        foreach ([[], ['name' => ''], ['name' => "  \t "], ['name' => ['x']]] as $data) {
            $person = $this->people->createFor($this->devA, $data, $scope);
            $this->assertTrue($person->isNew());
            $this->assertNotSame([], $person->getError('name'), json_encode($data));
        }
        $this->commitSession();
        $this->assertSame(0, $this->peopleCount());
    }

    public function testInvalidValuesAreFieldErrorsAndNothingIsStored(): void
    {
        $scope = $this->actAsRole('administrator');

        $person = $this->people->createFor($this->devA, [
            'name' => str_repeat('a', 151),
            'email' => 'not-an-address',
            'phone' => str_repeat('1', 31),
            'rfc' => str_repeat('A', 14),
        ], $scope);
        $this->commitSession();

        $this->assertSame(['name', 'email', 'phone', 'rfc'], array_keys($person->getErrors()));
        $this->assertSame(0, $this->peopleCount());
    }

    public function testCreateForTakesOnlyTheFourFieldsFromTheData(): void
    {
        $scope = $this->actAsRole('administrator');

        $person = $this->people->createFor($this->devA, [
            'name' => 'Ana', 'id' => 999, 'created' => '2000-01-01', 'modified' => '2000-01-01', 'development_id' => $this->devB,
        ], $scope);
        $this->commitSession();

        $this->assertNotSame(999, (int)$person->id);
        $this->assertSame(
            [[(int)$person->id, $this->devA]],
            $this->db()->execute('SELECT person_id, development_id FROM person_developments')->fetchAll(),
        );
        $this->assertGreaterThan(2020, (int)$this->db()->execute('SELECT EXTRACT(YEAR FROM created) FROM people')->fetch()[0]);
    }

    public function testCreateForRefusesADevelopmentOutsideTheScopeBeforeTouchingTheDatabase(): void
    {
        $this->actAsSuperAdministrator();

        $this->expectException(DevelopmentScopeViolation::class);

        try {
            $this->people->createFor($this->devB, ['name' => 'Ana'], DevelopmentScope::only([$this->devA]));
        } finally {
            $this->commitSession();
            $this->assertSame(0, $this->peopleCount());
        }
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function refusedByTheDatabase(): array
    {
        return [
            'accountant' => ['accountant', false],
            'supervisor' => ['supervisor', false],
            'resident' => ['resident', false],
            'read-only administrator' => ['administrator', true],
        ];
    }

    /**
     * The scope handed in is wide on purpose: the application layer lets the call through and the definer function
     * is what refuses, with the same exception as a development outside the scope.
     */
    #[DataProvider('refusedByTheDatabase')]
    public function testTheDatabaseRefusalIsTheSameViolationAsAForeignDevelopment(string $role, bool $readOnly): void
    {
        $this->actAsRole($role, $readOnly);

        try {
            $this->people->createFor($this->devA, ['name' => 'Ana'], DevelopmentScope::all());
            $this->fail('The definer function must refuse this actor.');
        } catch (DevelopmentScopeViolation) {
            // Expected.
        }
        // The failed call was rolled back on its own: the session still works.
        $this->assertSame(0, (int)$this->appDb()->execute('SELECT COUNT(*) FROM people')->fetch()[0]);
        $this->commitSession();
        $this->assertSame(0, $this->peopleCount());
    }

    public function testCreateForRefusesAnArchivedDevelopment(): void
    {
        $scope = $this->actAsRole('administrator');
        $this->endSession();
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$this->devA]);
        $this->actAs((int)$this->db()->execute("SELECT id FROM users WHERE username = 'ann'")->fetch()[0]);

        $this->expectException(DevelopmentScopeViolation::class);

        $this->people->createFor($this->devA, ['name' => 'Ana'], $scope);
    }

    // --- writes: read first (task 11.8) -----------------------------------------------------------------------------

    public function testAPersonReadUnderTheScopeIsSavedUnderIt(): void
    {
        $id = $this->createPerson('Ana', [$this->devA]);
        $scope = $this->actAsRole('data_entry');
        $person = $this->readIn($id, $scope);

        $this->people->patchEntity($person, ['phone' => '555 0199']);
        $saved = $this->people->save($person, ['developmentScope' => $scope]);
        $this->commitSession();

        $this->assertInstanceOf(Person::class, $saved);
        $this->assertSame('555 0199', $this->personRow($id)['phone'] ?? null);
    }

    public function testAWriteWithoutTheReadIsRefused(): void
    {
        $id = $this->createPerson('Ana', [$this->devA]);
        $this->actAsSuperAdministrator();
        // Never read through the table: built by hand and presented as an existing row.
        $forged = new Person(['id' => $id, 'name' => 'Forged'], ['markNew' => false, 'markClean' => true]);
        $forged->set('name', 'Forged');

        try {
            $this->people->save($forged, ['developmentScope' => DevelopmentScope::only([$this->devA])]);
            $this->fail('A person that was never read under a scope must not be written.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('read', $e->getMessage());
        }
        $this->commitSession();
        $this->assertSame('Ana', $this->personRow($id)['name'] ?? null);
    }

    public function testACopyOfAReadPersonIsNotTheRead(): void
    {
        $id = $this->createPerson('Ana', [$this->devA]);
        $this->actAsSuperAdministrator();
        $scope = DevelopmentScope::only([$this->devA]);
        $copy = clone $this->readIn($id, $scope);
        $copy->set('name', 'Copy');

        $this->expectException(LogicException::class);

        $this->people->save($copy, ['developmentScope' => $scope]);
    }

    public function testAPersonReadInOneScopeCannotBeSavedUnderAnotherThatDoesNotReachIt(): void
    {
        $id = $this->createPerson('Ana', [$this->devA]);
        $this->actAsSuperAdministrator();
        $person = $this->readIn($id, DevelopmentScope::only([$this->devA]));
        $person->set('name', 'Moved');

        try {
            $this->people->save($person, ['developmentScope' => DevelopmentScope::only([$this->devB])]);
            $this->fail('The save scope must reach the person.');
        } catch (DevelopmentScopeViolation) {
            // Expected.
        }
        $this->commitSession();
        $this->assertSame('Ana', $this->personRow($id)['name'] ?? null);
    }

    public function testARedirectedKeyIsRefusedEvenWhenThePersonWasRead(): void
    {
        $mine = $this->createPerson('Mine', [$this->devA]);
        $foreign = $this->createPerson('Foreign', [$this->devB]);
        $this->actAsSuperAdministrator();
        $scope = DevelopmentScope::only([$this->devA]);
        $person = $this->readIn($mine, $scope);
        $person->set('id', $foreign);
        $person->set('name', 'Taken over');

        try {
            $this->people->save($person, ['developmentScope' => $scope]);
            $this->fail('A changed key must be refused.');
        } catch (DevelopmentScopeViolation) {
            // Expected.
        }
        $this->commitSession();
        $this->assertSame('Foreign', $this->personRow($foreign)['name'] ?? null);
        $this->assertSame('Mine', $this->personRow($mine)['name'] ?? null);
    }

    public function testARedirectedKeyIsRefusedEvenWhenTheOtherPersonIsInTheSameScope(): void
    {
        $mine = $this->createPerson('Mine', [$this->devA]);
        $neighbour = $this->createPerson('Neighbour', [$this->devA]);
        $this->actAsSuperAdministrator();
        $scope = DevelopmentScope::only([$this->devA]);
        $person = $this->readIn($mine, $scope);
        $person->set('id', $neighbour);
        $person->set('name', 'Taken over');

        try {
            $this->people->save($person, ['developmentScope' => $scope]);
            $this->fail('A changed key must be refused.');
        } catch (DevelopmentScopeViolation) {
            // Expected.
        }
        $this->commitSession();
        $this->assertSame('Neighbour', $this->personRow($neighbour)['name'] ?? null);
    }

    public function testASaveWithoutAScopeThrows(): void
    {
        $id = $this->createPerson('Ana', [$this->devA]);
        $this->actAsSuperAdministrator();
        $person = $this->readIn($id, DevelopmentScope::only([$this->devA]));
        $person->set('name', 'No scope');

        $this->expectException(MissingDevelopmentScopeException::class);

        $this->people->save($person);
    }

    public function testASharedPersonIsRefusedByTheDatabaseToAnEditorOfOneDevelopment(): void
    {
        $id = $this->createPerson('Shared', [$this->devA, $this->devB]);
        $scope = $this->actAsRole('administrator');
        $person = $this->readIn($id, $scope);
        $person->set('name', 'Edited');

        try {
            $this->people->save($person, ['developmentScope' => $scope]);
            $this->fail('The update policy must refuse a person shared with a development the editor cannot manage.');
        } catch (DevelopmentScopeViolation) {
            // Expected: no row matched.
        }
        $this->commitSession();
        $this->assertSame('Shared', $this->personRow($id)['name'] ?? null);
    }
}
