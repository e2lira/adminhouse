<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database;

use App\Test\Support\DatabaseTestCase;
use Migrations\Migrations;

/**
 * AD6, AD13: the `permission_dev_ids`, `units.manage` and resident-revoke migrations undo exactly what they did.
 *
 * Rolls the three back on the test database and forwards again, comparing the role/permission rows. The forward run
 * sits in `finally`, so a failing assertion cannot leave later tests on a half-migrated schema. Not parallel-safe.
 */
class PermissionMigrationsRollbackTest extends DatabaseTestCase
{
    private const BEFORE_THE_THREE = 20261120000001;

    /**
     * @return list<string> `role:permission` for every `role_permissions` row, sorted.
     */
    private function matrix(): array
    {
        $rows = $this->db()->execute(
            "SELECT r.code || ':' || p.code
             FROM role_permissions rp
             JOIN roles r ON r.id = rp.role_id
             JOIN permissions p ON p.id = rp.permission_id
             ORDER BY 1",
        )->fetchAll();

        return array_map(static fn(array $row): string => $row[0], $rows);
    }

    /**
     * @return list<string> Permission codes, sorted.
     */
    private function codes(): array
    {
        $rows = $this->db()->execute('SELECT code FROM permissions ORDER BY code')->fetchAll();

        return array_map(static fn(array $row): string => $row[0], $rows);
    }

    private function functionExists(): bool
    {
        return (int)$this->db()->execute(
            "SELECT COUNT(*) FROM pg_proc WHERE proname = 'permission_dev_ids' AND pronamespace = 'public'::regnamespace",
        )->fetch()[0] === 1;
    }

    public function testRollingBackRestoresTheMatrixTheThreeMigrationsFoundAndForwardRestoresTheirs(): void
    {
        $migrations = new Migrations(['connection' => 'test_migrations']);
        $matrixAfterUp = $this->matrix();
        $codesAfterUp = $this->codes();

        // The control: the forward state is the one the other tests rely on.
        $this->assertTrue($this->functionExists());
        $this->assertContains('administrator:units.manage', $matrixAfterUp);
        $this->assertContains('data_entry:units.manage', $matrixAfterUp);
        $this->assertNotContains('resident:units.view', $matrixAfterUp);

        $expectedAfterDown = array_values(array_filter(
            $matrixAfterUp,
            static fn(string $pair): bool => !str_ends_with($pair, ':units.manage'),
        ));
        $expectedAfterDown[] = 'resident:units.view';
        sort($expectedAfterDown);

        try {
            $this->assertTrue($migrations->rollback(['target' => self::BEFORE_THE_THREE]));

            $this->assertFalse($this->functionExists());
            // Only the rows the migrations own changed: units.manage went away, one resident row came back.
            $this->assertSame($expectedAfterDown, $this->matrix());
            $this->assertSame(
                array_values(array_diff($codesAfterUp, ['units.manage'])),
                $this->codes(),
            );
        } finally {
            $this->assertTrue($migrations->migrate());
        }

        $this->assertTrue($this->functionExists());
        $this->assertSame($matrixAfterUp, $this->matrix());
        $this->assertSame($codesAfterUp, $this->codes());
    }
}
