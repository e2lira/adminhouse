<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Authorization\DevelopmentScopeStack;
use App\Model\Behavior\DevelopmentScopedBehavior;
use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Query\DeleteQuery;
use Cake\ORM\Query\InsertQuery;
use Cake\ORM\Query\UpdateQuery;
use Cake\ORM\Table;
use Closure;
use LogicException;

/**
 * Required on every Table class that adds `DevelopmentScopedBehavior` (an architecture test enforces it).
 *
 * A bulk statement has no entity and fires no find event, so the behavior cannot put the development condition on it.
 * `updateAll()`, `deleteAll()`, `insertQuery()`, `updateQuery()` and `deleteQuery()` therefore throw on a scoped
 * table. Row-level security still bounds them in the database, but the application must not depend on it alone.
 *
 * The ORM itself builds the statement of a single-row `save()` and `delete()` through `insertQuery()`,
 * `updateQuery()` and `deleteQuery()`; only that direct internal call, made on this very table, is let through.
 * Those statements address one row by primary key, so the update and delete ones also get the development condition
 * of the running scope (skipped for an all-development scope, and for through-mode tables, which have no development
 * column): a primary key redirected after the guards ran still cannot reach another development.
 *
 * A dependent `hasMany`/`hasOne` whose `cascadeCallbacks` is false cascades with `deleteAll()` on the target, so it is
 * refused when the target is scoped. A scoped dependent association must set `cascadeCallbacks` to true, which deletes
 * the related rows one by one, each under the scope of the operation.
 */
trait ScopedBulkOperationsTrait
{
    /**
     * @param \Cake\Database\Expression\QueryExpression|\Closure|array|string $fields Fields to set.
     * @param \Cake\Database\Expression\QueryExpression|\Closure|array|string|null $conditions Conditions.
     * @return int
     * @throws \LogicException Always.
     */
    public function updateAll(
        QueryExpression|Closure|array|string $fields,
        QueryExpression|Closure|array|string|null $conditions,
    ): int {
        throw $this->bulkRefused(__FUNCTION__);
    }

    /**
     * @param \Cake\Database\Expression\QueryExpression|\Closure|array|string|null $conditions Conditions.
     * @return int
     * @throws \LogicException Always.
     */
    public function deleteAll(QueryExpression|Closure|array|string|null $conditions): int
    {
        throw $this->bulkRefused(__FUNCTION__);
    }

    /**
     * @return \Cake\ORM\Query\InsertQuery
     * @throws \LogicException Unless called by the ORM to insert one entity.
     */
    public function insertQuery(): InsertQuery
    {
        if (!$this->calledByTheOrm('_insert')) {
            throw $this->bulkRefused(__FUNCTION__);
        }

        return parent::insertQuery();
    }

    /**
     * @return \Cake\ORM\Query\UpdateQuery
     * @throws \LogicException Unless called by the ORM to save one entity.
     */
    public function updateQuery(): UpdateQuery
    {
        if (!$this->calledByTheOrm('_update')) {
            throw $this->bulkRefused(__FUNCTION__);
        }

        return $this->withScopeCondition(parent::updateQuery());
    }

    /**
     * @return \Cake\ORM\Query\DeleteQuery
     * @throws \LogicException Unless called by the ORM to delete one entity.
     */
    public function deleteQuery(): DeleteQuery
    {
        if (!$this->calledByTheOrm('_processDelete')) {
            throw $this->bulkRefused(__FUNCTION__);
        }

        return $this->withScopeCondition(parent::deleteQuery());
    }

    /**
     * @param \Cake\ORM\Query\UpdateQuery|\Cake\ORM\Query\DeleteQuery $query Statement of a single-row write.
     * @return \Cake\ORM\Query\UpdateQuery|\Cake\ORM\Query\DeleteQuery The same statement, limited to the scope.
     */
    private function withScopeCondition(UpdateQuery|DeleteQuery $query): UpdateQuery|DeleteQuery
    {
        $column = null;
        foreach ($this->behaviors()->loaded() as $name) {
            $behavior = $this->behaviors()->get($name);
            if ($behavior instanceof DevelopmentScopedBehavior) {
                $column = $behavior->guardedColumn();
            }
        }
        if ($column === null) {
            return $query;
        }

        $scope = DevelopmentScopeStack::current()
            ?? throw new LogicException(sprintf('No development scope is running for %s.', $this->getAlias()));
        if ($scope->isAll()) {
            return $query;
        }

        return $scope->isEmpty() ? $query->where('1 = 0') : $query->where([$column . ' IN' => $scope->ids()]);
    }

    /**
     * @param string $method Name of the refused method.
     * @return \LogicException
     */
    private function bulkRefused(string $method): LogicException
    {
        return new LogicException(sprintf(
            '%s() is not allowed on the development-scoped table %s: a bulk statement cannot be scoped. '
            . 'Load the entities under a scope and save or delete them one by one '
            . '(dependent associations need cascadeCallbacks).',
            $method,
            $this->getAlias(),
        ));
    }

    /**
     * @param string $method Method of `Cake\ORM\Table` that builds the statement of a single-row write.
     * @return bool Whether the method that called the one running now is that method of the ORM, running on this table.
     */
    private function calledByTheOrm(string $method): bool
    {
        // Frame 0 is this check, frame 1 the guarded method, frame 2 the method that called the guarded one.
        $caller = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2] ?? [];

        return ($caller['class'] ?? null) === Table::class
            && ($caller['function'] ?? null) === $method
            && ($caller['object'] ?? null) === $this;
    }
}
