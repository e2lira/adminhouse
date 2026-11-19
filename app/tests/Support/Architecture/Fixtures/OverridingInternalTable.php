<?php
declare(strict_types=1);

namespace App\Test\Support\Architecture\Fixtures;

use App\Model\Table\ScopedBulkOperationsTrait;
use App\Model\Table\ScopedOperationTrait;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;

/**
 * Architecture fixture: uses the traits but overrides an internal write method the trait's frame check relies on
 * (a violation).
 */
class OverridingInternalTable extends Table
{
    use ScopedBulkOperationsTrait;
    use ScopedOperationTrait;

    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('fixture_overriding_internal');
        $this->addBehavior('DevelopmentScoped');
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param array<string, mixed> $data Data.
     * @return \Cake\Datasource\EntityInterface|false
     */
    protected function _update(EntityInterface $entity, array $data): EntityInterface|false
    {
        return $entity;
    }
}
