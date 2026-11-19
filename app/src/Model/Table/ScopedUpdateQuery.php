<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Exception\DevelopmentScopeViolation;
use Cake\Database\StatementInterface;
use Cake\ORM\Query\UpdateQuery;

/**
 * The UPDATE the ORM builds for a single-row save of a scoped table.
 *
 * Cake's `Table::_update()` reports success whenever the statement ran, without looking at the row count. Once the
 * statement carries the development condition (or row-level security filters the row out), "no row matched" means the
 * entity reached nothing inside the scope, and it must not look saved. `delete()` already returns false in that case;
 * a save throws, like every other scope guard.
 */
class ScopedUpdateQuery extends UpdateQuery
{
    /**
     * @return \Cake\Database\StatementInterface
     * @throws \App\Model\Exception\DevelopmentScopeViolation When no row was updated.
     */
    public function execute(): StatementInterface
    {
        $statement = parent::execute();
        if ($statement->rowCount() < 1) {
            throw new DevelopmentScopeViolation();
        }

        return $statement;
    }
}
