<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Behavior;

use App\Authorization\DevelopmentScope;
use App\Authorization\DevelopmentScopeStack;
use App\Model\Exception\MissingDevelopmentScopeException;
use App\Test\Support\Scoped\ScopedSchema;
use App\Test\Support\ScopedTableTestCase;
use Cake\ORM\Table;
use LogicException;

/**
 * Layer 1 reads: fail closed without scope, filter by the scope, inherit only through composite foreign keys.
 */
class DevelopmentScopedBehaviorReadTest extends ScopedTableTestCase
{
    /**
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query.
     * @return list<string> Sorted item names.
     */
    private function names($query): array
    {
        $names = $query->all()->extract('name')->toList();
        sort($names);

        return $names;
    }

    private function seedItems(): void
    {
        $this->insertItem(1, 'a1');
        $this->insertItem(1, 'a2');
        $this->insertItem(2, 'b1');
    }

    public function testAPrimaryFindWithoutScopeThrowsAndReturnsNothing(): void
    {
        $this->seedItems();

        $this->expectException(MissingDevelopmentScopeException::class);

        $this->items->find()->all();
    }

    public function testAnAllScopeAddsNoFilter(): void
    {
        $this->seedItems();

        $query = $this->items->find('all', developmentScope: DevelopmentScope::all());

        $this->assertSame(['a1', 'a2', 'b1'], $this->names($query));
        $this->assertStringNotContainsString('development_id IN', $query->sql());
    }

    public function testAnEmptyScopeMatchesNothing(): void
    {
        $this->seedItems();

        $query = $this->items->find('all', developmentScope: DevelopmentScope::none());

        $this->assertStringContainsString('1 = 0', $query->sql());
        $this->assertSame([], $this->names($query));
    }

    public function testAScopeWithIdsFiltersByTheDevelopmentColumn(): void
    {
        $this->seedItems();

        $this->assertSame(['a1', 'a2'], $this->names($this->items->find('all', developmentScope: DevelopmentScope::only([1]))));
        $this->assertSame(['b1'], $this->names($this->items->find('all', developmentScope: DevelopmentScope::only([2]))));
    }

    public function testTheFieldOptionFiltersByAnotherColumn(): void
    {
        $this->appDb()->execute("INSERT INTO scoped_realms (name) VALUES ('r1'), ('r2')");

        $rows = $this->realms->find('all', developmentScope: DevelopmentScope::only([2]))->all();

        $this->assertSame(['r2'], $rows->extract('name')->toList());
    }

    public function testTheThroughOptionFiltersByTheLinkTable(): void
    {
        $this->appDb()->execute("INSERT INTO scoped_people (name) VALUES ('p1'), ('p2'), ('p3')");
        $this->appDb()->execute('INSERT INTO scoped_person_links (person_id, development_id) VALUES (1, 1), (2, 2), (3, 1), (3, 2)');

        $rows = $this->people->find('all', developmentScope: DevelopmentScope::only([1]))->all();

        $names = $rows->extract('name')->toList();
        sort($names);
        $this->assertSame(['p1', 'p3'], $names);
    }

    public function testAContainedTableInheritsThroughACompositeForeignKey(): void
    {
        $item = $this->insertItem(1, 'a1');
        $this->appDb()->execute("INSERT INTO scoped_notes (development_id, item_id, body) VALUES (1, $item, 'n1')");

        $found = $this->items->find('all', developmentScope: DevelopmentScope::only([1]))->contain('ScopedNotes')->firstOrFail();

        $this->assertSame(['n1'], collection($found->notes)->extract('body')->toList());
    }

    private function seedNoteForHolder(): void
    {
        $item = $this->insertItem(1, 'a1');
        $this->appDb()->execute("INSERT INTO scoped_notes (development_id, item_id, body) VALUES (1, $item, 'n1')");
        $this->appDb()->execute('INSERT INTO scoped_holders (item_id, note_id) VALUES (?, 1)', [$item]);
    }

    public function testACompositeCapableTableContainedFromAnUnscopedParentThrows(): void
    {
        $this->seedNoteForHolder();

        $this->expectException(MissingDevelopmentScopeException::class);

        $this->holders->find()->contain('ScopedNotes')->all();
    }

    public function testACompositeCapableTableMatchedFromAnUnscopedParentThrows(): void
    {
        $this->seedNoteForHolder();

        $this->expectException(MissingDevelopmentScopeException::class);

        $this->holders->find()->matching('ScopedNotes')->all();
    }

