<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Authorization\DevelopmentScope;
use App\Test\Support\DatabaseTestCase;
use Cake\ORM\Table;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

/**
 * Defense in depth (W-E): `SectionsTable` and `UnitsTable` refuse a save into an archived development whoever calls,
 * the super-administrator included, even if the policy was skipped. The development row is read FOR UPDATE inside the
 * save transaction, so archiving between a policy check and the save cannot slip through.
 */
class ArchivedDevelopmentGuardTest extends DatabaseTestCase
{
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
     * @return array<string, array{string, string}> Table alias and the role that writes.
     */
    public static function cases(): array
    {
        $cases = [];
        foreach (['Sections', 'Units'] as $alias) {
            foreach (['super_administrator', 'administrator', 'data_entry'] as $role) {
                $cases["{$alias}: {$role}"] = [$alias, $role];
            }
        }

        return $cases;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function aliases(): array
    {
        return ['Sections' => ['Sections'], 'Units' => ['Units']];
    }

    private function table(string $alias): Table
    {
        return $this->getTableLocator()->get($alias);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $alias): array
    {
        return $alias === 'Sections' ? ['name' => 'Torre A'] : ['number' => '101', 'type' => 'apartment'];
    }

    private function seed(string $alias, int $development): int
    {
        return $alias === 'Sections'
            ? $this->seedSection($development, 'Torre A')
            : $this->seedUnit($development, null, '101');
    }

    private function field(string $alias): string
    {
        return $alias === 'Sections' ? 'name' : 'number';
    }

    private function actAsWriter(string $role, int $development): void
    {
        $user = $this->createUser('writer');
        $this->assignRole($user, $role, $role === 'super_administrator' ? null : $development);
        $this->actAs($user);
    }

    private function archive(int $development): void
    {
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$development]);
    }

    /**
     * @param int $development Development.
     * @return array<string, mixed> Save options.
     */
    private function saveIn(int $development): array
    {
        return ['developmentScope' => DevelopmentScope::only([$development]), 'developmentId' => $development];
    }

    #[DataProvider('cases')]
    public function testACreateInAnArchivedDevelopmentIsRefusedWithAFieldError(string $alias, string $role): void
    {
        $north = $this->createDevelopment('Norte');
        $this->archive($north);
        $this->actAsWriter($role, $north);
        $table = $this->table($alias);
        $entity = $table->newEntity($this->payload($alias));

        $this->assertFalse($table->save($entity, $this->saveIn($north)));
        $this->assertArrayHasKey('development_id', $entity->getErrors());
        $this->commitSession();
        $this->assertSame(0, (int)$this->db()->execute('SELECT COUNT(*) FROM ' . $table->getTable())->fetch()[0]);
    }

    #[DataProvider('cases')]
    public function testAnUpdateInAnArchivedDevelopmentIsRefusedWithAFieldError(string $alias, string $role): void
    {
        $north = $this->createDevelopment('Norte');
        $id = $this->seed($alias, $north);
        $this->archive($north);
        $this->actAsWriter($role, $north);
        $scope = DevelopmentScope::only([$north]);
        $table = $this->table($alias);
        $field = $this->field($alias);
        $entity = $table->patchEntity($table->get($id, developmentScope: $scope), [$field => 'Renamed']);

        $this->assertFalse($table->save($entity, ['developmentScope' => $scope]));
        $this->assertArrayHasKey('development_id', $entity->getErrors());
        $this->commitSession();
        $stored = $this->db()->execute("SELECT {$field} FROM " . $table->getTable())->fetch()[0];
        $this->assertNotSame('Renamed', $stored);
    }

    #[DataProvider('aliases')]
    public function testAnUnarchivedDevelopmentStillTakesWrites(string $alias): void
    {
        $north = $this->createDevelopment('Norte');
        $this->actAsWriter('administrator', $north);
        $table = $this->table($alias);

        $this->assertNotFalse($table->save($table->newEntity($this->payload($alias)), $this->saveIn($north)));
    }

    #[DataProvider('aliases')]
    public function testASaveFailsClosedWhenTheDevelopmentIsNotFoundUnderTheScope(string $alias): void
    {
        $root = $this->createUser('root');
        $this->assignRole($root, 'super_administrator', null);
        $this->actAs($root);
        $table = $this->table($alias);
        $entity = $table->newEntity($this->payload($alias));

        $this->assertFalse($table->save($entity, $this->saveIn(999999)));
        $this->assertArrayHasKey('development_id', $entity->getErrors());
    }

    #[DataProvider('aliases')]
    public function testTheGuardAlsoHoldsWhenRulesAreSkipped(string $alias): void
    {
        $north = $this->createDevelopment('Norte');
        $this->archive($north);
        $this->actAsWriter('super_administrator', $north);
        $table = $this->table($alias);

        $saved = $table->save($table->newEntity($this->payload($alias)), $this->saveIn($north) + ['checkRules' => false]);

        $this->assertFalse($saved);
    }

    #[DataProvider('aliases')]
    public function testTheDevelopmentRowIsLockedForTheLengthOfTheSave(string $alias): void
    {
        $north = $this->createDevelopment('Norte');
        $this->actAsWriter('super_administrator', $north);
        $table = $this->table($alias);
        $locked = null;
        $table->getEventManager()->on('Model.afterSave', function () use (&$locked, $north): void {
            // The owner connection cannot take the row lock while the save transaction holds it.
            try {
                $this->db()->execute('SELECT 1 FROM developments WHERE id = ? FOR UPDATE NOWAIT', [$north])->fetch();
                $locked = false;
            } catch (Throwable) {
                $locked = true;
            }
        });

        $table->save($table->newEntity($this->payload($alias)), $this->saveIn($north));

        $this->assertTrue($locked);
    }
}
