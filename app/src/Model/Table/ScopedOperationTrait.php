<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Authorization\DevelopmentScopeStack;
use App\Database\ConstraintViolation;
use Cake\Datasource\EntityInterface;
use Throwable;

/**
 * For Table classes that use `DevelopmentScopedBehavior`: publishes the scope of a save or delete for the length of
 * the operation, so rules and nested saves run under it (AD4b), and always takes it back (W-A).
 *
 * A unique or foreign key violation raised by the database anyway (a race) becomes a field error, not a 500.
 */
trait ScopedOperationTrait
{
    /**
     * @param \Cake\Datasource\EntityInterface $entity Entity to save.
     * @param array<string, mixed> $options Save options; `developmentScope` and `developmentId` apply.
     * @return \Cake\Datasource\EntityInterface|false
     */
    public function save(EntityInterface $entity, array $options = []): EntityInterface|false
    {
        try {
            $scope = $options['developmentScope'] ?? null;

            return DevelopmentScopeStack::run($scope, fn() => parent::save($entity, $options));
        } catch (Throwable $e) {
            $violation = ConstraintViolation::from($e);
            if ($violation === null) {
                throw $e;
            }
            $message = $violation->rule === '_unique'
                ? 'This value is already in use.'
                : 'The referenced record does not exist.';
            $entity->setError($violation->field(), [$violation->rule => $message]);

            return false;
        }
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entity Entity to delete.
     * @param array<string, mixed> $options Delete options; `developmentScope` applies.
     * @return bool
     */
    public function delete(EntityInterface $entity, array $options = []): bool
    {
        $scope = $options['developmentScope'] ?? null;

        return DevelopmentScopeStack::run($scope, fn() => parent::delete($entity, $options));
    }
}
