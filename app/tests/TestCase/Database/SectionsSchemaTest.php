<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database;

use App\Test\Support\DatabaseTestCase;

/**
 * Database-level guarantees of `sections` (spec units: Sections; tasks 7.1 and 7.3).
 *
 * Fixtures and assertions use the owner login: these are table constraints, not access rules.
 */
class SectionsSchemaTest extends DatabaseTestCase
{
    private const NOT_NULL = '23502';

    private const FOREIGN_KEY = '23503';

    private const UNIQUE = '23505';

    private const CHECK = '23514';

    private function insertSection(int $developmentId, string $name): ?string
    {
        return $this->ownerSqlState(
            'INSERT INTO sections (development_id, name, created, modified) VALUES (?, ?, NOW(), NOW())',
            [$developmentId, $name],
        );
    }

    /**
     * @param string $table Table name.
     * @return list<string> One entry per unique constraint, its columns joined by a comma in key order.
     */
    private function uniqueKeys(string $table): array
    {
        $rows = $this->db()->execute(
            "SELECT string_agg(a.attname, ',' ORDER BY k.ord)
             FROM pg_constraint c
             CROSS JOIN LATERAL unnest(c.conkey) WITH ORDINALITY AS k(attnum, ord)
             JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = k.attnum
             WHERE c.conrelid = ?::regclass AND c.contype = 'u'
             GROUP BY c.oid
             ORDER BY 1",
            [$table],
        )->fetchAll();

        return array_map(static fn(array $row): string => $row[0], $rows);
    }

    public function testASectionBelongsToExactlyOneDevelopment(): void
    {
        $dev = $this->createDevelopment('A');

        $this->assertNull($this->insertSection($dev, 'Tower 1'));
        $this->assertSame(self::FOREIGN_KEY, $this->insertSection($dev + 1000, 'Tower 1'));
        $this->assertSame(
            self::NOT_NULL,
            $this->ownerSqlState("INSERT INTO sections (development_id, name, created, modified) VALUES (NULL, 'x', NOW(), NOW())"),
        );
    }

    public function testASectionNameCannotBeBlank(): void
    {
        $dev = $this->createDevelopment('A');

        foreach (['', '   ', "\t"] as $name) {
            $this->assertSame(self::CHECK, $this->insertSection($dev, $name), json_encode($name));
        }
        $this->assertNull($this->insertSection($dev, 'Tower 1'));
    }

    public function testTheNameIsRequiredAndBoundedTo150Characters(): void
    {
        $dev = $this->createDevelopment('A');

        $this->assertSame(
            self::NOT_NULL,
            $this->ownerSqlState(
                'INSERT INTO sections (development_id, name, created, modified) VALUES (?, NULL, NOW(), NOW())',
                [$dev],
            ),
        );
        $this->assertNull($this->insertSection($dev, str_repeat('n', 150)));
        $this->assertSame(self::CHECK, $this->insertSection($dev, str_repeat('m', 151)));
        // Characters, not bytes: 150 accented letters fit.
        $this->assertNull($this->insertSection($dev, str_repeat('á', 150)));
    }

    public function testSectionNamesAreUniquePerDevelopmentOnly(): void
    {
        $a = $this->createDevelopment('A');
        $b = $this->createDevelopment('B');

        $this->assertNull($this->insertSection($a, 'Tower 1'));
        $this->assertSame(self::UNIQUE, $this->insertSection($a, 'Tower 1'));
        $this->assertNull($this->insertSection($a, 'Tower 2'));
        $this->assertNull($this->insertSection($b, 'Tower 1'));
    }

    public function testTheDevelopmentAndIdPairIsUniqueSoUnitsCanReferenceItWithACompositeKey(): void
    {
        $this->assertSame(['development_id,id', 'development_id,name'], $this->uniqueKeys('sections'));
    }

    public function testTheDatabaseGeneratesTheSectionId(): void
    {
        $dev = $this->createDevelopment('A');

        $this->assertSame('YES', $this->db()->execute(
            "SELECT is_identity FROM information_schema.columns WHERE table_name = 'sections' AND column_name = 'id'",
        )->fetch()[0]);
        $this->assertNull($this->insertSection($dev, 'Tower 1'));
        $this->assertSame(1, (int)$this->db()->execute('SELECT id FROM sections')->fetch()[0]);
    }

    public function testASectionNeverMovesToAnotherDevelopment(): void
    {
        $a = $this->createDevelopment('A');
        $b = $this->createDevelopment('B');
        $this->insertSection($a, 'Tower 1');

        $this->assertSame(
            self::CHECK,
            $this->ownerSqlState('UPDATE sections SET development_id = ? WHERE name = ?', [$b, 'Tower 1']),
        );
        $this->assertNull($this->ownerSqlState("UPDATE sections SET name = 'Tower 9', modified = NOW() WHERE name = 'Tower 1'"));
        $this->assertSame($a, (int)$this->db()->execute('SELECT development_id FROM sections')->fetch()[0]);
    }
}
