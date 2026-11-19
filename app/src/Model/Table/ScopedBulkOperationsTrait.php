<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Query\DeleteQuery;
use Cake\ORM\Query\UpdateQuery;
use Cake\ORM\Table;
use Closure;
use LogicException;

/**
 * Required on every Table class that adds `DevelopmentScopedBehavior` (an architecture test enforces it).
 *
 * A bulk statement has no entity and fires no find event, so the behavior cannot put the development condition on it.
 * `updateAll()`, `deleteAll()`, `updateQuery()` and `deleteQuery()` therefore throw on a scoped table. Row-level
 * security still bounds them in the database, but the application must not depend on it alone.
 *
 * The ORM itself builds the statement of a single-row `save()` (update) and `delete()` through `updateQuery()` and
 * `deleteQuery()`; only that direct internal call is let through. Those statements address one row by primary key,
 * after the behavior has read it under the scope.
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
     * @return \Cake\ORM\Query\UpdateQuery
     * @throws \LogicException Unless called by the ORM to save one entity.
     */
    public function updateQuery(): UpdateQuery
    {
        if (!$this->calledByTheOrm('_update')) {
            throw $this->bulkRefused(__FUNCTION__);
        }

        return parent::updateQuery();
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

        return parent::deleteQuery();
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
     * @return bool Whether the method that called the one running now is that method of the ORM itself.
     */
    private function calledByTheOrm(string $method): bool
    {
        // Frame 0 is this check, frame 1 the guarded method, frame 2 the method that called the guarded one.
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2] ?? [];

        return ($caller['class'] ?? null) === Table::class && ($caller['function'] ?? null) === $method;
    }
}
