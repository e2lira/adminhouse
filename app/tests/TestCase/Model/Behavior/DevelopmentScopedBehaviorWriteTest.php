<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Behavior;

use App\Authorization\DevelopmentScope;
use App\Authorization\DevelopmentScopeStack;
use App\Model\Exception\DevelopmentScopeViolation;
use App\Model\Exception\MissingDevelopmentScopeException;
use App\Test\Support\ScopedTableTestCase;
use Cake\Database\Exception\QueryException;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use PDOException;
use RuntimeException;

/**
 * Layer 1 writes: the development comes from the scope, never from the body; the rules phase runs under the save's
 * scope and never leaks it (AD4, AD4b, W-A, W-B).
 */
class DevelopmentScopedBehaviorWriteTest extends ScopedTableTestCase
{
    /**
     * @param int $developmentId Development id.
     * @return array<string, mixed> Save options.
     */
    private function saveIn(int $developmentId): array
    {
        return ['developmentScope' => DevelopmentScope::only([$developmentId]), 'developmentId' => $developmentId];
    }

    private function storedDevelopmentOf(string $name): ?int
    {
        $row = $this->appDb()->execute('SELECT development_id FROM scoped_items WHERE name = ?', [$name])->fetch();

        return $row === false ? null : (int)$row[0];
    }

    public function testABodyDevelopmentDifferentFromTheTargetThrowsBeforeMarshalling(): void
    {
        $this->expectException(DevelopmentScopeViolation::class);

        $this->items->newEntity(['name' => 'x', 'development_id' => 2], $this->saveIn(1));
    }

    public function testABodyDevelopmentOutsideTheScopeThrowsWhenThereIsNoTarget(): void
    {
        $this->expectException(DevelopmentScopeViolation::class);

        $this->items->newEntity(
            ['name' => 'x', 'development_id' => 2],
            ['developmentScope' => DevelopmentScope::only([1])],
        );
    }

    public function testTheDevelopmentColumnIsNeverMassAssigned(): void
    {
        $entity = $this->items->newEntity(['name' => 'x', 'development_id' => 1], $this->saveIn(1));

        $this->assertNull($entity->get('development_id'));
    }

    public function testTheSaveOptionSetsTheDevelopmentOfNewEntities(): void
    {
        $entity = $this->items->newEntity(['name' => 'x'], $this->saveIn(1));

        $this->assertNotFalse($this->items->save($entity, $this->saveIn(1)));
        $this->assertSame(1, $this->storedDevelopmentOf('x'));
    }

    public function testANewEntityOutsideTheScopeOrWithoutATargetIsRefused(): void
    {
        $outside = ['developmentScope' => DevelopmentScope::only([1]), 'developmentId' => 2];
        $noTarget = ['developmentScope' => DevelopmentScope::only([1])];

        foreach ([$outside, $noTarget] as $options) {
            try {
                $this->items->save($this->items->newEntity(['name' => 'x']), $options);
                $this->fail('The save must be refused.');
            } catch (DevelopmentScopeViolation) {
                $this->assertNull($this->storedDevelopmentOf('x'));
            }
        }
    }

    public function testASaveWithoutScopeThrows(): void
    {
        $this->expectException(MissingDevelopmentScopeException::class);

        $this->items->save($this->items->newEntity(['name' => 'x']), ['developmentId' => 1]);
    }

    public function testAnExistingEntityCannotChangeDevelopmentOrLeaveTheScope(): void
    {
        $mine = $this->items->get($this->insertItem(1, 'mine'), developmentScope: DevelopmentScope::all());
        $theirs = $this->items->get($this->insertItem(2, 'theirs'), developmentScope: DevelopmentScope::all());

        $mine->set('development_id', 2);
        try {
            $this->items->save($mine, $this->saveIn(1));
            $this->fail('A development change must be refused.');
        } catch (DevelopmentScopeViolation) {
            $this->assertSame(1, $this->storedDevelopmentOf('mine'));
        }

        $theirs->set('name', 'renamed');
        $this->expectException(DevelopmentScopeViolation::class);
        $this->items->save($theirs, $this->saveIn(1));
    }

    public function testAnExistingEntityInScopeIsUpdated(): void
    {
        $mine = $this->items->get($this->insertItem(1, 'mine'), developmentScope: DevelopmentScope::all());
        $mine->set('name', 'renamed');

        $this->assertNotFalse($this->items->save($mine, $this->saveIn(1)));
        $this->assertSame(1, $this->storedDevelopmentOf('renamed'));
    }

