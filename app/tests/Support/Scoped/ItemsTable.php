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

        $this->setTable(ScopedSchema::qualify('scoped_items'));
        $this->addBehavior('DevelopmentScoped');
        $this->hasMany('ScopedNotes', [
            'propertyName' => 'notes',
            'className' => NotesTable::class,
            'foreignKey' => ['development_id', 'item_id'],
            'bindingKey' => ['development_id', 'id'],
        ]);
        // A scoped table that is composite-capable elsewhere, reached from here through a plain foreign key.
        $this->belongsTo('ScopedLooseNotes', [
            'className' => NotesTable::class,
            'foreignKey' => 'note_id',
            'propertyName' => 'loose_note',
        ]);
        // Many-to-many: the target is reached through a junction, so the development is never linked by a key.
        $this->belongsToMany('ScopedTags', [
            'className' => RealmsTable::class,
            'through' => ItemRealmsTable::class,
            'foreignKey' => 'item_id',
            'targetForeignKey' => 'realm_id',
            'propertyName' => 'tags',
        ]);
        // Plain (single-column) foreign key to another scoped table: it can never inherit the development.
        $this->belongsTo('ScopedRealms', [
            'className' => RealmsTable::class,
            'foreignKey' => 'section_id',
            'propertyName' => 'realm',
        ]);
    }
}
