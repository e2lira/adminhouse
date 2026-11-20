<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database\Rls;

use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

/**
 * Who creates and edits people at the database level (spec people: Create person through definer function, Editing
 * people, Database enforcement; design AD8 and S-3).
 *
 * - `person_create(development, name, email, phone, rfc)` follows `people.manage` through `permission_dev_ids`;
 * - `person_in_permission_scope(person, code)` is true only when the person has a link and EVERY linked development
 *   is one where the session holds the code (an archived development drops out of a write code, S-3);
 * - the people UPDATE policy is `is_super OR own row OR person_in_permission_scope(id, 'people.manage')`.
 *
 * A denied UPDATE matches no row instead of raising, so every denial is also read back as the owner after the session
 * is committed.
 */
class RlsPeopleTest extends RlsTestCase
{
    private const DENIED = '42501';

    private int $devC;

    private int $supervisorA;

    private int $readOnlyA;

    /**
     * Administrator of A and of the (later archived) development C.
     */
    private int $adminAC;

    /**
     * Administrator of A and of B: the one editor who may change a person shared by both.
     */
    private int $adminAB;

    private int $archivedAdmin;

    private int $personA;

    private int $personAC;

    private int $personNoLinks;

    protected function setUp(): void
    {
        parent::setUp();

        $this->devC = $this->createDevelopment('Development C');
        $this->supervisorA = $this->createUser('supervisor_a', null);
        $this->assignRole($this->supervisorA, 'supervisor', $this->devA);
        $this->readOnlyA = $this->createUser('read_only_a', null);
        $this->assignRole($this->readOnlyA, 'administrator', $this->devA, -30, null, true);
        $this->adminAC = $this->createUser('admin_ac', null);
        $this->assignRole($this->adminAC, 'administrator', $this->devA);
        $this->assignRole($this->adminAC, 'administrator', $this->devC);
        $this->adminAB = $this->createUser('admin_ab', null);
        $this->assignRole($this->adminAB, 'administrator', $this->devA);
        $this->assignRole($this->adminAB, 'administrator', $this->devB);
        $this->archivedAdmin = $this->createUser('archived_admin', null);
        $this->assignRole($this->archivedAdmin, 'administrator', $this->devC);

        $this->personA = $this->createPerson('Only A', [$this->devA]);
        $this->personAC = $this->createPerson('A and C', [$this->devA, $this->devC]);
        $this->personNoLinks = $this->createPerson('No links', []);
    }