    public function testInsertingADevelopmentRowRequiresAnAllScope(): void
    {
        $only = ['developmentScope' => DevelopmentScope::only([1])];
        $all = ['developmentScope' => DevelopmentScope::all()];

        try {
            $this->realms->save($this->realms->newEntity(['name' => 'r']), $only);
            $this->fail('Only a super-administrator scope may create developments.');
        } catch (DevelopmentScopeViolation) {
            $this->assertNotFalse($this->realms->save($this->realms->newEntity(['name' => 'r']), $all));
        }
    }

    public function testDeleteRequiresAScopeThatCoversTheRow(): void
    {
        $theirs = $this->items->get($this->insertItem(2, 'theirs'), developmentScope: DevelopmentScope::all());
        try {
            $this->items->delete($theirs);
            $this->fail('A delete without scope must throw.');
        } catch (MissingDevelopmentScopeException) {
        }
        try {
            $this->items->delete($theirs, ['developmentScope' => DevelopmentScope::only([1])]);
            $this->fail('A delete outside the scope must throw.');
        } catch (DevelopmentScopeViolation) {
        }

        $this->assertTrue($this->items->delete($theirs, ['developmentScope' => DevelopmentScope::only([2])]));
        $this->assertNull(DevelopmentScopeStack::current());
    }

    public function testARuleThatThrowsDoesNotLeakTheScopeIntoTheNextFind(): void
    {
        $this->items->getEventManager()->on('Model.buildRules', function (EventInterface $e, RulesChecker $rules): void {
            $rules->add(function (): bool {
                throw new RuntimeException('rule failure');
            });
        });
        try {
            $this->items->save($this->items->newEntity(['name' => 'x']), $this->saveIn(1));
            $this->fail('The rule must propagate its exception.');
        } catch (RuntimeException) {
            $this->assertNull(DevelopmentScopeStack::current());
        }

        $this->expectException(MissingDevelopmentScopeException::class);
        $this->items->find()->all();
    }

    public function testTheScopeIsClearedAfterSuccessfulAndFailedSaves(): void
    {
        $this->items->save($this->items->newEntity(['name' => 'ok']), $this->saveIn(1));
        $this->assertNull(DevelopmentScopeStack::current());

        $this->items->getEventManager()->on('Model.beforeSave', function (EventInterface $e): void {
            $e->setResult(false);
            $e->stopPropagation();
        });
        $this->assertFalse($this->items->save($this->items->newEntity(['name' => 'no']), $this->saveIn(1)));
        $this->assertNull(DevelopmentScopeStack::current());
    }

    public function testRulesOfAnotherScopedTableRunUnderTheOperationScope(): void
    {
        $this->appDb()->execute("INSERT INTO scoped_realms (name) VALUES ('r1'), ('r2')");
        $this->items->getEventManager()->on('Model.buildRules', function (EventInterface $e, RulesChecker $rules): void {
            $rules->add($rules->existsIn('section_id', $this->realms));
        });

        $inScope = $this->items->newEntity(['name' => 'in', 'section_id' => 1]);
        $outOfScope = $this->items->newEntity(['name' => 'out', 'section_id' => 2]);

        $this->assertNotFalse($this->items->save($inScope, $this->saveIn(1)));
        $this->assertFalse($this->items->save($outOfScope, $this->saveIn(1)));
        $this->assertNotEmpty($outOfScope->getError('section_id'));
    }

    public function testNestedAssociationSavesShareAndRestoreTheOperationScope(): void
    {
        $scope = DevelopmentScope::only([1]);
        $seen = [];
        $this->notes->getEventManager()->on('Model.beforeSave', function () use (&$seen): void {
            $seen['inner'] = DevelopmentScopeStack::current();
        });
        $this->items->getEventManager()->on('Model.afterSave', function () use (&$seen): void {
            $seen['outer'] = DevelopmentScopeStack::current();
        });

        $item = $this->items->newEntity(
            ['name' => 'with notes', 'notes' => [['body' => 'n1'], ['body' => 'n2']]],
            ['associated' => ['ScopedNotes']] + $this->saveIn(1),
        );
        $this->assertNotFalse($this->items->save($item, ['associated' => ['ScopedNotes']] + $this->saveIn(1)));

        $this->assertEquals($scope, $seen['inner']);
        $this->assertEquals($scope, $seen['outer']);
        $this->assertNull(DevelopmentScopeStack::current());
        $count = $this->appDb()->execute('SELECT COUNT(*) FROM scoped_notes WHERE development_id = 1')->fetch()[0];
        $this->assertSame(2, (int)$count);
    }

