<?php
declare(strict_types=1);

namespace App\Test\Support\Scoped;

use Cake\ORM\Table;

/**
 * Test-only scoped table (`scoped_items`), standing in for units. `section_id` references RealmsTable.
 */
class ItemsTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('scoped_items');
        $this->addBehavior('DevelopmentScoped');
        $this->hasMany('Notes', [
            'className' => NotesTable::class,
            'foreignKey' => ['development_id', 'item_id'],
            'bindingKey' => ['development_id', 'id'],
        ]);
    }
}
