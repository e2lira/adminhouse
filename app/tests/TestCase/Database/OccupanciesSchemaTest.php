<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database;

use App\Test\Support\DatabaseTestCase;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

/**
 * Database-level guarantees of `occupancies` (spec occupancies: Occupancy records, Person linked to the development,
 * No overlapping occupancies of one person, One primary owner and one primary tenant at a time, History is kept;
 * design AD9, AD12, W-D; tasks 12.4 and 12.5). Fixtures and assertions use the owner login: these are table
 * constraints, not access rules.
 *
 * A period is the half-open range `[starts_on, ends_on)`: the end day itself is free, the start day is taken.
 */
class OccupanciesSchemaTest extends DatabaseTestCase
{
    private const NOT_NULL = '23502';

    private const FOREIGN_KEY = '23503';

    private const EXCLUSION = '23P01';

    private const CHECK = '23514';

    private const OVERLAP = 'occupancies_no_overlap_per_person';

    private const ONE_PRIMARY = 'occupancies_one_primary_per_role';

    private int $devA;

    private int $devB;

    private int $unitA;

    private int $unitA2;

    private int $unitB;

    private int $ana;

    private int $beto;

    private int $carla;

    private int $onlyB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->devA = $this->createDevelopment('A');
        $this->devB = $this->createDevelopment('B');
        $this->unitA = $this->seedUnit($this->devA, null, '101');
        $this->unitA2 = $this->seedUnit($this->devA, null, '102');
        $this->unitB = $this->seedUnit($this->devB, null, '201');
        $this->ana = $this->createPerson('Ana', [$this->devA]);
        $this->beto = $this->createPerson('Beto', [$this->devA]);
        $this->carla = $this->createPerson('Carla', [$this->devA, $this->devB]);
        $this->onlyB = $this->createPerson('Only B', [$this->devB]);
    }

    /**
     * Run a statement as the owner and report the SQLSTATE and the constraint it names.
     *
     * @param string $sql Statement.
     * @param list<mixed> $params Parameters.
     * @return array{string|null, string|null} SQLSTATE and constraint name, null when the statement succeeded.
     */
    private function attempt(string $sql, array $params = []): array
    {
        try {
            $this->db()->execute($sql, $params)->fetchAll();

            return [null, null];
        } catch (Throwable $e) {
            for (; $e !== null; $e = $e->getPrevious()) {
                if ($e instanceof PDOException && isset($e->errorInfo[0])) {
                    $named = preg_match('/occupancies_[a-z_]+|person_developments_[a-z_]+/', (string)$e->errorInfo[2], $match) === 1;

                    return [(string)$e->errorInfo[0], $named ? $match[0] : null];
                }
            }

            return ['unknown', null];
        }
    }

    /**
     * @param int|null $developmentId Development.
     * @param int|null $unitId Unit.
     * @param int|null $personId Person.
     * @param string|null $role Role.
     * @param bool $primary Primary flag.
     * @param string|null $starts First day.
     * @param string|null $ends Day the period ends (exclusive), or null for open-ended.
     * @return array{string|null, string|null} SQLSTATE and constraint, null when stored.
     */
    private function insert(
        ?int $developmentId,
        ?int $unitId,
        ?int $personId,
        ?string $role,
        bool $primary,
        ?string $starts,
        ?string $ends = null,
    ): array {
        return $this->attempt(
            'INSERT INTO occupancies (development_id, unit_id, person_id, role, is_primary, starts_on, ends_on, created, modified)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [$developmentId, $unitId, $personId, $role, $primary ? 't' : 'f', $starts, $ends],
        );
    }

    private function own(
        int $unitId,
        int $personId,
        string $role,
        bool $primary,
        string $starts,
        ?string $ends = null,
    ): array {
        return $this->insert($this->devA, $unitId, $personId, $role, $primary, $starts, $ends);
    }

    private function id(string $personName, string $starts): int
    {
        return (int)$this->db()->execute(
            'SELECT o.id FROM occupancies o JOIN people p ON p.id = o.person_id WHERE p.name = ? AND o.starts_on = ?',
            [$personName, $starts],
        )->fetch()[0];
    }

    public function testTheTableHasExactlyTheDesignedColumns(): void
    {
        $rows = $this->db()->execute(
            "SELECT column_name, data_type, is_nullable FROM information_schema.columns
             WHERE table_schema = 'public' AND table_name = 'occupancies' ORDER BY ordinal_position",
        )->fetchAll();

        $this->assertSame([
            ['id', 'bigint', 'NO'],
            ['development_id', 'bigint', 'NO'],
            ['unit_id', 'bigint', 'NO'],
            ['person_id', 'bigint', 'NO'],
            ['role', 'text', 'NO'],
            ['is_primary', 'boolean', 'NO'],
            ['starts_on', 'date', 'NO'],
            ['ends_on', 'date', 'YES'],
            ['created', 'timestamp without time zone', 'NO'],
            ['modified', 'timestamp without time zone', 'NO'],
        ], $rows);
    }

    public function testAnOpenEndedOwnerOccupancyIsStored(): void
    {
        $this->assertSame([null, null], $this->own($this->unitA, $this->ana, 'owner', true, '2026-01-01'));

        $row = $this->db()->execute('SELECT ends_on, is_primary, role FROM occupancies')->fetch();
        $this->assertSame([null, true, 'owner'], [$row[0], (bool)$row[1], $row[2]]);
    }

    public function testEveryRequiredColumnRefusesNull(): void
    {
        $this->assertSame(self::NOT_NULL, $this->insert(null, $this->unitA, $this->ana, 'owner', true, '2026-01-01')[0]);
        $this->assertSame(self::NOT_NULL, $this->insert($this->devA, null, $this->ana, 'owner', true, '2026-01-01')[0]);
        // W-D: person_id is NOT NULL, so the exclusion constraints can never be skipped through a NULL.
        $this->assertSame(self::NOT_NULL, $this->insert($this->devA, $this->unitA, null, 'owner', true, '2026-01-01')[0]);
        $this->assertSame(self::NOT_NULL, $this->insert($this->devA, $this->unitA, $this->ana, null, true, '2026-01-01')[0]);
        $this->assertSame(self::NOT_NULL, $this->insert($this->devA, $this->unitA, $this->ana, 'owner', true, null)[0]);
        $this->assertSame(
            self::NOT_NULL,
            $this->attempt(
                "INSERT INTO occupancies (development_id, unit_id, person_id, role, is_primary, starts_on, created, modified)
                 VALUES (?, ?, ?, 'owner', NULL, '2026-01-01', NOW(), NOW())",
                [$this->devA, $this->unitA, $this->ana],
            )[0],
        );
    }

    public function testThePrimaryFlagDefaultsToFalse(): void
    {
        $this->db()->execute(
            "INSERT INTO occupancies (development_id, unit_id, person_id, role, starts_on, created, modified)
             VALUES (?, ?, ?, 'owner', '2026-01-01', NOW(), NOW())",
            [$this->devA, $this->unitA, $this->ana],
        );

        $this->assertFalse((bool)$this->db()->execute('SELECT is_primary FROM occupancies')->fetch()[0]);
    }

    public function testTheRoleIsOwnerOrTenant(): void
    {
        $this->assertSame([null, null], $this->own($this->unitA, $this->ana, 'owner', false, '2026-01-01'));
        $this->assertSame([null, null], $this->own($this->unitA, $this->ana, 'tenant', false, '2026-01-01'));
        foreach (['Owner', 'resident', '', 'owner '] as $role) {
            $this->assertSame(self::CHECK, $this->own($this->unitA2, $this->ana, $role, false, '2026-01-01')[0], $role);
        }
    }

    /**
     * @return array<string, array{string, string|null, bool}>
     */
    public static function periods(): array
    {
        return [
            'open ended' => ['2026-01-01', null, true],
            'one day later' => ['2026-01-01', '2026-01-02', true],
            'a year' => ['2026-01-01', '2027-01-01', true],
            'ends the day it starts' => ['2026-01-01', '2026-01-01', false],
            'ends before it starts' => ['2026-01-02', '2026-01-01', false],
            'ends a year before' => ['2026-01-01', '2025-01-01', false],
        ];
    }

    /**
     * @param string $starts First day.
     * @param string|null $ends End day.
     * @param bool $accepted Whether the database stores it.
     * @return void
     */
    #[DataProvider('periods')]
    public function testTheEndIsAfterTheStartOrAbsent(string $starts, ?string $ends, bool $accepted): void
    {
        $result = $this->own($this->unitA, $this->ana, 'owner', false, $starts, $ends);

        $this->assertSame($accepted ? null : self::CHECK, $result[0]);
    }

    public function testAUnitOfAnotherDevelopmentIsRefused(): void
    {
        $this->assertSame(self::FOREIGN_KEY, $this->insert($this->devA, $this->unitB, $this->carla, 'owner', true, '2026-01-01')[0]);
        $this->assertSame(self::FOREIGN_KEY, $this->insert($this->devA, $this->unitB + 1000, $this->ana, 'owner', true, '2026-01-01')[0]);
        $this->assertSame([null, null], $this->insert($this->devB, $this->unitB, $this->carla, 'owner', true, '2026-01-01'));
    }

    public function testTheOccupantMustBeLinkedToTheDevelopmentOfTheUnit(): void
    {
        $unlinked = $this->createPerson('Nobody', []);

        $this->assertSame(self::FOREIGN_KEY, $this->own($this->unitA, $unlinked, 'owner', true, '2026-01-01')[0]);
        $this->assertSame([null, null], $this->own($this->unitA, $this->carla, 'owner', true, '2026-01-01'));
    }

    public function testANonexistentPersonAndAPersonOfAnotherDevelopmentFailTheSameConstraint(): void
    {
        $nonexistent = $this->own($this->unitA, $this->onlyB + 1000, 'owner', true, '2026-01-01');
        $foreign = $this->own($this->unitA, $this->onlyB, 'owner', true, '2026-01-01');

        $this->assertSame(self::FOREIGN_KEY, $nonexistent[0]);
        $this->assertSame($nonexistent, $foreign);
        $this->assertSame('occupancies_person_development_fkey', $foreign[1]);
    }

    public function testThereIsNoPlainForeignKeyOnThePerson(): void
    {
        $rows = $this->db()->execute(
            "SELECT conname, confrelid::regclass::text, confdeltype, array_length(conkey, 1) AS columns,
                    pg_get_constraintdef(oid) AS definition
             FROM pg_constraint WHERE conrelid = 'occupancies'::regclass AND contype = 'f' ORDER BY conname",
        )->fetchAll('assoc');

        $byName = array_column($rows, null, 'conname');
        $this->assertSame(
            ['occupancies_development_id_fkey', 'occupancies_person_development_fkey', 'occupancies_unit_fkey'],
            array_keys($byName),
        );
        $this->assertSame(
            'FOREIGN KEY (person_id, development_id) REFERENCES person_developments(person_id, development_id) ON DELETE RESTRICT',
            $byName['occupancies_person_development_fkey']['definition'],
        );
        $this->assertSame(
            'FOREIGN KEY (development_id, unit_id) REFERENCES units(development_id, id)',
            $byName['occupancies_unit_fkey']['definition'],
        );
        // No constraint of one column references `people` or anything on `person_id` alone.
        foreach ($rows as $row) {
            if ($row['confrelid'] === 'people') {
                $this->fail('A plain foreign key to people exists: ' . $row['definition']);
            }
        }
    }

    public function testALinkInUseCannotBeRemovedAndAnUnusedOneCan(): void
    {
        $this->own($this->unitA, $this->ana, 'owner', true, '2026-01-01');

        [$state, $name] = $this->attempt('DELETE FROM person_developments WHERE person_id = ? AND development_id = ?', [$this->ana, $this->devA]);
        // ON DELETE RESTRICT reports restrict_violation, not foreign_key_violation (NO ACTION would).
        $this->assertSame('23001', $state);
        $this->assertSame('occupancies_person_development_fkey', $name);
        $this->assertSame([null, null], $this->attempt(
            'DELETE FROM person_developments WHERE person_id = ? AND development_id = ?',
            [$this->beto, $this->devA],
        ));
    }

    public function testTheDevelopmentForeignKeyHasNoCascade(): void
    {
        $definition = $this->db()->execute(
            "SELECT pg_get_constraintdef(oid) FROM pg_constraint
             WHERE conrelid = 'occupancies'::regclass AND conname = 'occupancies_development_id_fkey'",
        )->fetch()[0];

        $this->assertSame('FOREIGN KEY (development_id) REFERENCES developments(id)', $definition);
    }

    public function testAUnitOrADevelopmentWithOccupanciesCannotBeDeleted(): void
    {
        $this->own($this->unitA, $this->ana, 'owner', true, '2026-01-01');

        $unit = $this->attempt('DELETE FROM units WHERE id = ?', [$this->unitA]);
        $this->assertSame([self::FOREIGN_KEY, 'occupancies_unit_fkey'], $unit);
        // A development is also held by its units, which are checked too: the refusal is a foreign key either way.
        $development = $this->attempt('DELETE FROM developments WHERE id = ?', [$this->devA]);
        $this->assertSame(self::FOREIGN_KEY, $development[0]);
        $this->assertSame(1, (int)$this->db()->execute('SELECT COUNT(*) FROM occupancies')->fetch()[0]);
        $this->assertSame(1, (int)$this->db()->execute('SELECT COUNT(*) FROM units WHERE id = ?', [$this->unitA])->fetch()[0]);
        // Without occupancies the same unit is deletable: the refusal comes from the occupancy.
        $this->assertSame([null, null], $this->attempt('DELETE FROM units WHERE id = ?', [$this->unitA2]));
    }

    public function testSettingTheSameEndDateAgainIsAcceptedAndChangesNothing(): void
    {
        $this->own($this->unitA, $this->ana, 'tenant', true, '2026-01-01', '2026-09-01');
        $id = $this->id('Ana', '2026-01-01');
        $before = $this->db()->execute('SELECT * FROM occupancies WHERE id = ?', [$id])->fetch('assoc');

        // A repeated submit of the same end is idempotent (the trigger refuses only a different value).
        $this->assertSame([null, null], $this->attempt("UPDATE occupancies SET ends_on = '2026-09-01' WHERE id = ?", [$id]));
        $this->assertSame($before, $this->db()->execute('SELECT * FROM occupancies WHERE id = ?', [$id])->fetch('assoc'));
    }

    public function testNoBoundExistsOnTheEndBeyondItBeingAfterTheStart(): void
    {
        // Accepted by design (review S1): an end in the past or far ahead is a valid period, and a same-day row
        // (one day long) is corrected by the super-administrator, who deletes it.
        $this->assertSame([null, null], $this->own($this->unitA, $this->ana, 'owner', false, '1990-01-01', '1990-01-02'));
        $this->own($this->unitA, $this->beto, 'owner', false, '2026-01-01');
        $id = $this->id('Beto', '2026-01-01');
        $this->assertSame([null, null], $this->attempt("UPDATE occupancies SET ends_on = '2026-01-02' WHERE id = ?", [$id]));
    }

    public function testBothExclusionConstraintsAreExactlyTheDesignedOnes(): void
    {
        $rows = $this->db()->execute(
            "SELECT conname, pg_get_constraintdef(oid) FROM pg_constraint
             WHERE conrelid = 'occupancies'::regclass AND contype = 'x' ORDER BY conname",
        )->fetchAll();

        $this->assertSame([
            [
                self::OVERLAP,
                "EXCLUDE USING gist (development_id WITH =, unit_id WITH =, person_id WITH =, role WITH =, daterange(starts_on, ends_on, '[)'::text) WITH &&)",
            ],
            [
                self::ONE_PRIMARY,
                "EXCLUDE USING gist (development_id WITH =, unit_id WITH =, role WITH =, daterange(starts_on, ends_on, '[)'::text) WITH &&) WHERE (is_primary)",
            ],
        ], $rows);
    }

    public function testTheSamePersonCannotOverlapHerselfInTheSameRoleOnTheSameUnit(): void
    {
        $this->own($this->unitA, $this->ana, 'owner', false, '2026-01-01', '2026-06-01');

        $this->assertSame([self::EXCLUSION, self::OVERLAP], $this->own($this->unitA, $this->ana, 'owner', false, '2026-05-01'));
        // Whatever the primary flag of either row.
        $this->assertSame([self::EXCLUSION, self::OVERLAP], $this->own($this->unitA, $this->ana, 'owner', true, '2026-05-01'));
    }

    public function testAdjacentPeriodsOfTheSamePersonAreAccepted(): void
    {
        $this->own($this->unitA, $this->ana, 'tenant', false, '2026-01-01', '2026-06-01');

        // The end day is exclusive, so the next period may start on it.
        $this->assertSame([null, null], $this->own($this->unitA, $this->ana, 'tenant', false, '2026-06-01', '2026-09-01'));
        $this->assertSame([null, null], $this->own($this->unitA, $this->ana, 'tenant', false, '2026-09-01'));
        // And may end on the first day of the earlier one.
        $this->assertSame([null, null], $this->own($this->unitA, $this->ana, 'tenant', false, '2025-01-01', '2026-01-01'));
    }

    /**
     * @return array<string, array{string, string|null, bool}>
     */
    public static function boundaryDays(): array
    {
        // Existing period of the same person, role and unit: [2026-03-01, 2026-06-01).
        return [
            'ends the day before it starts' => ['2026-01-01', '2026-02-28', true],
            'ends the day it starts (exclusive)' => ['2026-01-01', '2026-03-01', true],
            'ends the day after it starts' => ['2026-01-01', '2026-03-02', false],
            'starts on its first day' => ['2026-03-01', '2026-03-02', false],
            'starts on its last day' => ['2026-05-31', '2026-06-01', false],
            'starts on its end day (exclusive)' => ['2026-06-01', '2026-06-02', true],
            'starts the day after its end' => ['2026-06-02', null, true],
            'inside' => ['2026-04-01', '2026-05-01', false],
            'around' => ['2026-01-01', '2026-12-01', false],
            'open-ended from before' => ['2026-01-01', null, false],
            'open-ended from its end day' => ['2026-06-01', null, true],
            'open-ended from its last day' => ['2026-05-31', null, false],
        ];
    }

    /**
     * @param string $starts First day of the new period.
     * @param string|null $ends End day of the new period.
     * @param bool $accepted Whether the database stores it.
     * @return void
     */
    #[DataProvider('boundaryDays')]
    public function testBoundaryDaysOfTheOverlapRule(string $starts, ?string $ends, bool $accepted): void
    {
        $this->own($this->unitA, $this->ana, 'owner', false, '2026-03-01', '2026-06-01');

        $result = $this->own($this->unitA, $this->ana, 'owner', false, $starts, $ends);

        $this->assertSame($accepted ? [null, null] : [self::EXCLUSION, self::OVERLAP], $result);
    }

    public function testAnOpenEndedPeriodOverlapsEveryLaterOne(): void
    {
        $this->own($this->unitA, $this->ana, 'owner', false, '2026-01-01');

        $this->assertSame([self::EXCLUSION, self::OVERLAP], $this->own($this->unitA, $this->ana, 'owner', false, '2090-01-01'));
        $this->assertSame([null, null], $this->own($this->unitA, $this->ana, 'owner', false, '2025-01-01', '2026-01-01'));
    }

    public function testTheOverlapRuleIsPerUnitPersonAndRole(): void
    {
        $this->own($this->unitA, $this->ana, 'owner', false, '2026-01-01');

        // Another unit, the same person, the same days.
        $this->assertSame([null, null], $this->own($this->unitA2, $this->ana, 'owner', false, '2026-01-01'));
        // The same unit and days, another role.
        $this->assertSame([null, null], $this->own($this->unitA, $this->ana, 'tenant', false, '2026-01-01'));
        // The same unit, role and days, another person (a co-owner).
        $this->assertSame([null, null], $this->own($this->unitA, $this->beto, 'owner', false, '2026-01-01'));
    }

    public function testAPrimaryOwnerCannotBeJoinedByASecondOverlappingPrimaryOwner(): void
    {
        $this->own($this->unitA, $this->ana, 'owner', true, '2026-01-01');

        $this->assertSame([self::EXCLUSION, self::ONE_PRIMARY], $this->own($this->unitA, $this->beto, 'owner', true, '2026-06-01'));
        $this->assertSame([self::EXCLUSION, self::ONE_PRIMARY], $this->own($this->unitA, $this->beto, 'owner', true, '2025-01-01', '2026-01-02'));
        // The same holds for tenants.
        $this->own($this->unitA, $this->ana, 'tenant', true, '2026-01-01', '2026-12-01');
        $this->assertSame([self::EXCLUSION, self::ONE_PRIMARY], $this->own($this->unitA, $this->beto, 'tenant', true, '2026-11-30'));
    }

    public function testPrimariesOfTheSameRoleMayFollowEachOther(): void
    {
        $this->own($this->unitA, $this->ana, 'owner', true, '2026-01-01', '2026-06-01');

        $this->assertSame([null, null], $this->own($this->unitA, $this->beto, 'owner', true, '2026-06-01'));
    }

    public function testACoOwnerAndARoommateAreNotPrimaryAndAreAccepted(): void
    {
        $this->own($this->unitA, $this->ana, 'owner', true, '2026-01-01');

        $this->assertSame([null, null], $this->own($this->unitA, $this->beto, 'owner', false, '2026-01-01'));
        $this->assertSame([null, null], $this->own($this->unitA, $this->carla, 'owner', false, '2026-03-01'));
        $this->own($this->unitA, $this->ana, 'tenant', true, '2026-01-01');
        $this->assertSame([null, null], $this->own($this->unitA, $this->beto, 'tenant', false, '2026-01-01'));
        $this->assertSame([null, null], $this->own($this->unitA, $this->carla, 'tenant', false, '2026-01-01'));
    }

    public function testANonPrimaryAndAPrimaryOfTheSameRoleMayCoexistWhenTheyAreDifferentPeople(): void
    {
        $this->own($this->unitA, $this->beto, 'owner', false, '2026-01-01');

        $this->assertSame([null, null], $this->own($this->unitA, $this->ana, 'owner', true, '2026-01-01'));
    }

    public function testAPrimaryOwnerAndAPrimaryTenantLiveTogether(): void
    {
        $this->own($this->unitA, $this->ana, 'owner', true, '2026-01-01');

        $this->assertSame([null, null], $this->own($this->unitA, $this->beto, 'tenant', true, '2026-01-01'));
    }

    public function testThePrimaryRuleIsPerUnit(): void
    {
        $this->own($this->unitA, $this->ana, 'owner', true, '2026-01-01');

        $this->assertSame([null, null], $this->own($this->unitA2, $this->beto, 'owner', true, '2026-01-01'));
    }

    public function testTheSamePersonMayHoldTheSameRoleOnTwoDevelopmentsUnits(): void
    {
        $this->own($this->unitA, $this->carla, 'owner', true, '2026-01-01');

        $this->assertSame([null, null], $this->insert($this->devB, $this->unitB, $this->carla, 'owner', true, '2026-01-01'));
    }

    public function testOnlyTheEndOfAnOpenOccupancyMayChangeOnce(): void
    {
        $this->own($this->unitA, $this->ana, 'tenant', true, '2026-01-01');
        $id = $this->id('Ana', '2026-01-01');

        // An update that changes nothing, or only `modified`, is not a change of the record.
        $this->assertSame([null, null], $this->attempt('UPDATE occupancies SET modified = NOW() WHERE id = ?', [$id]));
        // The end is set once, and must be after the start.
        $this->assertSame(self::CHECK, $this->attempt("UPDATE occupancies SET ends_on = '2026-01-01' WHERE id = ?", [$id])[0]);
        $this->assertSame([null, null], $this->attempt("UPDATE occupancies SET ends_on = '2026-09-01', modified = NOW() WHERE id = ?", [$id]));

        // It cannot be changed again, moved earlier or reopened: not even by the owner login.
        foreach (["'2026-10-01'", "'2026-08-01'", 'NULL'] as $value) {
            $this->assertSame(
                self::CHECK,
                $this->attempt("UPDATE occupancies SET ends_on = {$value} WHERE id = ?", [$id])[0],
                $value,
            );
        }
        $this->assertSame('2026-09-01', $this->db()->execute('SELECT ends_on::text FROM occupancies WHERE id = ?', [$id])->fetch()[0]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function immutableColumns(): array
    {
        return [
            'role' => ["role = 'owner'"],
            'is_primary' => ['is_primary = false'],
            'starts_on' => ["starts_on = '2026-01-02'"],
            'person_id' => ['person_id = {beto}'],
            'unit_id' => ['unit_id = {unit2}'],
            'id' => ['id = id + 1000'],
            'created' => ["created = NOW() + interval '1 day'"],
            'development_id' => ['development_id = {devB}'],
        ];
    }

    /**
     * @param string $assignment SET clause; `{beto}`, `{unit2}` and `{devB}` stand for fixture ids.
     * @return void
     */
    #[DataProvider('immutableColumns')]
    public function testNothingButTheEndAndTheModificationTimeEverChanges(string $assignment): void
    {
        $this->own($this->unitA, $this->ana, 'tenant', true, '2026-01-01');
        $before = $this->db()->execute('SELECT * FROM occupancies')->fetch('assoc');
        $set = strtr($assignment, ['{beto}' => $this->beto, '{unit2}' => $this->unitA2, '{devB}' => $this->devB]);

        [$state] = $this->attempt("UPDATE occupancies SET {$set} WHERE id = ?", [$before['id']]);

        $this->assertSame(self::CHECK, $state);
        $this->assertSame($before, $this->db()->execute('SELECT * FROM occupancies')->fetch('assoc'));
    }

    public function testTheOccupanciesMigrationChecksButNeverCreatesTheExtension(): void
    {
        $files = glob(dirname(__DIR__, 3) . '/config/Migrations/*CreateOccupancies.php') ?: [];
        $this->assertCount(1, $files);
        $source = (string)file_get_contents($files[0]);

        $this->assertStringContainsString('ExtensionGuard::assertInstalled', $source);
        $this->assertStringContainsString("'btree_gist'", $source);
        $this->assertStringNotContainsString('CREATE EXTENSION', $source);
    }
}
