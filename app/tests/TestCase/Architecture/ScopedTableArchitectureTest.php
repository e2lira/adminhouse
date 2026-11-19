<?php
declare(strict_types=1);

namespace App\Test\TestCase\Architecture;

use App\Model\Table\RoleAssignmentsTable;
use App\Test\Support\Architecture\Fixtures\GuardedTable;
use App\Test\Support\Architecture\Fixtures\LooseLinkTable;
use App\Test\Support\Architecture\Fixtures\StrayTable;
use App\Test\Support\Architecture\Fixtures\UnguardedTable;
use App\Test\Support\Architecture\ScopedTableAudit;
use App\Test\Support\Scoped\HoldersTable;
use App\Test\Support\Scoped\ItemsTable;
use Cake\TestSuite\TestCase;

/**
 * Static guards that keep the development scope from being bypassed (AD2, AD4c): every scoped table refuses bulk
 * statements, every association that enters a scoped table is either safe by construction or listed with a reason,
 * and no raw SQL or manual join names a scoped table.
 *
 * The checks live in `ScopedTableAudit`; the fixtures prove each one fails on a violation.
 */
class ScopedTableArchitectureTest extends TestCase
{
    /**
     * Associations that enter a scoped table without being safe by construction. They are legal because runtime
     * refuses an unscoped query on a scoped table (MissingDevelopmentScopeException), so each caller must pass an
     * explicit `developmentScope`. The reason says why the association exists.
     *
     * `UsersTable hasMany RoleAssignments` needs no entry: RoleAssignments is not scoped (AD3). If that ever changes,
     * the association shows up here as unlisted and fails.
     *
     * @var array<string, string>
     */
    private const ASSOCIATION_WHITELIST = [
        'ItemsTable::ScopedLooseNotes' => 'Test support: plain foreign key to a scoped table, read with an explicit scope.',
        'ItemsTable::ScopedTags' => 'Test support: many-to-many, the development is never linked by a key.',
        'ItemsTable::ScopedRealms' => 'Test support: plain foreign key to a scoped table, read with an explicit scope.',
        'HoldersTable::ScopedItems' => 'Test support: unscoped table reaching a scoped one, read with an explicit scope.',
        'HoldersTable::ScopedNotes' => 'Test support: unscoped table reaching a scoped one, read with an explicit scope.',
        'ItemRealmsTable::ScopedItems' => 'Test support: unscoped junction table, read with an explicit scope.',
        'ItemRealmsTable::ScopedRealms' => 'Test support: unscoped junction table, read with an explicit scope.',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->getTableLocator()->clear();
    }

    protected function tearDown(): void
    {
        $this->getTableLocator()->clear();
        parent::tearDown();
    }

    /**
     * @return list<\Cake\ORM\Table> Every production and test-support table.
     */
    private function everyTable(): array
    {
        return ScopedTableAudit::load(ScopedTableAudit::discover(), $this->getTableLocator());
    }

    public function testDiscoveryFindsProductionAndTestSupportTables(): void
    {
        $classes = ScopedTableAudit::discover();

        $this->assertContains(RoleAssignmentsTable::class, $classes);
        $this->assertContains(ItemsTable::class, $classes);
        $this->assertContains(HoldersTable::class, $classes);
    }

    public function testEveryTableThatAddsTheBehaviorUsesTheBulkOperationsTrait(): void
    {
        $this->assertSame([], ScopedTableAudit::missingBulkGuard($this->everyTable()));
    }

    public function testTheBulkGuardCheckFailsOnATableWithoutTheTrait(): void
    {
        $tables = ScopedTableAudit::load([GuardedTable::class, UnguardedTable::class], $this->getTableLocator());

        $this->assertSame([UnguardedTable::class], ScopedTableAudit::missingBulkGuard($tables));
    }

    public function testEveryAssociationEnteringAScopedTableIsSafeOrListedWithAReason(): void
    {
        $this->assertSame(
            [],
            ScopedTableAudit::associationProblems($this->everyTable(), self::ASSOCIATION_WHITELIST),
        );
    }