    public function testADatabaseUniqueRaceBecomesAFieldError(): void
    {
        $this->insertItem(1, 'taken');
        $entity = $this->items->newEntity(['name' => 'taken']);

        $this->assertFalse($this->items->save($entity, $this->saveIn(1)));
        $this->assertArrayHasKey('_unique', $entity->getError('name'));
    }

    public function testAForeignKeyViolationFromADriverExceptionBecomesAFieldError(): void
    {
        $driverError = new PDOException('constraint');
        $driverError->errorInfo = ['23503', 7, 'Key (development_id, section_id)=(1, 9) is not present in table "x".'];
        $this->items->getEventManager()->on('Model.beforeSave', function () use ($driverError): void {
            throw new RuntimeException('wrapped', 0, $driverError);
        });
        $entity = $this->items->newEntity(['name' => 'x']);

        $this->assertFalse($this->items->save($entity, $this->saveIn(1)));
        $errors = $entity->getError('section_id');
        $this->assertArrayHasKey('_existsIn', $errors);
        $this->assertStringNotContainsString('9', $errors['_existsIn']);
    }

    public function testAViolationInsideANestedChildSaveIsNotAttributedToTheParent(): void
    {
        $item = $this->items->newEntity(
            ['name' => 'parent', 'notes' => [['body' => 'same'], ['body' => 'same']]],
            ['associated' => ['ScopedNotes']] + $this->saveIn(1),
        );

        try {
            $this->items->save($item, ['associated' => ['ScopedNotes']] + $this->saveIn(1));
            $this->fail('A violation raised by a child save must reach the caller.');
        } catch (QueryException) {
            $this->assertSame([], $item->getErrors());
            $this->assertNull($this->storedDevelopmentOf('parent'));
        }
    }

    public function testSaveManyRethrowsAViolationAndLeavesTheConnectionUsable(): void
    {
        $this->insertItem(1, 'taken');
        $entities = $this->items->newEntities([['name' => 'fresh'], ['name' => 'taken']]);

        try {
            $this->items->saveMany($entities, $this->saveIn(1));
            $this->fail('A violation inside saveMany must not be hidden as a field error.');
        } catch (QueryException) {
            $this->assertSame([], $entities[1]->getErrors());
        }

        $this->assertNull($this->storedDevelopmentOf('fresh'));
        $this->assertFalse($this->appDb()->inTransaction());
        $this->assertSame(1, (int)$this->appDb()->execute('SELECT 1')->fetch()[0]);
    }

    public function testSaveManyWithSavepointsFlagsTheEntityThatViolatedAndRollsEverythingBack(): void
    {
        $this->insertItem(1, 'taken');
        $entities = $this->items->newEntities([['name' => 'fresh'], ['name' => 'taken']]);
        $this->appDb()->enableSavePoints(true);

        try {
            $this->assertFalse($this->items->saveMany($entities, $this->saveIn(1)));
            $this->assertArrayHasKey('_unique', $entities[1]->getError('name'));
            $this->assertSame([], $entities[0]->getErrors());
        } finally {
            $this->appDb()->disableSavePoints();
        }

        $this->assertNull($this->storedDevelopmentOf('fresh'));
        $this->assertSame(1, (int)$this->appDb()->execute('SELECT 1')->fetch()[0]);
    }

    public function testACallerTransactionWithoutSavepointsGetsTheViolationRethrown(): void
    {
        $this->insertItem(1, 'taken');
        $entity = $this->items->newEntity(['name' => 'taken']);

        $this->appDb()->begin();
        try {
            $this->items->save($entity, $this->saveIn(1));
            $this->fail('Converting here would leave the caller with an aborted transaction.');
        } catch (QueryException) {
            $this->assertSame([], $entity->getErrors());
        } finally {
            $this->appDb()->rollback();
        }
    }

    public function testACallerTransactionWithSavepointsKeepsWorkingAfterAConvertedViolation(): void
    {
        $this->insertItem(1, 'taken');
        $entity = $this->items->newEntity(['name' => 'taken']);

        $this->appDb()->enableSavePoints(true);
        $this->appDb()->begin();
        try {
            $this->assertFalse($this->items->save($entity, $this->saveIn(1)));
            $this->assertArrayHasKey('_unique', $entity->getError('name'));
            $this->assertSame(1, (int)$this->appDb()->execute('SELECT 1')->fetch()[0]);
            $this->assertNotFalse($this->items->save($this->items->newEntity(['name' => 'after']), $this->saveIn(1)));
        } finally {
            $this->appDb()->rollback();
            $this->appDb()->disableSavePoints();
        }
    }

