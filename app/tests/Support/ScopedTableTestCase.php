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
use App\Test\Support\Scoped\ScopedSchema;

/**
 * Base for tests of `DevelopmentScopedBehavior`: scoped test tables plus the test-only Table classes over them.
 *
 * The tables are real tables (a TEMPORARY table would not be visible to the runtime role, which is another
 * connection), so they live in a schema of their own, named after the process, and both connections put that schema
 * first in their `search_path`. Nothing is created in `public`, parallel runs on the same database cannot collide, and
 * the schema is dropped (CASCADE) in a `finally` even when an assertion in tearDown fails.
 */
abstract class ScopedTableTestCase extends DatabaseTestCase
{
    protected ItemsTable $items;

    protected NotesTable $notes;

    protected PeopleTable $people;

    protected RealmsTable $realms;

    protected HoldersTable $holders;

    private string $schema = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->schema = ScopedSchema::name();
        $db = $this->db();
        $db->execute('DROP SCHEMA IF EXISTS ' . $this->schema . ' CASCADE');
        $db->execute('CREATE SCHEMA ' . $this->schema);
        $db->execute('GRANT USAGE ON SCHEMA ' . $this->schema . ' TO ' . RowLevelSecurity::ROLE_APP);
        $this->useSchema($this->schema . ', public');
        // Other suites enable savepoints on the shared runtime connection; each test states what it needs.
        $this->appDb()->disableSavePoints();
        foreach (
            [
                'scoped_items (id serial PRIMARY KEY, development_id integer NOT NULL, name text, section_id integer,
                    note_id integer,
                    UNIQUE (development_id, id), UNIQUE (development_id, name))',
                'scoped_notes (id serial PRIMARY KEY, development_id integer NOT NULL, item_id integer NOT NULL,
                    body text, UNIQUE (development_id, body))',
                'scoped_holders (id serial PRIMARY KEY, item_id integer, note_id integer)',
                'scoped_people (id serial PRIMARY KEY, name text)',
                'scoped_person_links (person_id integer NOT NULL, development_id integer NOT NULL)',
                'scoped_realms (id serial PRIMARY KEY, name text)',
                'scoped_item_realms (item_id integer NOT NULL, realm_id integer NOT NULL)',
            ] as $definition
        ) {
            $db->execute('CREATE TABLE ' . $definition);
            $db->execute('GRANT ALL ON ' . strtok($definition, ' ') . ' TO ' . RowLevelSecurity::ROLE_APP);
        }
        $db->execute('GRANT USAGE ON ALL SEQUENCES IN SCHEMA ' . $this->schema . ' TO ' . RowLevelSecurity::ROLE_APP);

        $locator = $this->getTableLocator();
        $this->items = $locator->get('ScopedItems', ['className' => ItemsTable::class]);
        $this->notes = $locator->get('ScopedNotes', ['className' => NotesTable::class]);
        $this->people = $locator->get('ScopedPeople', ['className' => PeopleTable::class]);
        $this->realms = $locator->get('ScopedRealms', ['className' => RealmsTable::class]);
        $this->holders = $locator->get('ScopedHolders', ['className' => HoldersTable::class]);
    }

    protected function tearDown(): void
    {
        try {
            $this->assertNull(DevelopmentScopeStack::current(), 'No scope may outlive the operation that set it.');
            $this->getTableLocator()->clear();
        } finally {
            try {
                parent::tearDown();
            } finally {
                $this->useSchema(null);
                $this->db()->execute('DROP SCHEMA IF EXISTS ' . $this->schema . ' CASCADE');
            }
        }
    }

    /**
     * @param string|null $path New `search_path` for both connections; null restores the default.
     * @return void
     */
    private function useSchema(?string $path): void
    {
        foreach ([$this->db(), $this->appDb()] as $connection) {
            $connection->execute($path === null ? 'RESET search_path' : 'SET search_path TO ' . $path);
        }
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
