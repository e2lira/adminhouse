<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Amenidad Model
 *
 * @property \App\Model\Table\CondominioTable|\Cake\ORM\Association\BelongsToMany $Condominio
 *
 * @method \App\Model\Entity\Amenidad get($primaryKey, $options = [])
 * @method \App\Model\Entity\Amenidad newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Amenidad[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Amenidad|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Amenidad patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Amenidad[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Amenidad findOrCreate($search, callable $callback = null, $options = [])
 */
class AmenidadTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('amenidad');
        $this->setDisplayField('id_amenidad');
        $this->setPrimaryKey('id_amenidad');

        $this->belongsToMany('Condominio', [
            'foreignKey' => 'id_condominio',
            'targetForeignKey' => 'id_amenidad',
            'joinTable' => 'amenidad_condominio'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id_amenidad')
            ->allowEmpty('id_amenidad', 'create');

        $validator
            ->scalar('amenidad')
            ->maxLength('amenidad', 45)
            ->requirePresence('amenidad', 'create')
            ->notEmpty('amenidad');

        $validator
            ->scalar('color')
            ->allowEmpty('color');

        return $validator;
    }
}
