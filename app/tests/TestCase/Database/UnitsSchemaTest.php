<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database;

use App\Test\Support\DatabaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Database-level guarantees of `units` (spec units: Unit identity, Mixed unit types, Measurements, Active flag;
 * tasks 7.2 and 7.3). Fixtures and assertions use the owner login: these are table constraints, not access rules.
 */
class UnitsSchemaTest extends DatabaseTestCase
{
    private const NOT_NULL = '23502';

    private const FOREIGN_KEY = '23503';

    private const UNIQUE = '23505';

    private const CHECK = '23514';

    private int $devA;

    private int $devB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->devA = $this->createDevelopment('A');
        $this->devB = $this->createDevelopment('B');
    }

    private function createSection(int $developmentId, string $name): int
    {
        return (int)$this->db()->execute(
            'INSERT INTO sections (development_id, name, created, modified) VALUES (?, ?, NOW(), NOW()) RETURNING id',
            [$developmentId, $name],
        )->fetch()[0];
    }

    /**
     * @param int $developmentId Development.
     * @param int|null $sectionId Section, or null for a unit with none.
     * @param string $number Unit number.
     * @param string $type Unit type.
     * @param string|null $area Lot area.
     * @param string|null $indiviso Indiviso percentage.
     * @return string|null SQLSTATE of the error, or null when the row was stored.
     */
    private function insertUnit(
        int $developmentId,
        ?int $sectionId,
        string $number,
        string $type = 'house',
        ?string $area = null,
        ?string $indiviso = null,
    ): ?string {
        return $this->ownerSqlState(
            'INSERT INTO units (development_id, section_id, number, type, lot_area_m2, indiviso_pct, created, modified)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [$developmentId, $sectionId, $number, $type, $area, $indiviso],
        );
    }

    public function testAUnitNeedsADevelopmentANumberAndAKnownType(): void
    {
        $this->assertNull($this->insertUnit($this->devA, null, '1'));
        $this->assertSame(self::FOREIGN_KEY, $this->insertUnit($this->devA + 1000, null, '1'));
        $this->assertSame(
            self::NOT_NULL,
            $this->ownerSqlState("INSERT INTO units (development_id, number, type, created, modified) VALUES (NULL, '2', 'house', NOW(), NOW())"),
        );
        $this->assertSame(
            self::NOT_NULL,
            $this->ownerSqlState('INSERT INTO units (development_id, number, created, modified) VALUES (?, \'3\', NOW(), NOW())', [$this->devA]),
        );
        $this->assertSame(self::CHECK, $this->insertUnit($this->devA, null, '4', 'castle'));
    }

    public function testTheUnitNumberCannotBeBlank(): void
    {
        foreach (['', '   ', "\t", "\n"] as $number) {
            $this->assertSame(self::CHECK, $this->insertUnit($this->devA, null, $number), json_encode($number));
        }
    }

    public function testTheNumberIsRequiredAndBoundedTo50Characters(): void
    {
        $this->assertSame(
            self::NOT_NULL,
            $this->ownerSqlState(
                "INSERT INTO units (development_id, number, type, created, modified) VALUES (?, NULL, 'house', NOW(), NOW())",
                [$this->devA],
            ),
        );
        $this->assertNull($this->insertUnit($this->devA, null, str_repeat('9', 50)));
        $this->assertSame(self::CHECK, $this->insertUnit($this->devA, null, str_repeat('9', 51)));
        // Characters, not bytes: 50 accented letters fit.
        $this->assertNull($this->insertUnit($this->devA, null, str_repeat('á', 50)));
    }

    public function testAHouseAnApartmentAndALotLiveInTheSameDevelopment(): void
    {
        $this->assertNull($this->insertUnit($this->devA, null, '1', 'house'));
        $this->assertNull($this->insertUnit($this->devA, null, '2', 'apartment'));
        $this->assertNull($this->insertUnit($this->devA, null, '3', 'lot'));

        $types = $this->db()->execute('SELECT type FROM units WHERE development_id = ? ORDER BY number', [$this->devA])->fetchAll();
        $this->assertSame([['house'], ['apartment'], ['lot']], $types);
    }

    /**
     * @return array<string, array{string|null, string|null, string|null}>
     */
    public static function measurements(): array
    {
        return [
            'both absent' => [null, null, null],
            'area only' => ['120.50', null, null],
            'indiviso only' => [null, '2.5000', null],
            'indiviso exactly 100' => [null, '100', null],
            'tiny positive indiviso' => [null, '0.0001', null],
            'NaN area' => ['NaN', null, self::CHECK],
            'NaN indiviso' => [null, 'NaN', self::CHECK],
            'zero area' => ['0', null, self::CHECK],
            'negative area' => ['-1', null, self::CHECK],
            'area that rounds to zero' => ['0.004', null, self::CHECK],
            'zero indiviso' => [null, '0', self::CHECK],
            'negative indiviso' => [null, '-5', self::CHECK],
            'indiviso 120' => [null, '120', self::CHECK],
            'indiviso just above 100' => [null, '100.0001', self::CHECK],
        ];
    }

    /**
     * @param string|null $area Lot area.
     * @param string|null $indiviso Indiviso percentage.
     * @param string|null $expected SQLSTATE, or null when accepted.
     * @return void
     */
    #[DataProvider('measurements')]
    public function testMeasurementsAreOptionalPositiveAndIndivisoIsAtMostOneHundred(
        ?string $area,
        ?string $indiviso,
        ?string $expected,
    ): void {
        $this->assertSame($expected, $this->insertUnit($this->devA, null, '1', 'lot', $area, $indiviso));
    }

    public function testAUnitIsActiveByDefaultAndCanBeDeactivatedWithoutLosingTheRow(): void
    {
        $this->insertUnit($this->devA, null, '1');

        $this->assertTrue((bool)$this->db()->execute('SELECT is_active FROM units')->fetch()[0]);
        $this->db()->execute('UPDATE units SET is_active = false, modified = NOW()');
        $this->assertSame([[false]], $this->db()->execute('SELECT is_active FROM units')->fetchAll());
        $this->assertSame(
            self::NOT_NULL,
            $this->ownerSqlState('UPDATE units SET is_active = NULL'),
        );
    }

    public function testTheSectionOfAUnitMustBelongToTheSameDevelopment(): void
    {
        $towerA = $this->createSection($this->devA, 'Tower 1');
        $towerB = $this->createSection($this->devB, 'Tower 1');

        $this->assertNull($this->insertUnit($this->devA, $towerA, '12'));
        // A section of B and a section that does not exist fail the same way.
        $this->assertSame(self::FOREIGN_KEY, $this->insertUnit($this->devA, $towerB, '13'));
        $this->assertSame(self::FOREIGN_KEY, $this->insertUnit($this->devA, $towerB + 1000, '13'));
        $this->assertNull($this->insertUnit($this->devB, $towerB, '12'));
        $this->assertSame(self::FOREIGN_KEY, $this->insertUnit($this->devB, $towerA, '14'));
    }

    public function testAnExistingUnitCannotBeMovedToASectionOfAnotherDevelopment(): void
    {
        $towerA = $this->createSection($this->devA, 'Tower 1');
        $towerB = $this->createSection($this->devB, 'Tower 1');
        $this->insertUnit($this->devA, $towerA, '12');

        $this->assertSame(
            self::FOREIGN_KEY,
            $this->ownerSqlState('UPDATE units SET section_id = ?', [$towerB]),
        );
        $this->assertSame($towerA, (int)$this->db()->execute('SELECT section_id FROM units')->fetch()[0]);
    }

    public function testASectionWithUnitsCannotBeDeleted(): void
    {
        $tower = $this->createSection($this->devA, 'Tower 1');
        $this->insertUnit($this->devA, $tower, '12');

        $this->assertSame(self::FOREIGN_KEY, $this->ownerSqlState('DELETE FROM sections'));
    }

    public function testTheNumberIsUniqueWithinASectionAndAMissingSectionCountsAsOne(): void
    {
        $tower1 = $this->createSection($this->devA, 'Tower 1');
        $tower2 = $this->createSection($this->devA, 'Tower 2');
        $towerB = $this->createSection($this->devB, 'Tower 1');

        $this->assertNull($this->insertUnit($this->devA, null, '12'));
        $this->assertSame(self::UNIQUE, $this->insertUnit($this->devA, null, '12'));

        $this->assertNull($this->insertUnit($this->devA, $tower1, '12'));
        $this->assertSame(self::UNIQUE, $this->insertUnit($this->devA, $tower1, '12'));

        // The same number in another section, in another development, or with no section is accepted.
        $this->assertNull($this->insertUnit($this->devA, $tower2, '12'));
        $this->assertNull($this->insertUnit($this->devB, $towerB, '12'));
        $this->assertNull($this->insertUnit($this->devB, null, '12'));
    }

    public function testTheKeyOfAUnitIsUniqueWithItsDevelopmentSoLaterTablesCanReferenceIt(): void
    {
        $keys = array_map(
            static fn(array $row): string => $row[0],
            $this->db()->execute(
                "SELECT string_agg(a.attname, ',' ORDER BY k.ord)
                 FROM pg_constraint c
                 CROSS JOIN LATERAL unnest(c.conkey) WITH ORDINALITY AS k(attnum, ord)
                 JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = k.attnum
                 WHERE c.conrelid = 'units'::regclass AND c.contype = 'u'
                 GROUP BY c.oid ORDER BY 1",
            )->fetchAll(),
        );

        $this->assertSame(['development_id,id', 'development_id,section_id,number'], $keys);
        $this->assertSame('YES', $this->db()->execute(
            "SELECT is_identity FROM information_schema.columns WHERE table_name = 'units' AND column_name = 'id'",
        )->fetch()[0]);
    }

    public function testAUnitNeverMovesToAnotherDevelopment(): void
    {
        $this->insertUnit($this->devA, null, '12');

        $this->assertSame(
            self::CHECK,
            $this->ownerSqlState('UPDATE units SET development_id = ?', [$this->devB]),
        );
        $this->assertNull($this->ownerSqlState("UPDATE units SET number = '13', modified = NOW()"));
        $this->assertSame($this->devA, (int)$this->db()->execute('SELECT development_id FROM units')->fetch()[0]);
    }
}
