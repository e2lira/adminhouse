<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Authorization\AccessControl;
use App\Authorization\DevelopmentScope;
use App\Authorization\DevelopmentScopeStack;
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

    /**
     * @return array<string, array{list<string>, list<string>}> Developments of the scope (A, B) and who it finds.
     */
    public static function scopes(): array
    {
        return [
            'A' => [['A'], ['Both', 'Only A']],
            'B' => [['B'], ['Both', 'Only B']],
            'A and B: the shared person once' => [['A', 'B'], ['Both', 'Only A', 'Only B']],
            'neither' => [[], []],
        ];
    }

    /**
     * @param list<string> $letters Developments of the scope.
     * @param list<string> $expected Names found, ordered.
     */
    #[DataProvider('scopes')]
    public function testAScopeNarrowsTheListToThePeopleLinkedToItsDevelopments(array $letters, array $expected): void
    {
        $this->createPerson('Only A', [$this->devA]);
        $this->createPerson('Only B', [$this->devB]);
        $this->createPerson('Both', [$this->devA, $this->devB]);
        $this->createPerson('Nowhere', []);
        $this->actAsSuperAdministrator();
        $ids = array_map(fn(string $letter): int => $letter === 'A' ? $this->devA : $this->devB, $letters);

        $names = $this->people->find('all', developmentScope: DevelopmentScope::only($ids))
            ->orderBy(['People.name' => 'ASC'])->all()->extract('name')->toList();

        $this->assertSame($expected, $names);
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
        // Control: the same call succeeds before the archive, so the refusal below is the archive's doing.
        $this->people->createFor($this->devA, ['name' => 'Antes'], $scope);
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$this->devA]);

        try {
            $this->people->createFor($this->devA, ['name' => 'Despues'], $scope);
            $this->fail('An archived development must refuse the creation.');
        } catch (DevelopmentScopeViolation) {
            // Expected.
        }
        $this->commitSession();
        $this->assertSame(1, $this->peopleCount());
        $this->assertSame(0, (int)$this->db()->execute("SELECT COUNT(*) FROM people WHERE name = 'Despues'")->fetch()[0]);
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

    // --- review round 1 -----------------------------------------------------------------------------------------------

    /**
     * Intended (spec "Editing people": the person themself): the own-row branch of the update policy does not look at
     * the other links, so a user may edit their own person even when it is shared with a development they do not
     * manage. The layer-1 read still has to reach the person.
     */
    public function testAUserMayEditTheirOwnSharedPerson(): void
    {
        $id = $this->createPerson('Shared', [$this->devA, $this->devB]);
        $scope = $this->actAsRole('data_entry');
        $this->endSession();
        $this->db()->execute("UPDATE users SET person_id = ? WHERE username = 'ann'", [$id]);
        $this->actAs((int)$this->db()->execute("SELECT id FROM users WHERE username = 'ann'")->fetch()[0]);
        $person = $this->readIn($id, $scope);
        $person->set('phone', '555 0100');

        $this->people->save($person, ['developmentScope' => $scope]);
        $this->commitSession();

        $this->assertSame('555 0100', $this->personRow($id)['phone'] ?? null);
    }

    /**
     * Intended (AD7: the super-administrator is not narrowed by the archive state): editing a person linked to an
     * archived development is refused to every editor and allowed to the super-administrator.
     */
    public function testTheSuperAdministratorEditsAPersonLinkedToAnArchivedDevelopment(): void
    {
        $archived = $this->createDevelopment('Archivo');
        $id = $this->createPerson('Shared', [$this->devA, $archived]);
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$archived]);
        $this->actAsSuperAdministrator();
        $scope = DevelopmentScope::all();
        $person = $this->readIn($id, $scope);
        $person->set('name', 'Edited');

        $this->people->save($person, ['developmentScope' => $scope]);
        $this->commitSession();

        $this->assertSame('Edited', $this->personRow($id)['name'] ?? null);
    }

    public function testAPersonIsNeverDeletedThroughTheTable(): void
    {
        $id = $this->createPerson('Ana', [$this->devA]);
        $this->actAsSuperAdministrator();
        $scope = DevelopmentScope::all();
        $person = $this->readIn($id, $scope);

        try {
            $this->people->delete($person, ['developmentScope' => $scope]);
            $this->fail('Nobody deletes a person through the application.');
        } catch (LogicException) {
            // Expected.
        }
        $this->commitSession();
        $this->assertNotNull($this->personRow($id));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function controlCharacters(): array
    {
        $cases = [];
        foreach (['name', 'email', 'phone', 'rfc'] as $field) {
            $cases[$field . ': NUL'] = [$field, "ab\0cd"];
            $cases[$field . ': bell'] = [$field, "ab\x07cd"];
            $cases[$field . ': newline'] = [$field, "ab\ncd"];
            $cases[$field . ': invalid UTF-8'] = [$field, "ab\xC3\x28cd"];
        }

        return $cases;
    }

    #[DataProvider('controlCharacters')]
    public function testControlCharactersAreFieldErrorsOnCreateAndNeverReachTheDatabase(string $field, string $value): void
    {
        $scope = $this->actAsRole('administrator');

        $person = $this->people->createFor($this->devA, ['name' => 'Ana', $field => $value], $scope);
        $this->commitSession();

        $this->assertSame([$field], array_keys($person->getErrors()));
        $this->assertSame(0, $this->peopleCount());
    }

    #[DataProvider('controlCharacters')]
    public function testControlCharactersAreFieldErrorsOnEditAndTheRowIsKept(string $field, string $value): void
    {
        $id = $this->createPerson('Ana', [$this->devA]);
        $scope = $this->actAsRole('administrator');
        $person = $this->readIn($id, $scope);

        $this->people->patchEntity($person, [$field => $value]);
        $saved = $this->people->save($person, ['developmentScope' => $scope]);
        $this->commitSession();

        $this->assertFalse($saved);
        $this->assertSame([$field], array_keys($person->getErrors()));
        $this->assertSame('Ana', $this->personRow($id)['name'] ?? null);
    }

    /**
     * A well-formed address of 255 characters (local part and labels within their limits) fails only the length rule.
     */
    public function testAValidEmailOf255CharactersFailsOnlyTheLengthRule(): void
    {
        $scope = $this->actAsRole('administrator');
        $email = str_repeat('a', 50) . '@' . str_repeat(str_repeat('b', 60) . '.', 3) . str_repeat('c', 21);
        $this->assertSame(255, strlen($email));

        $person = $this->people->createFor($this->devA, ['name' => 'Ana', 'email' => $email], $scope);

        $this->assertSame(['email'], array_keys($person->getErrors()));
        $this->assertSame(['maxLength'], array_keys($person->getError('email')));
    }

    public function testValuesAtTheLimitsAreAccepted(): void
    {
        $scope = $this->actAsRole('administrator');
        $email = str_repeat('a', 50) . '@' . str_repeat(str_repeat('b', 60) . '.', 3) . str_repeat('c', 20);
        $this->assertSame(254, strlen($email));
        $data = [
            'name' => str_repeat('n', 150), 'email' => $email, 'phone' => str_repeat('1', 30),
            'rfc' => str_repeat('a', 13),
        ];

        $person = $this->people->createFor($this->devA, $data, $scope);
        $this->commitSession();

        $this->assertSame([], $person->getErrors());
        $this->assertSame(
            [$data['name'], $email, $data['phone'], str_repeat('A', 13)],
            array_values($this->personRow((int)$person->id) ?? []),
        );
    }

    public function testANullNameIsRejected(): void
    {
        $scope = $this->actAsRole('administrator');

        $person = $this->people->createFor($this->devA, ['name' => null], $scope);

        $this->assertNotSame([], $person->getError('name'));
        $this->assertTrue($person->isNew());
    }

    /**
     * The save scope is the option narrowed by the scope that is already running: a person read under {A, B} cannot
     * be saved inside an operation that only reaches B.
     */
    public function testARunningScopeNarrowsTheSaveScope(): void
    {
        $id = $this->createPerson('Ana', [$this->devA]);
        $this->actAsSuperAdministrator();
        $wide = DevelopmentScope::only([$this->devA, $this->devB]);
        $person = $this->readIn($id, $wide);
        $person->set('name', 'Moved');

        try {
            DevelopmentScopeStack::run(
                DevelopmentScope::only([$this->devB]),
                fn() => $this->people->save($person, ['developmentScope' => $wide]),
            );
            $this->fail('The running scope must narrow the save scope.');
        } catch (DevelopmentScopeViolation) {
            // Expected.
        }
        $this->commitSession();
        $this->assertSame('Ana', $this->personRow($id)['name'] ?? null);
    }
}
