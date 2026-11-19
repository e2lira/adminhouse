<?php
declare(strict_types=1);

namespace App\Test\Support\Scoped;

use Cake\ORM\Table;

/**
 * Test-only junction table (`scoped_item_realms`) between ItemsTable and RealmsTable. It is not scoped itself.
 */
class ItemRealmsTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable(ScopedSchema::qualify('scoped_item_realms'));
        $this->belongsTo('ScopedItems', ['className' => ItemsTable::class, 'foreignKey' => 'item_id']);
        $this->belongsTo('ScopedRealms', ['className' => RealmsTable::class, 'foreignKey' => 'realm_id']);
    }
}
