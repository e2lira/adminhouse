<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Role held by a user, globally (super administrator) or in one development.
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\RolesTable&\Cake\ORM\Association\BelongsTo $Roles
 */
class RoleAssignmentsTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('role_assignments');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', ['foreignKey' => 'user_id', 'joinType' => 'INNER']);
        $this->belongsTo('Roles', ['foreignKey' => 'role_id', 'joinType' => 'INNER']);
    }
}