    private function archiveC(): void
    {
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$this->devC]);
    }

    private function peopleCount(): int
    {
        return (int)$this->db()->execute('SELECT COUNT(*) FROM people')->fetch()[0];
    }

    /**
     * Run a statement as the runtime role inside a savepoint and report the SQLSTATE and message of the failure.
     *
     * @param string $sql Statement.
     * @param list<mixed> $params Positional parameters.
     * @return array{string, string}|null SQLSTATE and message, or null when the statement succeeded.
     */
    private function failureOf(string $sql, array $params = []): ?array
    {
        $connection = $this->appDb();
        $connection->execute('SAVEPOINT attempt');
        try {
            $connection->execute($sql, $params)->fetchAll();
            $connection->execute('RELEASE SAVEPOINT attempt');

            return null;
        } catch (Throwable $e) {
            $connection->execute('ROLLBACK TO SAVEPOINT attempt');
            for (; $e !== null; $e = $e->getPrevious()) {
                if ($e instanceof PDOException && isset($e->errorInfo[0])) {
                    return [(string)$e->errorInfo[0], (string)($e->errorInfo[2] ?? '')];
                }
            }

            return ['unknown', ''];
        }
    }

    private function create(int $developmentId, string $name = 'Created'): ?array
    {
        return $this->failureOf(
            'SELECT person_create(?, ?, ?, ?, ?)',
            [$developmentId, $name, 'created@example.test', '555', 'RFC123'],
        );
    }

    // person_create -----------------------------------------------------------------------------------------------

    /**
     * @return array<string, array{string}>
     */
    public static function creators(): array
    {
        return ['adminA' => ['adminA'], 'dataEntryA' => ['dataEntryA']];
    }

    #[DataProvider('creators')]
    public function testHoldersOfPeopleManageCreateThePersonAndTheLinkTogether(string $actor): void
    {
        $before = $this->peopleCount();
        $this->actAs($this->{$actor});

        $this->assertNull($this->create($this->devA));
        $this->commitSession();

        $this->assertSame($before + 1, $this->peopleCount());
        $row = $this->db()->execute(
            "SELECT id, email, phone, rfc FROM people WHERE name = 'Created'",
        )->fetch('assoc');
        $this->assertSame(['email' => 'created@example.test', 'phone' => '555', 'rfc' => 'RFC123'], [
            'email' => $row['email'], 'phone' => $row['phone'], 'rfc' => $row['rfc'],
        ]);
        $links = $this->db()->execute(
            'SELECT development_id FROM person_developments WHERE person_id = ?',
            [$row['id']],
        )->fetchAll();
        $this->assertSame([[(string)$this->devA]], array_map(static fn(array $l): array => [(string)$l[0]], $links));
    }

    public function testTheSuperAdministratorCreatesInAnyDevelopment(): void
    {
        $this->actAs($this->superUser);

        $this->assertNull($this->create($this->devB));
        $this->commitSession();

        $this->assertSame(1, (int)$this->db()->execute(
            "SELECT COUNT(*) FROM people p JOIN person_developments pd ON pd.person_id = p.id
             WHERE p.name = 'Created' AND pd.development_id = ?",
            [$this->devB],
        )->fetch()[0]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function refusedCreators(): array
    {
        return [
            'accountantA' => ['accountantA'],
            'supervisorA' => ['supervisorA'],
            'guardA' => ['guardA'],
            'resident' => ['resident'],
            'readOnlyA' => ['readOnlyA'],
            'disabledAdmin' => ['disabledAdmin'],
            'endedAdmin' => ['endedAdmin'],
            'futureAdmin' => ['futureAdmin'],
            'loner' => ['loner'],
        ];
    }

    #[DataProvider('refusedCreators')]
    public function testEveryoneElseIsRefusedAndNothingIsStored(string $actor): void
    {
        $before = $this->peopleCount();
        $this->actAs($this->{$actor});

        $failure = $this->create($this->devA);

        $this->assertNotNull($failure);
        $this->assertSame(self::DENIED, $failure[0]);
        $this->commitSession();
        $this->assertSame($before, $this->peopleCount());
        $this->assertSame(0, (int)$this->db()->execute(
            "SELECT COUNT(*) FROM people WHERE name = 'Created'",
        )->fetch()[0]);
    }

    public function testNoSessionAndAJobAreRefused(): void
    {
        $this->actAs(null);
        $this->assertSame(self::DENIED, $this->create($this->devA)[0] ?? null);
        $this->endSession();

        $this->actAsJob([$this->devA]);
        $this->assertSame(self::DENIED, $this->create($this->devA)[0] ?? null);
        $this->commitSession();

        $this->assertSame(0, (int)$this->db()->execute(
            "SELECT COUNT(*) FROM people WHERE name = 'Created'",
        )->fetch()[0]);
    }

    public function testAnAdministratorOfAIsRefusedForBWithTheSameErrorAsForADevelopmentThatDoesNotExist(): void
    {
        $this->actAs($this->adminA);

        $foreign = $this->create($this->devB);
        $missing = $this->create(999999999);
        $unset = $this->failureOf("SELECT person_create(NULL, 'Created', NULL, NULL, NULL)");

        $this->assertNotNull($foreign);
        $this->assertSame(self::DENIED, $foreign[0]);
        $this->assertSame($missing, $foreign);
        $this->assertSame($unset, $foreign);
        $this->commitSession();
        $this->assertSame(0, (int)$this->db()->execute(
            "SELECT COUNT(*) FROM people WHERE name = 'Created'",
        )->fetch()[0]);
    }

    public function testAnArchivedDevelopmentRefusesCreationAndAnUnarchivedOneAllowsIt(): void
    {
        $this->actAs($this->archivedAdmin);
        $this->assertNull($this->create($this->devC, 'Before archive'));
        $this->commitSession();

        $this->archiveC();
        $this->actAs($this->archivedAdmin);
        $failure = $this->create($this->devC, 'After archive');
        $this->commitSession();

        $this->assertNotNull($failure);
        $this->assertSame(self::DENIED, $failure[0]);
        $this->assertSame(
            ['Before archive'],
            array_column($this->db()->execute(
                "SELECT name FROM people WHERE name IN ('Before archive', 'After archive')",
            )->fetchAll('assoc'), 'name'),
        );
    }

    public function testACreationThatFailsMidwayLeavesNothingBehind(): void
    {
        $before = $this->peopleCount();
        $this->actAs($this->dataEntryA);

        $failure = $this->failureOf('SELECT person_create(?, NULL, NULL, NULL, NULL)', [$this->devA]);

        $this->assertNotNull($failure);
        $this->assertSame('23502', $failure[0]);
        $this->commitSession();
        $this->assertSame($before, $this->peopleCount());
    }

    public function testNoOneInsertsIntoPeopleDirectly(): void
    {
        $before = $this->peopleCount();
        foreach (['adminA', 'dataEntryA', 'superUser'] as $actor) {
            $this->actAs($this->{$actor});
            $this->assertRejected("INSERT INTO people (name, created, modified) VALUES ('Direct', NOW(), NOW())");
            $this->commitSession();
        }

        $this->assertSame($before, $this->peopleCount());
    }

    // person_in_permission_scope ----------------------------------------------------------------------------------

    private function inScope(int $personId, string $code = 'people.manage'): bool
    {
        return (bool)$this->appDb()->execute(
            'SELECT person_in_permission_scope(?, ?)',
            [$personId, $code],
        )->fetch()[0];
    }

    public function testAPersonLinkedOnlyToDevelopmentsOfTheSessionIsInScope(): void
    {
        foreach (['adminA', 'dataEntryA'] as $actor) {
            $this->actAs($this->{$actor});
            $this->assertTrue($this->inScope($this->personA), $actor);
            $this->endSession();
        }
    }

    public function testAPersonLinkedToADevelopmentOutsideTheScopeIsNotInScope(): void
    {
        $this->actAs($this->adminA);

        $this->assertFalse($this->inScope($this->personShared), 'shared with B');
        $this->assertFalse($this->inScope($this->personB), 'only B');
    }

    public function testEveryLinkMustBeInScopeNotJustOne(): void
    {
        // The same shared person: in scope for the one editor who holds the code in both developments.
        $this->actAs($this->adminAB);
        $this->assertTrue($this->inScope($this->personShared));
        $this->endSession();

        $this->actAs($this->adminA);
        $this->assertFalse($this->inScope($this->personShared));
    }

    public function testAPersonWithoutLinksAndAnUnknownPersonAreNotInScope(): void
    {
        $this->actAs($this->adminA);

        $this->assertFalse($this->inScope($this->personNoLinks));
        $this->assertFalse($this->inScope(999999999));
        $this->assertTrue($this->inScope($this->personA), 'the control: a linked person is in scope');
    }

    public function testTheCodeDecidesTheScope(): void
    {
        $this->actAs($this->accountantA);
        $this->assertFalse($this->inScope($this->personA, 'people.manage'));
        $this->assertTrue($this->inScope($this->personA, 'people.view'));
        $this->assertFalse($this->inScope($this->personA, 'no.such_code'));
        $this->endSession();

        $this->actAs($this->resident);
        $this->assertFalse($this->inScope($this->personA, 'people.view'));
    }

    public function testAnArchivedLinkedDevelopmentDropsOutOfAWriteCodeButNotOutOfAReadCode(): void
    {
        $this->actAs($this->adminAC);
        $this->assertTrue($this->inScope($this->personAC), 'control: before the archive');
        $this->endSession();

        $this->archiveC();
        $this->actAs($this->adminAC);

        $this->assertFalse($this->inScope($this->personAC, 'people.manage'));
        $this->assertTrue($this->inScope($this->personAC, 'people.view'));
        $this->assertTrue($this->inScope($this->personA, 'people.manage'), 'a person of A only is unaffected');
    }

    public function testAReadOnlyAssignmentAndAJobAreNeverInScopeOfAWriteCode(): void
    {
        $this->actAs($this->readOnlyA);
        $this->assertFalse($this->inScope($this->personA, 'people.manage'));
        $this->assertTrue($this->inScope($this->personA, 'people.view'));
        $this->endSession();

        $this->actAsJob([$this->devA]);
        $this->assertFalse($this->inScope($this->personA, 'people.view'));
    }

    public function testTheSuperAdministratorHasNoRowsInThePermissionScope(): void
    {
        // Policies add is_super() next to it, as for permission_dev_ids.
        $this->actAs($this->superUser);

        $this->assertFalse($this->inScope($this->personA));
    }

    public function testTheFunctionsAreDefinerFunctionsOnlyTheRuntimeRoleExecutes(): void
    {
        foreach (['person_create', 'person_in_permission_scope'] as $name) {
            $row = $this->db()->execute(
                "SELECT p.prosecdef, pg_get_userbyid(p.proowner) AS owner, p.proconfig::text AS config,
                        has_function_privilege('kualitet_app', p.oid, 'EXECUTE') AS app,
                        has_function_privilege('public', p.oid, 'EXECUTE') AS public
                 FROM pg_proc p WHERE p.proname = ? AND p.pronamespace = 'public'::regnamespace",
                [$name],
            )->fetch('assoc');

            $this->assertTrue((bool)$row['prosecdef'], $name);
            $this->assertSame('kualitet_definer', $row['owner'], $name);
            $this->assertStringContainsString('search_path=pg_catalog, pg_temp', $row['config'], $name);
            $this->assertTrue((bool)$row['app'], $name);
            $this->assertFalse((bool)$row['public'], $name);
        }
    }

    // UPDATE policy -----------------------------------------------------------------------------------------------

    private function phoneOf(int $personId): ?string
    {
        $phone = $this->db()->execute('SELECT phone FROM people WHERE id = ?', [$personId])->fetch()[0];

        return $phone === null ? null : (string)$phone;
    }

    #[DataProvider('creators')]
    public function testHoldersOfPeopleManageEditAPersonLinkedOnlyToTheirDevelopment(string $actor): void
    {
        $this->actAs($this->{$actor});

        $this->assertAllowed('UPDATE people SET phone = ? WHERE id = ?', ['5551', $this->personA]);
        $this->commitSession();

        $this->assertSame('5551', $this->phoneOf($this->personA));
    }

    #[DataProvider('creators')]
    public function testASharedPersonIsRefusedToEditorsOfOnlyOneOfItsDevelopments(string $actor): void
    {
        $this->actAs($this->{$actor});

        $this->assertDenied('UPDATE people SET phone = ? WHERE id = ?', ['5552', $this->personShared]);
        $this->commitSession();

        $this->assertNull($this->phoneOf($this->personShared));
    }

    public function testAnArchivedLinkedDevelopmentBlocksEditingAPersonItShares(): void
    {
        $this->actAs($this->adminAC);
        $this->assertAllowed('UPDATE people SET phone = ? WHERE id = ?', ['5553', $this->personAC]);
        $this->commitSession();
        $this->assertSame('5553', $this->phoneOf($this->personAC), 'control: allowed before the archive');

        $this->archiveC();
        $this->actAs($this->adminAC);
        $this->assertDenied('UPDATE people SET phone = ? WHERE id = ?', ['5554', $this->personAC]);
        $this->commitSession();

        $this->assertSame('5553', $this->phoneOf($this->personAC));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function refusedEditors(): array
    {
        return [
            'accountantA' => ['accountantA'],
            'supervisorA' => ['supervisorA'],
            'readOnlyA' => ['readOnlyA'],
            'guardA' => ['guardA'],
            'disabledAdmin' => ['disabledAdmin'],
            'endedAdmin' => ['endedAdmin'],
            'futureAdmin' => ['futureAdmin'],
        ];
    }

    #[DataProvider('refusedEditors')]
    public function testEveryoneElseEditsNothing(string $actor): void
    {
        $this->actAs($this->{$actor});

        $this->assertDenied('UPDATE people SET phone = ? WHERE id = ?', ['5555', $this->personA]);
        $this->commitSession();

        $this->assertNull($this->phoneOf($this->personA));
    }

    public function testAnEditorWithoutALinkToThePersonSeesAndChangesNothing(): void
    {
        $this->actAs($this->adminB);

        $this->assertSame([], $this->ids('SELECT id FROM people WHERE id = ?', [$this->personA]));
        $this->assertDenied('UPDATE people SET phone = ? WHERE id = ?', ['5556', $this->personA]);
        $this->commitSession();

        $this->assertNull($this->phoneOf($this->personA));
    }

    public function testAPersonWithoutLinksIsNotEditedByAnyAdministrator(): void
    {
        $this->actAs($this->adminA);

        $this->assertDenied('UPDATE people SET phone = ? WHERE id = ?', ['5557', $this->personNoLinks]);
        $this->commitSession();

        $this->assertNull($this->phoneOf($this->personNoLinks));
    }

    public function testOnlyTheSuperAdministratorAndTheEditorOfEveryLinkChangeASharedPerson(): void
    {
        $this->actAs($this->adminAB);
        $this->assertAllowed('UPDATE people SET phone = ? WHERE id = ?', ['5558', $this->personShared]);
        $this->commitSession();
        $this->assertSame('5558', $this->phoneOf($this->personShared));

        $this->actAs($this->superUser);
        $this->assertAllowed('UPDATE people SET phone = ? WHERE id = ?', ['5559', $this->personShared]);
        $this->commitSession();
        $this->assertSame('5559', $this->phoneOf($this->personShared));
    }

    public function testAPersonStillEditsTheirOwnRow(): void
    {
        $this->actAs($this->resident);

        $this->assertAllowed('UPDATE people SET phone = ? WHERE id = ?', ['5560', $this->personResident]);
        $this->assertDenied('UPDATE people SET phone = ? WHERE id = ?', ['5560', $this->personA]);
        $this->commitSession();

        $this->assertSame('5560', $this->phoneOf($this->personResident));
        $this->assertNull($this->phoneOf($this->personA));
    }
}
