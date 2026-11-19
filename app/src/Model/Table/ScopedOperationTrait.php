<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Authorization\DevelopmentScope;
use App\Authorization\DevelopmentScopeStack;
use App\Database\ConstraintViolation;
use Cake\Datasource\EntityInterface;
use Throwable;

/**
 * For Table classes that use `DevelopmentScopedBehavior`: publishes the scope of a save or delete for the length of
 * the operation, so rules and nested saves run under it (AD4b), and always takes it back (W-A).
 *
 * A scope given to an operation that runs inside another one can only narrow the running scope, never widen it.
 *
 * A unique or foreign key violation raised by the database anyway (a race) becomes a field error, not a 500, only
 * when that is safe and correctly attributed: the save is the outermost scoped one, it saves no associated entities,
 * it runs in its own transaction (`atomic`), and either no transaction is open around it or savepoints are enabled so
 * the failed statement is rolled back on its own, and the violated column belongs to this table (a violation raised
 * by another table, for example in an afterSave hook, or one that names no column, such as an expression index, cannot
 * be attributed to a field). Anywhere else (saveMany, nested association saves, a caller-held
 * transaction without savepoints) the PostgreSQL transaction would be left aborted or the violation would be blamed on
 * the wrong entity, so the exception is rethrown untouched.
 */
trait ScopedOperationTrait
{
    /**
     * @param \Cake\Datasource\EntityInterface $entity Entity to save.
     * @param array<string, mixed> $options Save options; `developmentScope` and `developmentId` apply.
     * @return \Cake\Datasource\EntityInterface|false
     */
    public function save(EntityInterface $entity, array $options = []): EntityInterface|false
    {
        $convert = $this->canConvertViolations($entity, $options);
        try {
            $scope = $this->operationScope($options);

            return ScopedSaveDepth::run(
                fn() => DevelopmentScopeStack::run($scope, fn() => parent::save($entity, $options)),
            );
        } catch (Throwable $e) {
            $violation = $convert ? $this->violationOf($e) : null;
            if ($violation === null || !$this->getSchema()->hasColumn($violation->field())) {
                throw $e;
            }
            $message = $violation->rule === '_unique'
                ? 'This value is already in use.'
                : 'The referenced record does not exist.';
            $entity->setError($violation->field(), [$violation->rule => $message]);

            return false;
        }
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entity Entity to delete.
     * @param array<string, mixed> $options Delete options; `developmentScope` applies.
     * @return bool
     */
    public function delete(EntityInterface $entity, array $options = []): bool
    {
        return DevelopmentScopeStack::run($this->operationScope($options), fn() => parent::delete($entity, $options));
    }

    /**
     * The violation behind an error, with its columns. A session under row-level security gets no DETAIL line, so the
     * columns are read from the catalog by the constraint name, for a constraint of THIS table only (a violation
     * raised by another table finds no columns and stays unattributed). Safe because the caller only converts when the
     * failed statement was rolled back on its own.
     *
     * @param \Throwable $error Error thrown by the save.
     * @return \App\Database\ConstraintViolation|null
     */
    private function violationOf(Throwable $error): ?ConstraintViolation
    {
        $violation = ConstraintViolation::from($error);
        if ($violation === null || $violation->field() !== '_constraint' || $violation->constraint === null) {
            return $violation;
        }

        try {
            $columns = $this->getConnection()->execute(
                'SELECT a.attname FROM pg_constraint c
                 JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
                 WHERE c.conrelid = to_regclass(?) AND c.conname = ?
                 ORDER BY array_position(c.conkey, a.attnum)',
                [$this->getTable(), $violation->constraint],
            )->fetchAll('assoc') ?: [];
        } catch (Throwable) {
            return $violation;
        }

        return $violation->withColumns(array_map(fn(array $row): string => (string)$row['attname'], $columns));
    }

    /**
     * @param array<string, mixed> $options Operation options.
     * @return \App\Authorization\DevelopmentScope|null The scope to run under: the option, narrowed by the running one.
     */
    private function operationScope(array $options): ?DevelopmentScope
    {
        $scope = $options['developmentScope'] ?? null;
        $running = DevelopmentScopeStack::current();

        return $scope instanceof DevelopmentScope && $running !== null ? $running->intersect($scope) : $scope;
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entity Entity to save.
     * @param array<string, mixed> $options Save options.
     * @return bool Whether a constraint violation of this save may be turned into a field error.
     */
    private function canConvertViolations(EntityInterface $entity, array $options): bool
    {
        if (!ScopedSaveDepth::isOutermost() || ($options['atomic'] ?? true) === false) {
            return false;
        }

        $associated = $options['associated'] ?? true;
        if ($associated !== false && $associated !== []) {
            foreach ($this->associations() as $association) {
                if ($entity->isDirty($association->getProperty())) {
                    return false;
                }
            }
        }

        $connection = $this->getConnection();

        return !$connection->inTransaction() || $connection->isSavePointsEnabled();
    }
}