    public function testACompositeCapableTableJoinedWithInnerJoinWithFromAnUnscopedParentThrows(): void
    {
        $this->seedNoteForHolder();

        $this->expectException(MissingDevelopmentScopeException::class);

        $this->holders->find()->innerJoinWith('ScopedNotes')->all();
    }

    public function testAScopedParentReachingAScopedTableThroughAPlainForeignKeyThrows(): void
    {
        $this->appDb()->execute("INSERT INTO scoped_notes (development_id, item_id, body) VALUES (2, 1, 'foreign')");
        $item = $this->insertItem(1, 'a1');
        $this->appDb()->execute('UPDATE scoped_items SET note_id = 1 WHERE id = ?', [$item]);

        $this->expectException(MissingDevelopmentScopeException::class);

        $this->items->find('all', developmentScope: DevelopmentScope::only([1]))->contain('ScopedLooseNotes')->all();
    }

    public function testAScopedParentMatchingACompositeTableNeedsAnExplicitScope(): void
    {
        $item = $this->insertItem(1, 'a1');
        $this->appDb()->execute("INSERT INTO scoped_notes (development_id, item_id, body) VALUES (1, $item, 'n1')");

        $this->expectException(MissingDevelopmentScopeException::class);

        $this->items->find('all', developmentScope: DevelopmentScope::only([1]))->matching('ScopedNotes')->all();
    }

    public function testAScopedParentMatchingWithAnExplicitScopeFilters(): void
    {
        $item = $this->insertItem(1, 'a1');
        $this->appDb()->execute("INSERT INTO scoped_notes (development_id, item_id, body) VALUES (1, $item, 'n1')");

        $rows = $this->items->find('all', developmentScope: DevelopmentScope::only([1]))
            ->matching('ScopedNotes', fn($q) => $q->applyOptions(['developmentScope' => DevelopmentScope::only([2])]))
            ->all();

        $this->assertCount(0, $rows);
    }

    public function testACompositeBelongsToJoinStrategyFiltersByTheParentScope(): void
    {
        $item = $this->insertItem(1, 'a1');
        $this->appDb()->execute("INSERT INTO scoped_notes (development_id, item_id, body) VALUES (1, $item, 'n1')");

        $query = $this->notes->find('all', developmentScope: DevelopmentScope::only([1]))->contain('ScopedItems');

        $this->assertSame('a1', $query->firstOrFail()->item->name);
        $this->assertStringContainsString('ScopedItems.development_id IN', $query->sql());
        $this->assertCount(0, $this->notes->find('all', developmentScope: DevelopmentScope::only([2]))->contain('ScopedItems')->all());
    }

    public function testACompositeBelongsToSelectStrategyFiltersByTheParentScope(): void
    {
        $item = $this->insertItem(1, 'a1');
        $this->appDb()->execute("INSERT INTO scoped_notes (development_id, item_id, body) VALUES (1, $item, 'n1')");

        $found = $this->notes->find('all', developmentScope: DevelopmentScope::all())
            ->contain(['ScopedItems' => ['strategy' => 'select']])
            ->firstOrFail();

        $this->assertSame('a1', $found->item->name);
    }

    public function testNestedContainKeepsFilteringDownACompositeChain(): void
    {
        $item = $this->insertItem(1, 'a1');
        $this->appDb()->execute("INSERT INTO scoped_notes (development_id, item_id, body) VALUES (1, $item, 'n1')");

        $found = $this->notes->find('all', developmentScope: DevelopmentScope::only([1]))
            ->contain('ScopedItems.ScopedNotes')
            ->firstOrFail();

        $this->assertSame(['n1'], collection($found->item->notes)->extract('body')->toList());
    }

    public function testAnExplicitScopeOnAContainedQueryIsNarrowedToTheParentScopeNeverWidened(): void
    {
        $item = $this->insertItem(1, 'a1');
        $this->appDb()->execute("INSERT INTO scoped_notes (development_id, item_id, body) VALUES (1, $item, 'n1')");
        $seen = [];
        $this->notes->getEventManager()->on(
            'Model.beforeFind',
            ['priority' => 20],
            function ($event, $query, $options) use (&$seen): void {
                $seen[] = $options['developmentScope'];
            },
        );

        $this->items->find('all', developmentScope: DevelopmentScope::only([1]))->contain([
            'ScopedNotes' => ['queryBuilder' => fn($q) => $q->applyOptions(['developmentScope' => DevelopmentScope::only([1, 2])])],
        ])->all();
        $this->items->find('all', developmentScope: DevelopmentScope::all())->contain([
            'ScopedNotes' => ['queryBuilder' => fn($q) => $q->applyOptions(['developmentScope' => DevelopmentScope::only([2])])],
        ])->all();

        $this->assertCount(2, $seen);
        $this->assertSame([1], $seen[0]->ids());
        $this->assertSame([2], $seen[1]->ids());
    }

