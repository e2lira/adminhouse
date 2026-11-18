<?php
declare(strict_types=1);

namespace App\Test\Support\Scoped;

use Cake\ORM\Table;

/**
 * Test-only scoped table (`scoped_notes`) joined to ItemsTable by the composite key `(development_id, item_id)`.
 */
class NotesTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable(ScopedSchema::qualify('scoped_notes'));
        $this->addBehavior('DevelopmentScoped');
        $this->belongsTo('ScopedItems', [
            'propertyName' => 'item',
            'className' => ItemsTable::class,
            'foreignKey' => ['development_id', 'item_id'],
            'bindingKey' => ['development_id', 'id'],
        ]);
    }
}
