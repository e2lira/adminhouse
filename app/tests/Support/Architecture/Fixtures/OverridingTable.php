<?php
declare(strict_types=1);

namespace App\Test\Support\Architecture\Fixtures;

use App\Model\Table\ScopedBulkOperationsTrait;
use App\Model\Table\ScopedOperationTrait;
use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Table;
use Closure;

/**
 * Architecture fixture: uses the bulk-operations trait but overrides one of its methods (a violation).
 */
class OverridingTable extends Table
{
    use ScopedBulkOperationsTrait;
    use ScopedOperationTrait;

    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('fixture_overriding');
        $this->addBehavior('DevelopmentScoped');
    }

    /**
     * @param \Cake\Database\Expression\QueryExpression|\Closure|array|string|null $conditions Conditions.
     * @return int
     */
    public function deleteAll(QueryExpression|Closure|array|string|null $conditions): int
    {
        return 0;
    }
}
