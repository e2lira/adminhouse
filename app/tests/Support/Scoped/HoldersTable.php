<?php
declare(strict_types=1);

namespace App\Test\Support\Scoped;

use Cake\ORM\Table;

/**
 * Test-only UNSCOPED table (`scoped_holders`) that reaches a scoped table through a plain foreign key.
 */
class HoldersTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('scoped_holders');
        $this->belongsTo('Items', ['className' => ItemsTable::class, 'foreignKey' => 'item_id']);
    }
}
