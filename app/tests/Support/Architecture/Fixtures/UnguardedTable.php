<?php
declare(strict_types=1);

namespace App\Test\Support\Architecture\Fixtures;

use App\Model\Table\ScopedOperationTrait;
use Cake\ORM\Table;

/**
 * Architecture fixture: a scoped table that forgot the bulk-operations trait (a violation).
 */
class UnguardedTable extends Table
{
    use ScopedOperationTrait;

    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('fixture_unguarded');
        $this->addBehavior('DevelopmentScoped');
    }
}
