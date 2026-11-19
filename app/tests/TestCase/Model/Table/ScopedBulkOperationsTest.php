<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Authorization\DevelopmentScope;
use App\Test\Support\ScopedTableTestCase;
use Closure;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;

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
