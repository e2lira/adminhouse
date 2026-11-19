<?php
declare(strict_types=1);

namespace App\Test\Support\Architecture\Fixtures;

use App\Model\Table\ScopedBulkOperationsTrait;
use App\Model\Table\ScopedOperationTrait;
use Cake\ORM\Table;

/**
 * Architecture fixture: a correctly built scoped table. Never touches the database.
 */
class GuardedTable extends Table
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
        $this->setTable('fixture_guarded');
        $this->addBehavior('DevelopmentScoped');
    }
}