    public function testTheWhitelistCarriesReasonsAndNoStaleEntries(): void
    {
        foreach (self::ASSOCIATION_WHITELIST as $entry => $reason) {
            $this->assertNotSame('', trim($reason), $entry . ' needs a reason.');
        }
        $this->assertSame(
            [],
            ScopedTableAudit::staleWhitelistEntries($this->everyTable(), self::ASSOCIATION_WHITELIST),
        );
    }

    public function testRoleAssignmentsIsNotScopedSoTheUsersAssociationNeedsNoEntry(): void
    {
        $roleAssignments = ScopedTableAudit::load([RoleAssignmentsTable::class], $this->getTableLocator())[0];

        $this->assertFalse(ScopedTableAudit::isScoped($roleAssignments));
    }

    public function testTheAssociationCheckFailsOnAnUnlistedEntryIntoAScopedTable(): void
    {
        $tables = ScopedTableAudit::load(
            [GuardedTable::class, StrayTable::class, LooseLinkTable::class],
            $this->getTableLocator(),
        );

        $problems = ScopedTableAudit::associationProblems($tables, []);

        $this->assertCount(2, $problems);
        $this->assertStringContainsString('StrayTable::Guarded', $problems[0] . $problems[1]);
        $this->assertStringContainsString('LooseLinkTable::Plain', $problems[0] . $problems[1]);
        // The composite key on the development column is safe by construction, so it is not reported.
        $this->assertStringNotContainsString('LooseLinkTable::Composite', $problems[0] . $problems[1]);
    }

    public function testAListedEntryIsAcceptedAndAStaleOneIsReported(): void
    {
        $tables = ScopedTableAudit::load(
            [GuardedTable::class, StrayTable::class, LooseLinkTable::class],
            $this->getTableLocator(),
        );
        $whitelist = [
            'StrayTable::Guarded' => 'Fixture.',
            'LooseLinkTable::Plain' => 'Fixture.',
            'LooseLinkTable::Gone' => 'Fixture.',
        ];

        $this->assertSame([], ScopedTableAudit::associationProblems($tables, $whitelist));
        $this->assertSame(['LooseLinkTable::Gone'], ScopedTableAudit::staleWhitelistEntries($tables, $whitelist));
    }

    public function testNoSourceUnderSrcNamesAScopedTableInRawSqlOrAManualJoin(): void
    {
        $names = ScopedTableAudit::scopedTableNames($this->everyProductionTable());

        $this->assertSame([], ScopedTableAudit::rawSqlOffenders($names));
    }

    public function testTheRawSqlScannerDetectsViolations(): void
    {
        $violations = [
            "<?php \$c->execute('SELECT 1 FROM units WHERE id = 1');",
            "<?php \$c->execute('select * from public.units u');",
            "<?php \$q = 'UPDATE \"units\" SET area = 1';",
            "<?php \$q->join(['u' => 'units']);",
            "<?php \$q->leftJoin(['table' => 'units', 'alias' => 'u', 'conditions' => []]);",
            "<?php \$this->getConnection()->selectQuery()->from('units');",
            "<?php \$sql = <<<SQL\nDELETE FROM units WHERE id = 1\nSQL;",
        ];
        foreach ($violations as $source) {
            $this->assertNotSame([], ScopedTableAudit::rawSqlHits($source, ['units']), $source);
        }
    }

    public function testTheRawSqlScannerIgnoresCommentsAndOtherTables(): void
    {
        $clean = [
            "<?php // select * from units\n\$a = 1;",
            "<?php /** Update units when the section changes. */\n\$a = 1;",
            "<?php \$c->execute('SELECT 1 FROM unit_payer_changes');",
            "<?php \$c->execute('SELECT 1 FROM developments');",
            "<?php \$q->join(['u' => 'people']);",
            '<?php $units = $this->Units->find();',
        ];
        foreach ($clean as $source) {
            $this->assertSame([], ScopedTableAudit::rawSqlHits($source, ['units']), $source);
        }
    }

    /**
     * @return list<\Cake\ORM\Table> Production tables only.
     */
    private function everyProductionTable(): array
    {
        return ScopedTableAudit::load(ScopedTableAudit::discover(production: true, support: false), $this->getTableLocator());
    }
}
