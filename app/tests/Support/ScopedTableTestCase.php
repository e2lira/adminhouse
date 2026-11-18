<?php
declare(strict_types=1);

namespace App\Test\Support;

use App\Authorization\DevelopmentScopeStack;
use App\Database\RowLevelSecurity;
use App\Test\Support\Scoped\HoldersTable;
use App\Test\Support\Scoped\ItemsTable;
use App\Test\Support\Scoped\NotesTable;
use App\Test\Support\Scoped\PeopleTable;
use App\Test\Support\Scoped\RealmsTable;

/**
 * Base for tests of `DevelopmentScopedBehavior`: temporary tables on the runtime connection (they exist only for that
 * session, so no migration is needed and no real table is touched) plus the test-only Table classes over them.
 */
abstract class ScopedTableTestCase extends DatabaseTestCase
{
    private const TABLES = 'scoped_items, scoped_notes, scoped_holders, scoped_people, scoped_person_links, scoped_realms';

    protected ItemsTable $items;

    protected NotesTable $notes;

    protected PeopleTable $people;

    protected RealmsTable $realms;

    protected HoldersTable $holders;

    protected function setUp(): void
    {
        parent::setUp();

        $db = $this->db();
        $db->execute('DROP TABLE IF EXISTS ' . self::TABLES);
        foreach (
            [
                'scoped_items (id serial PRIMARY KEY, development_id integer NOT NULL, name text, section_id integer,
                    UNIQUE (development_id, id), UNIQUE (development_id, name))',
                'scoped_notes (id serial PRIMARY KEY, development_id integer NOT NULL, item_id integer NOT NULL,
                    body text)',
                'scoped_holders (id serial PRIMARY KEY, item_id integer)',
                'scoped_people (id serial PRIMARY KEY, name text)',
                'scoped_person_links (person_id integer NOT NULL, development_id integer NOT NULL)',
                'scoped_realms (id serial PRIMARY KEY, name text)',
            ] as $definition
        ) {
            $db->execute('CREATE TABLE ' . $definition);
            $db->execute('GRANT ALL ON ' . strtok($definition, ' ') . ' TO ' . RowLevelSecurity::ROLE_APP);
        }
        $db->execute('GRANT USAGE ON ALL SEQUENCES IN SCHEMA public TO ' . RowLevelSecurity::ROLE_APP);

        $locator = $this->getTableLocator();
        $this->items = $locator->get('ScopedItems', ['className' => ItemsTable::class]);
        $this->notes = $locator->get('Notes', ['className' => NotesTable::class]);
        $this->people = $locator->get('ScopedPeople', ['className' => PeopleTable::class]);
        $this->realms = $locator->get('ScopedRealms', ['className' => RealmsTable::class]);
        $this->holders = $locator->get('Holders', ['className' => HoldersTable::class]);
    }

    protected function tearDown(): void
    {
        $this->assertNull(DevelopmentScopeStack::current(), 'No scope may outlive the operation that set it.');
        $this->getTableLocator()->clear();

        parent::tearDown();
        $this->db()->execute('DROP TABLE IF EXISTS ' . self::TABLES);
    }

    /**
     * @param int $developmentId Development id.
     * @param string $name Item name.
     * @return int New item id.
     */
    protected function insertItem(int $developmentId, string $name): int
    {
        $this->appDb()->execute(
            'INSERT INTO scoped_items (development_id, name) VALUES (?, ?)',
            [$developmentId, $name],
        );

        return (int)$this->appDb()->execute('SELECT id FROM scoped_items WHERE name = ?', [$name])->fetch()[0];
    }
}
