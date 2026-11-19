<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Authorization\DevelopmentScope;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * W-E in the model layer: a table that uses this trait refuses to save a row into an archived development, whoever
 * calls and whatever the policy said (defense in depth; the policy is the first line).
 *
 * It runs in `beforeSave`, inside the save transaction and also when the rules are skipped. It reads the development
 * through the scoped `DevelopmentsTable` and locks the row (`FOR UPDATE`), the same pattern as archiving, so a
 * concurrent archive waits for this save and a save that starts after the archive sees it. The lock needs the UPDATE
 * policy of `developments`, which only the super-administrator holds (the only actor who archives, and the only one
 * not bounded by the archive check in `permission_dev_ids`); for anyone else the read is plain and row-level security
 * refuses the write itself in an archived development. A development that cannot be found under the save scope is
 * refused too (fail closed). The refusal is an error on `development_id`, never an
 * exception.
 *
 * Use it on a Table that also uses `ScopedOperationTrait`: the save scope is the one that is running.
 */
trait RefusesArchivedDevelopmentTrait
{
    use LocatorAwareTrait;

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Entity being saved.
     * @param \ArrayObject<string, mixed> $options Save options.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        // An existing row is judged by the development it is stored in; a new one by the save target.
        $developmentId = $entity->isNew()
            ? ($entity->get('development_id') ?? $options['developmentId'] ?? null)
            : $entity->getOriginal('development_id');

        if ($developmentId === null || !$this->developmentTakesWrites((int)$developmentId)) {
            $entity->setError('development_id', ['archived' => 'The development is archived or not available.']);
            $event->setResult(false);
            $event->stopPropagation();
        }
    }

    /**
     * @param int $developmentId Development id.
     * @return bool Whether it exists under the running scope and is not archived (row locked until commit).
     */
    private function developmentTakesWrites(int $developmentId): bool
    {
        $developments = $this->fetchTable('Developments');
        $scope = DevelopmentScope::only([$developmentId]);
        $read = fn() => $developments->find('all', developmentScope: $scope)
            ->select(['Developments.id', 'Developments.archived_at'])
            ->where(['Developments.id' => $developmentId]);

        // `FOR UPDATE` also needs the UPDATE policy of `developments`, which only the super-administrator has: for
        // anyone else it returns no row, so fall back to a plain read. Their writes are still bounded atomically by
        // row-level security (`permission_dev_ids` excludes archived developments for write permissions).
        $development = $read()->epilog('FOR UPDATE')->first() ?? $read()->first();

        return $development !== null && $development->get('archived_at') === null;
    }
}
