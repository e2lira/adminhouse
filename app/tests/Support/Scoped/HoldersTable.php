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

        $this->setTable(ScopedSchema::qualify('scoped_holders'));
        $this->belongsTo('ScopedItems', [
            'className' => ItemsTable::class,
            'foreignKey' => 'item_id',
            'propertyName' => 'item',
        ]);
        // A table that is itself scoped by a composite foreign key, reached here through a plain one.
        $this->belongsTo('ScopedNotes', [
            'className' => NotesTable::class,
            'foreignKey' => 'note_id',
            'propertyName' => 'note',
        ]);
    }
}
