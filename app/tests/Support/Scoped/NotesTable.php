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

        $this->setTable('scoped_notes');
        $this->addBehavior('DevelopmentScoped');
        $this->belongsTo('Items', [
            'className' => ItemsTable::class,
            'foreignKey' => ['development_id', 'item_id'],
            'bindingKey' => ['development_id', 'id'],
        ]);
    }
}
