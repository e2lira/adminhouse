<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Developments (condominiums and subdivisions) administered in the system.
 *
 * Scoped by their own id (AD3): every read needs the `developmentScope` option, and bulk statements are refused.
 */
class DevelopmentsTable extends Table
{
    use ScopedBulkOperationsTrait;

    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('developments');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('DevelopmentScoped', ['field' => 'id']);
    }
}
