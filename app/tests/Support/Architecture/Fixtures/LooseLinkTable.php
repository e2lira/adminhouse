<?php
declare(strict_types=1);

namespace App\Test\Support\Architecture\Fixtures;

use App\Model\Table\ScopedBulkOperationsTrait;
use App\Model\Table\ScopedOperationTrait;
use Cake\ORM\Table;

/**
 * Architecture fixture: a scoped table that reaches another scoped one by a plain foreign key (a violation unless
 * whitelisted), and one that reaches it by a development-column composite key (legal).
 */
class LooseLinkTable extends Table
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
        $this->setTable('fixture_loose_link');
        $this->addBehavior('DevelopmentScoped');
        $this->belongsTo('Plain', ['className' => GuardedTable::class, 'foreignKey' => 'guarded_id']);
        $this->belongsTo('Composite', [
            'className' => GuardedTable::class,
            'foreignKey' => ['development_id', 'guarded_id'],
            'bindingKey' => ['development_id', 'id'],
        ]);
    }
}
