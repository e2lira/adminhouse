<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model;

use App\Authorization\DevelopmentScope;
use App\Database\ConstraintViolation;
use App\Test\Support\Scoped\ScopedSchema;
use App\Test\Support\ScopedTableTestCase;
use Cake\Database\Connection;
use PDOException;
use ReflectionMethod;
use RuntimeException;

/**
 * Under row-level security the server sends no DETAIL line, so the field of a violation is found by looking the
 * constraint up in the catalog, for the table being saved only. These tests raise the error without a DETAIL line.
 */
class ConstraintViolationLookupTest extends ScopedTableTestCase
{
    private function uniqueKeyOn(string $table, string $column): string
    {
        return (string)$this->db()->execute(
            "SELECT c.conname FROM pg_constraint c
             JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
             WHERE c.conrelid = ?::regclass AND c.contype = 'u' AND a.attname = ?",
            [ScopedSchema::qualify($table), $column],
        )->fetch()[0];
    }

    private function raise(string $constraint): void
    {
        $driver = new PDOException('driver');
        $driver->errorInfo = ['23505', 7, 'ERROR: duplicate key value violates unique constraint "' . $constraint . '"'];
        $this->items->getEventManager()->on('Model.beforeSave', function () use ($driver): void {
            throw new RuntimeException('wrapped', 0, $driver);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return ['developmentScope' => DevelopmentScope::only([1]), 'developmentId' => 1];
    }

    public function testTheConstraintNameOfThisTableGivesTheField(): void
    {
        $this->raise($this->uniqueKeyOn('scoped_items', 'name'));
        $entity = $this->items->newEntity(['name' => 'x']);

        $this->assertFalse($this->items->save($entity, $this->options()));

        $this->assertArrayHasKey('_unique', $entity->getError('name'));
    }

    public function testAConstraintOfAnotherTableStaysUnattributedAndIsRethrown(): void
    {
        // `scoped_notes (development_id, body)` has a unique key too; it is not a key of the saved table.
        $this->raise($this->uniqueKeyOn('scoped_notes', 'body'));
        $entity = $this->items->newEntity(['name' => 'x']);

        try {
            $this->items->save($entity, $this->options());
            $this->fail('A violation of another table must reach the caller.');
        } catch (RuntimeException $e) {
            $this->assertSame('wrapped', $e->getMessage());
            $this->assertSame([], $entity->getErrors());
        }
    }

    public function testAnUnknownConstraintNameIsRethrown(): void
    {
        $this->raise('no_such_constraint');
        $entity = $this->items->newEntity(['name' => 'x']);

        try {
            $this->items->save($entity, $this->options());
            $this->fail('An unknown constraint must reach the caller.');
        } catch (RuntimeException) {
            $this->assertSame([], $entity->getErrors());
        }
    }

    public function testAFailedLookupLeavesTheViolationUnattributed(): void
    {
        $driver = new PDOException('driver');
        $driver->errorInfo = ['23505', 7, 'ERROR: duplicate key value violates unique constraint "k"'];
        $violation = ConstraintViolation::from($driver);
        $connection = $this->createStub(Connection::class);
        $connection->method('execute')->willThrowException(new RuntimeException('catalog unavailable'));
        $this->items->setConnection($connection);

        $result = (new ReflectionMethod($this->items, 'violationOf'))->invoke($this->items, $driver);

        $this->assertNotNull($violation);
        $this->assertSame('_constraint', $result->field());
        $this->assertSame('k', $result->constraint);
    }

    public function testAMessageWithoutAConstraintNameIsNotLookedUp(): void
    {
        $driver = new PDOException('driver');
        $driver->errorInfo = ['23505', 7, 'ERROR: duplicate key'];
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('execute');
        $this->items->setConnection($connection);

        $result = (new ReflectionMethod($this->items, 'violationOf'))->invoke($this->items, $driver);

        $this->assertSame('_constraint', $result->field());
        $this->assertNull($result->constraint);
    }
}
