<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Fixed role catalogue (seeded by a migration).
 */
class RolesTable extends Table
{
    public const SUPER_ADMINISTRATOR = 'super_administrator';

    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('roles');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
    }
}
