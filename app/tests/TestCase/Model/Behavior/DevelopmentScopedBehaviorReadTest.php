<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Behavior;

use App\Authorization\DevelopmentScope;
use App\Model\Exception\MissingDevelopmentScopeException;
use App\Test\Support\ScopedTableTestCase;

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

        $found = $this->items->find('all', developmentScope: DevelopmentScope::only([1]))->contain('Notes')->firstOrFail();

        $this->assertSame(['n1'], collection($found->notes)->extract('body')->toList());
    }

    public function testACompositeJoinNeverCrossesDevelopments(): void
    {
        $item = $this->insertItem(1, 'a1');
        $this->appDb()->execute("INSERT INTO scoped_notes (development_id, item_id, body) VALUES (2, $item, 'foreign')");

        $found = $this->items->find('all', developmentScope: DevelopmentScope::only([1]))->contain('Notes')->firstOrFail();

        $this->assertSame([], $found->notes);
    }

    public function testAScopedTableReachedFromAnUnscopedOneWithoutScopeThrows(): void
    {
        $item = $this->insertItem(1, 'a1');
        $this->appDb()->execute("INSERT INTO scoped_holders (item_id) VALUES ($item)");

        $this->expectException(MissingDevelopmentScopeException::class);

        $this->holders->find()->contain('Items')->all();
    }

    public function testAnExplicitScopeOnTheContainedQueryIsHonored(): void
    {
        $item = $this->insertItem(1, 'a1');
        $this->appDb()->execute("INSERT INTO scoped_holders (item_id) VALUES ($item)");

        $found = $this->holders->find()->contain([
            'Items' => ['queryBuilder' => fn($q) => $q->applyOptions(['developmentScope' => DevelopmentScope::only([2])])],
        ])->firstOrFail();

        $this->assertNull($found->item);
    }

    public function testAThroughTableNeverInheritsAScope(): void
    {
        $this->expectException(MissingDevelopmentScopeException::class);

        $this->people->find()->eagerLoaded(true)->all();
    }
}
