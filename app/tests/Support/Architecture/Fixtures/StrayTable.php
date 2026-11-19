<?php
declare(strict_types=1);

namespace App\Test\Support\Architecture\Fixtures;

use Cake\ORM\Table;

/**
 * Architecture fixture: an unscoped table that reaches a scoped one (a violation unless whitelisted).
 */
class StrayTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('fixture_stray');
        $this->belongsTo('Guarded', ['className' => GuardedTable::class, 'foreignKey' => 'guarded_id']);
    }
}
