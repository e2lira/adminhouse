<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database\Rls;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Who reads people at the database level (design amendment A2, user decision 2026-11-19).
 *
 * The people SELECT policy is `is_super OR own row OR the person has a link to a development in
 * permission_dev_ids('people.view')`, and `person_developments` follows the same code (plus the session's own links).
 * A role without `people.view` (resident, guard) reads only its own person; a read-only assignment still reads (the
 * code ends in `.view`); an archived development is still read (view codes keep archived ones); a job reads nothing
 * (permission_dev_ids is request-only).
 */
class RlsPeopleReadTest extends RlsTestCase
{
    private int $devC;

    private int $supervisorA;

    private int $readOnlyA;

    private int $archivedAdmin;

    private int $personOnlyA;

    private int $personAC;

    private int $personOnlyC;

    private int $personGuard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->devC = $this->createDevelopment('Development C');
        $this->supervisorA = $this->createUser('supervisor_a', null);
        $this->assignRole($this->supervisorA, 'supervisor', $this->devA);
        $this->readOnlyA = $this->createUser('read_only_a', null);
        $this->assignRole($this->readOnlyA, 'administrator', $this->devA, -30, null, true);
        $this->archivedAdmin = $this->createUser('archived_admin', null);
        $this->assignRole($this->archivedAdmin, 'administrator', $this->devC);

        $this->personOnlyA = $this->createPerson('Only A', [$this->devA]);
        $this->personAC = $this->createPerson('A and C', [$this->devA, $this->devC]);
        $this->personOnlyC = $this->createPerson('Only C', [$this->devC]);
        $this->personGuard = $this->createPerson('Guard A', [$this->devA]);
        $this->db()->execute('UPDATE users SET person_id = ? WHERE id = ?', [$this->personGuard, $this->guardA]);
    }

    /**
     * @param list<int> $ids Person ids.
     * @return list<int> The same ids, sorted.
     */
    private static function sorted(array $ids): array
    {
        sort($ids);

        return $ids;
    }

    /**
     * @return list<int> Every person with a link to development A (the B-only and C-only people are the control).
     */
    private function peopleOfA(): array
    {
        return self::sorted([
            $this->personResident,
            $this->personShared,
            $this->personOnlyA,
            $this->personAC,
            $this->personGuard,
        ]);
    }

    /**
     * @return array<string, array{string}> Roles that hold `people.view` in A (a read-only assignment included).
     */
    public static function readersOfA(): array
    {
        return [
            'adminA' => ['adminA'],
            'dataEntryA' => ['dataEntryA'],
            'accountantA' => ['accountantA'],
            'supervisorA' => ['supervisorA'],
            'readOnlyA' => ['readOnlyA'],
        ];
    }

    #[DataProvider('readersOfA')]
    public function testHoldersOfPeopleViewReadThePeopleOfTheirDevelopmentAndNoOtherPerson(string $actor): void
    {
        $this->actAs($this->{$actor});

        $visible = $this->visibleIds('people');

        $this->assertSame($this->peopleOfA(), $visible);
        $this->assertNotContains($this->personB, $visible, 'the control: a person of B exists and stays hidden');
        $this->assertNotContains($this->personOnlyC, $visible, 'the control: a person of C exists and stays hidden');
    }

    public function testAResidentReadsOnlyTheirOwnPerson(): void
    {
        $this->actAs($this->resident);

        $this->assertSame([$this->personResident], $this->visibleIds('people'));
    }

    public function testAGuardReadsOnlyTheirOwnPerson(): void
    {
        $this->actAs($this->guardA);

        $this->assertSame([$this->personGuard], $this->visibleIds('people'));
    }

    public function testAGuardWithoutAPersonReadsNobody(): void
    {
        $this->db()->execute('UPDATE users SET person_id = NULL WHERE id = ?', [$this->guardA]);
        $this->actAs($this->guardA);

        $this->assertSame([], $this->visibleIds('people'));
    }

    public function testAnAdministratorOfBSeesNoPersonThatBelongsOnlyToA(): void
    {
        $this->actAs($this->adminB);

        $visible = $this->visibleIds('people');

        $this->assertSame(self::sorted([$this->personB, $this->personShared]), $visible);
        $this->assertNotContains($this->personOnlyA, $visible);
    }

    public function testAPersonSharedByAAndBIsVisibleToBothAdministrators(): void
    {
        foreach (['adminA', 'adminB'] as $actor) {
            $this->actAs($this->{$actor});
            $this->assertContains($this->personShared, $this->visibleIds('people'), $actor);
            $this->endSession();
        }
    }

    public function testAnArchivedDevelopmentStillReadsThroughTheViewCode(): void
    {
        $this->actAs($this->archivedAdmin);
        $before = $this->visibleIds('people');
        $this->endSession();
        $this->assertSame(self::sorted([$this->personAC, $this->personOnlyC]), $before, 'control: before the archive');

        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$this->devC]);
        $this->actAs($this->archivedAdmin);

        $this->assertSame($before, $this->visibleIds('people'));
    }

    public function testAJobReadsNoPerson(): void
    {
        $this->actAsJob([$this->devA, $this->devB]);

        $this->assertSame([], $this->visibleIds('people'));
        $this->assertSame([], $this->visibleIds('person_developments'));
    }

    public function testTheSuperAdministratorReadsEveryPerson(): void
    {
        $this->actAs($this->superUser);

        $everyone = array_map(
            static fn(array $row): int => (int)$row[0],
            $this->db()->execute('SELECT id FROM people ORDER BY id')->fetchAll(),
        );
        $this->assertGreaterThan(5, count($everyone));
        $this->assertSame($everyone, $this->visibleIds('people'));
    }

    public function testNoSessionReadsNoPerson(): void
    {
        $this->actAs(null);

        $this->assertSame([], $this->visibleIds('people'));
        $this->assertSame([], $this->visibleIds('person_developments'));
    }

    public function testUsersWithoutAnActiveAssignmentReadOnlyTheirOwnPerson(): void
    {
        $this->db()->execute('UPDATE users SET person_id = ? WHERE id = ?', [$this->personOnlyA, $this->endedAdmin]);
        foreach (['disabledAdmin', 'futureAdmin', 'loner'] as $actor) {
            $this->actAs($this->{$actor});
            $this->assertSame([], $this->visibleIds('people'), $actor);
            $this->endSession();
        }

        $this->actAs($this->endedAdmin);
        $this->assertSame([$this->personOnlyA], $this->visibleIds('people'));
    }

    public function testHoldersOfPeopleManageStillReadWhatTheyCreateAndEdit(): void
    {
        foreach (['adminA', 'dataEntryA'] as $actor) {
            $this->actAs($this->{$actor});
            $id = (int)$this->appDb()->execute(
                "SELECT person_create(?, 'Created', NULL, NULL, NULL)",
                [$this->devA],
            )->fetch()[0];
            $this->assertContains($id, $this->visibleIds('people'), $actor);
            $this->assertAllowed('UPDATE people SET phone = ? WHERE id = ?', ['1', $id]);
            $this->endSession();
        }
    }

    // person_developments -----------------------------------------------------------------------------------------

    /**
     * @param int $personId Person id.
     * @return list<int> The developments linked to a person, as the session sees them.
     */
    private function linksOf(int $personId): array
    {
        return $this->ids('SELECT development_id FROM person_developments WHERE person_id = ?', [$personId]);
    }

    #[DataProvider('readersOfA')]
    public function testHoldersOfPeopleViewReadTheLinksOfTheirDevelopmentOnly(string $actor): void
    {
        $this->actAs($this->{$actor});

        $this->assertSame([$this->devA], $this->linksOf($this->personShared));
        $this->assertSame([], $this->linksOf($this->personB));
        $this->assertSame([$this->devA], $this->linksOf($this->personAC), 'C stays hidden');
    }

    public function testAResidentCannotEnumerateTheLinksOfOtherPeople(): void
    {
        $this->actAs($this->resident);

        $links = $this->appDb()->execute('SELECT person_id, development_id FROM person_developments')->fetchAll('assoc');
        $this->assertSame(
            [['person_id' => $this->personResident, 'development_id' => $this->devA]],
            array_map(static fn(array $l): array => array_map('intval', $l), $links),
        );
    }

    public function testAGuardReadsTheirOwnLinksOnly(): void
    {
        $this->actAs($this->guardA);

        $this->assertSame([$this->devA], $this->linksOf($this->personGuard));
        $this->assertSame([], $this->linksOf($this->personResident));
        $this->assertSame([], $this->linksOf($this->personShared));
    }

    public function testOwnLinksStayVisibleInEveryDevelopmentTheyReach(): void
    {
        $this->db()->execute('UPDATE users SET person_id = ? WHERE id = ?', [$this->personShared, $this->resident]);
        $this->actAs($this->resident);

        $this->assertSame([$this->devA, $this->devB], $this->linksOf($this->personShared));
    }

    public function testTheSuperAdministratorReadsEveryLink(): void
    {
        $this->actAs($this->superUser);

        $total = (int)$this->db()->execute('SELECT COUNT(*) FROM person_developments')->fetch()[0];
        $this->assertGreaterThan(0, $total);
        $this->assertSame(
            $total,
            (int)$this->appDb()->execute('SELECT COUNT(*) FROM person_developments')->fetch()[0],
        );
    }
}