    public function testARequestLikeSessionConvertsASingleEntityViolationAndStaysUsable(): void
    {
        $this->insertItem(1, 'taken');
        $entity = $this->items->newEntity(['name' => 'taken']);

        // Exactly what DevelopmentContext::beginRequest gives a request: an open transaction with savepoints.
        $this->actAs(null);
        $this->assertTrue($this->appDb()->inTransaction());
        $this->assertTrue($this->appDb()->isSavePointsEnabled());

        $this->assertFalse($this->items->save($entity, $this->saveIn(1)));
        $this->assertArrayHasKey('_unique', $entity->getError('name'));
        $this->assertNotFalse($this->items->save($this->items->newEntity(['name' => 'after']), $this->saveIn(1)));
    }

    public function testAViolationOfAnotherTableRaisedInAnAfterSaveHookIsNotAttributedToTheSavedEntity(): void
    {
        $this->appDb()->execute("INSERT INTO scoped_notes (development_id, item_id, body) VALUES (1, 1, 'dup')");
        $this->items->getEventManager()->on('Model.afterSave', function (): void {
            $this->appDb()->execute("INSERT INTO scoped_notes (development_id, item_id, body) VALUES (1, 1, 'dup')");
        });
        $entity = $this->items->newEntity(['name' => 'saved']);

        try {
            $this->items->save($entity, $this->saveIn(1));
            $this->fail('A violation of another table must reach the caller.');
        } catch (QueryException) {
            $this->assertSame([], $entity->getErrors());
        }
    }

    public function testAnInnerScopeMayNarrowTheOperationScopeButNeverWidenIt(): void
    {
        $outcome = [];
        $this->items->getEventManager()->on('Model.beforeSave', function () use (&$outcome): void {
            $wider = $this->notes->newEntity(['body' => 'wide', 'item_id' => 1], ['developmentId' => 1]);
            try {
                $this->notes->save($wider, [
                    'developmentScope' => DevelopmentScope::only([1, 2]),
                    'developmentId' => 2,
                    'associated' => false,
                    'atomic' => false,
                ]);
                $outcome['wider'] = 'saved';
            } catch (DevelopmentScopeViolation) {
                $outcome['wider'] = 'refused';
            }
        });

        $this->items->save($this->items->newEntity(['name' => 'outer']), $this->saveIn(1));

        $this->assertSame('refused', $outcome['wider']);
    }

    public function testAnExpressionIndexViolationIsRethrownBecauseNoColumnCanBeNamed(): void
    {
        $this->db()->execute('CREATE UNIQUE INDEX scoped_items_lower_name ON scoped_items (development_id, lower(name))');
        $this->insertItem(1, 'taken');
        $entity = $this->items->newEntity(['name' => 'TAKEN']);

        try {
            $this->items->save($entity, $this->saveIn(1));
            $this->fail('A violation that names no column of this table cannot be attributed to a field.');
        } catch (QueryException) {
            $this->assertSame([], $entity->getErrors());
        }
    }

    public function testAnExistingEntityWhoseKeyChangedIsRefusedOnSave(): void
    {
        $mine = $this->items->get($this->insertItem(1, 'mine'), developmentScope: DevelopmentScope::only([1]));
        $theirsId = $this->insertItem(2, 'theirs');
        $mine->set('id', $theirsId);
        $mine->set('name', 'hijacked');

        try {
            $this->items->save($mine, ['developmentScope' => DevelopmentScope::only([1])]);
            $this->fail('A changed primary key must be refused.');
        } catch (DevelopmentScopeViolation) {
            $row = $this->appDb()->execute('SELECT name FROM scoped_items WHERE id = ?', [$theirsId])->fetch();
            $this->assertSame('theirs', $row[0]);
        }
    }

    public function testAnExistingEntityWhoseKeyChangedIsRefusedOnDelete(): void
    {
        $mine = $this->items->get($this->insertItem(1, 'mine'), developmentScope: DevelopmentScope::only([1]));
        $theirsId = $this->insertItem(2, 'theirs');
        $mine->set('id', $theirsId);

        try {
            $this->items->delete($mine, ['developmentScope' => DevelopmentScope::only([1])]);
            $this->fail('A changed primary key must be refused.');
        } catch (DevelopmentScopeViolation) {
            $row = $this->appDb()->execute('SELECT name FROM scoped_items WHERE id = ?', [$theirsId])->fetch();
            $this->assertSame('theirs', $row[0]);
        }
    }
}