    public function testAManyToManyTargetNeedsAnExplicitScope(): void
    {
        $item = $this->insertItem(1, 'a1');
        $this->appDb()->execute("INSERT INTO scoped_realms (name) VALUES ('r1')");
        $this->appDb()->execute('INSERT INTO scoped_item_realms (item_id, realm_id) VALUES (?, 1)', [$item]);

        try {
            $this->items->find('all', developmentScope: DevelopmentScope::all())->contain('ScopedTags')->all();
            $this->fail('A many-to-many target must not inherit the parent scope.');
        } catch (MissingDevelopmentScopeException) {
        }

        $found = $this->items->find('all', developmentScope: DevelopmentScope::all())->contain([
            'ScopedTags' => ['queryBuilder' => fn($q) => $q->applyOptions(['developmentScope' => DevelopmentScope::only([2])])],
        ])->firstOrFail();
        $this->assertSame([], $found->tags);
    }

    public function testAStringForeignKeyFromTheDevelopmentColumnLinksTheChildren(): void
    {
        $this->appDb()->execute("INSERT INTO scoped_realms (name) VALUES ('r1'), ('r2')");
        $this->insertItem(1, 'a1');
        $this->insertItem(2, 'b1');

        $found = $this->realms->find('all', developmentScope: DevelopmentScope::only([1]))
            ->contain('ScopedItems')
            ->all();

        $this->assertCount(1, $found);
        $this->assertSame(['a1'], collection($found->first()->items)->extract('name')->toList());
    }

    public function testAnExplicitScopeNeverWidensTheRunningOperationScope(): void
    {
        $this->seedItems();

        $names = DevelopmentScopeStack::run(
            DevelopmentScope::only([1]),
            fn() => $this->names($this->items->find('all', developmentScope: DevelopmentScope::all())),
        );

        $this->assertSame(['a1', 'a2'], $names);
    }

    public function testAMatchingBuilderNeverWidensTheRunningOperationScope(): void
    {
        $item = $this->insertItem(1, 'a1');
        $this->appDb()->execute("INSERT INTO scoped_notes (development_id, item_id, body) VALUES (1, $item, 'n1')");
        $this->appDb()->execute("INSERT INTO scoped_notes (development_id, item_id, body) VALUES (2, $item, 'n2')");
        $this->appDb()->execute('INSERT INTO scoped_holders (item_id, note_id) VALUES (?, 1), (?, 2)', [$item, $item]);

        $count = DevelopmentScopeStack::run(
            DevelopmentScope::only([1]),
            fn() => $this->holders->find()
                ->matching('ScopedNotes', fn($q) => $q->applyOptions(['developmentScope' => DevelopmentScope::all()]))
                ->count(),
        );

        $this->assertSame(1, $count);
    }

    public function testAnUnscopedParentTrustsTheExplicitScopeOfItsContainBuilder(): void
    {
        $item = $this->insertItem(1, 'a1');
        $this->appDb()->execute("INSERT INTO scoped_holders (item_id) VALUES ($item)");

        $found = $this->holders->find()->contain([
            'ScopedItems' => ['queryBuilder' => fn($q) => $q->applyOptions(['developmentScope' => DevelopmentScope::all()])],
        ])->firstOrFail();

        $this->assertSame('a1', $found->item->name);
    }

    public function testAThroughTableWithACompositePrimaryKeyIsRejected(): void
    {
        $table = new Table(['table' => ScopedSchema::qualify('scoped_person_links'), 'alias' => 'ScopedLinks']);
        $table->setPrimaryKey(['person_id', 'development_id']);
        $table->addBehavior('DevelopmentScoped', ['through' => 'scoped_person_links', 'throughKey' => 'person_id']);

        $this->expectException(LogicException::class);

        $table->find('all', developmentScope: DevelopmentScope::only([1]))->all();
    }

    public function testAThroughTableNeverInheritsAScope(): void
    {
        $this->expectException(MissingDevelopmentScopeException::class);

        $this->people->find()->eagerLoaded(true)->all();
    }
}
