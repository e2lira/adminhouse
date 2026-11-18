<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Users table. Credentials are stored apart, in `user_credentials`.
 *
 * @property \App\Model\Table\UserCredentialsTable&\Cake\ORM\Association\HasOne $UserCredentials
 * @property \App\Model\Table\RoleAssignmentsTable&\Cake\ORM\Association\HasMany $RoleAssignments
 */
class UsersTable extends Table
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISABLED = 'disabled';

    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setDisplayField('username');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasOne('UserCredentials', ['foreignKey' => 'user_id', 'dependent' => true]);
        $this->hasMany('RoleAssignments', ['foreignKey' => 'user_id']);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('username')
            ->requirePresence('username', 'create')
            ->notEmptyString('username')
            ->maxLength('username', 64)
            ->regex('username', '/^[A-Za-z0-9._@-]+$/', 'Only letters, digits and . _ @ - are allowed.');

        $validator
            ->inList('status', [self::STATUS_ACTIVE, self::STATUS_DISABLED])
            ->notEmptyString('status');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules Rules.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['username']), ['errorField' => 'username']);

        return $rules;
    }
}
