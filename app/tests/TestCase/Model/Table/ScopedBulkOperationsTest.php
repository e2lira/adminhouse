<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Authorization\DevelopmentScope;
use App\Test\Support\ScopedTableTestCase;
use Cake\ORM\Table;
use Closure;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

/**
 * Bulk statements cannot be scoped by the behavior (no entity, no find event), so a scoped table refuses them (AD4c).
 * Single-row save() and delete() keep working: they are scoped by the behavior and by RLS.
 */
class ScopedBulkOperationsTest extends ScopedTableTestCase
{
    /**
     * @return array<string, array{0: \Closure(\Cake\ORM\Table): mixed}>
     */
    public static function bulkOperations(): array
    {
        return [
            'updateAll' => [fn($table) => $table->updateAll(['name' => 'x'], ['id >' => 0])],
            'deleteAll' => [fn($table) => $table->deleteAll(['id >' => 0])],
            'updateQuery' => [fn($table) => $table->updateQuery()],
            'deleteQuery' => [fn($table) => $table->deleteQuery()],
            'insertQuery' => [fn($table) => $table->insertQuery()],
        ];
    }

    /**
     * @param \Closure(\Cake\ORM\Table): mixed $operation Bulk operation to try.
     * @return void
     */
    #[DataProvider('bulkOperations')]
    public function testEveryBulkOperationOnAScopedTableThrowsAndNamesTheTable(Closure $operation): void
    {
        $this->insertItem(1, 'mine');

        try {
            $operation($this->items);
            $this->fail('A bulk operation on a scoped table must be refused.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('ScopedItems', $e->getMessage());
        }

        $this->assertSame(1, $this->countItems());
    }

    public function testSingleRowSaveAndDeleteStillWork(): void
    {
        $scope = ['developmentScope' => DevelopmentScope::only([1])];
        $item = $this->items->get($this->insertItem(1, 'mine'), developmentScope: DevelopmentScope::only([1]));

        $item->set('name', 'renamed');
        $this->assertNotFalse($this->items->save($item, $scope));
        $this->assertTrue($this->items->delete($item, $scope));
        $this->assertSame(0, $this->countItems());
    }

    public function testADependentCascadeWithoutCallbacksIsRefusedBecauseItBulkDeletes(): void
    {
        $itemId = $this->insertItem(1, 'mine');
        $this->insertNote(1, $itemId, 'n1');
        $item = $this->items->get($itemId, developmentScope: DevelopmentScope::only([1]));
        $this->items->getAssociation('ScopedNotes')->setDependent(true)->setCascadeCallbacks(false);

        try {
            $this->items->delete($item, ['developmentScope' => DevelopmentScope::only([1])]);
            $this->fail('A dependent cascade that bulk deletes must be refused.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('ScopedNotes', $e->getMessage());
        }
        $this->assertSame(1, $this->countItems());
        $this->assertSame(1, $this->countNotes());
    }

    public function testADependentCascadeWithCallbacksDeletesRowByRow(): void
    {
        $itemId = $this->insertItem(1, 'mine');
        $this->insertNote(1, $itemId, 'n1');
        $this->insertNote(1, $itemId, 'n2');
        $item = $this->items->get($itemId, developmentScope: DevelopmentScope::only([1]));
        $this->items->getAssociation('ScopedNotes')->setDependent(true)->setCascadeCallbacks(true);

        $this->assertTrue($this->items->delete($item, ['developmentScope' => DevelopmentScope::only([1])]));
        $this->assertSame(0, $this->countItems());
        $this->assertSame(0, $this->countNotes());
    }

    public function testTheOrmUpdateStatementCarriesTheDevelopmentCondition(): void
    {
        $mine = $this->items->get($this->insertItem(1, 'mine'), developmentScope: DevelopmentScope::only([1]));
        $theirsId = $this->insertItem(2, 'theirs');
        $mine->set('name', 'renamed');
        // After every guard has run, something redirects the statement to another development's row.
        $this->items->getEventManager()->on(
            'Model.beforeSave',
            ['priority' => 100],
            function ($event, $entity) use ($theirsId): void {
                $entity->set('id', $theirsId);
            },
        );

        $this->items->save($mine, ['developmentScope' => DevelopmentScope::only([1])]);

        $this->assertSame('theirs', $this->nameOf($theirsId));
    }

    public function testTheOrmDeleteStatementCarriesTheDevelopmentCondition(): void
    {
        $mine = $this->items->get($this->insertItem(1, 'mine'), developmentScope: DevelopmentScope::only([1]));
        $theirsId = $this->insertItem(2, 'theirs');
        $this->items->getEventManager()->on(
            'Model.beforeDelete',
            ['priority' => 100],
            function ($event, $entity) use ($theirsId): void {
                $entity->set('id', $theirsId);
            },
        );

        $deleted = $this->items->delete($mine, ['developmentScope' => DevelopmentScope::only([1])]);

        $this->assertFalse($deleted);
        $this->assertSame('theirs', $this->nameOf($theirsId));
    }

    public function testTheOrmStillDeclaresTheMethodsTheFrameCheckRelaysOn(): void
    {
        foreach (['_update', '_processDelete', '_insert'] as $method) {
            $this->assertSame(
                Table::class,
                (new ReflectionMethod(Table::class, $method))->getDeclaringClass()->getName(),
            );
        }
    }

    private function nameOf(int $id): ?string
    {
        $row = $this->appDb()->execute('SELECT name FROM scoped_items WHERE id = ?', [$id])->fetch();

        return $row === false ? null : $row[0];
    }

    private function insertNote(int $developmentId, int $itemId, string $body): void
    {
        $this->appDb()->execute(
            'INSERT INTO scoped_notes (development_id, item_id, body) VALUES (?, ?, ?)',
            [$developmentId, $itemId, $body],
        );
    }

    private function countItems(): int
    {
        return (int)$this->appDb()->execute('SELECT count(*) FROM scoped_items')->fetch()[0];
    }

    private function countNotes(): int
    {
        return (int)$this->appDb()->execute('SELECT count(*) FROM scoped_notes')->fetch()[0];
    }
}
