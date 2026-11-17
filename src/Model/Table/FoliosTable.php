<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Folios Model
 *
 * @method \App\Model\Entity\Folio get($primaryKey, $options = [])
 * @method \App\Model\Entity\Folio newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Folio[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Folio|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Folio patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Folio[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Folio findOrCreate($search, callable $callback = null, $options = [])
 */
class FoliosTable extends Table
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

        $this->setTable('folios');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Condominio')
            ->setForeignKey('id_condominio')
            ->setJoinType('INNER');
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
            ->integer('id')
            ->allowEmpty('id', 'create');

        $validator
            ->integer('id_condominio')
            ->requirePresence('id_condominio', 'create')
            ->notEmpty('id_condominio');

        $validator
            ->integer('serie')
            ->requirePresence('serie', 'create')
            ->notEmpty('serie');

        $validator
            ->requirePresence('incremento', 'create')
            ->notEmpty('incremento');

        $validator
            ->dateTime('fecha_alta')
            ->requirePresence('fecha_alta', 'create')
            ->notEmpty('fecha_alta');

        return $validator;
    }
}
