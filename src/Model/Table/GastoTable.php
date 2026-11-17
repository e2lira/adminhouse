<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Gasto Model
 *
 * @method \App\Model\Entity\Gasto get($primaryKey, $options = [])
 * @method \App\Model\Entity\Gasto newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Gasto[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Gasto|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Gasto patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Gasto[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Gasto findOrCreate($search, callable $callback = null, $options = [])
 */
class GastoTable extends Table
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

        $this->setTable('gasto');
        $this->setDisplayField('id_gasto');
        $this->setPrimaryKey('id_gasto');

        $this->addBehavior('Josegonzalez/Upload.Upload', [
            'recibo'=> []
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
            ->integer('id_gasto')
            ->allowEmpty('id_gasto', 'create');

        $validator
            ->integer('id_tipo')
            ->requirePresence('id_tipo', 'create')
            ->notEmpty('id_tipo');

        $validator
            ->integer('id_categoria');

        $validator
            ->numeric('gasto')
            ->requirePresence('gasto', 'create')
            ->notEmpty('gasto');

        $validator
            ->scalar('monto_letra')
            ->maxLength('monto_letra', 150)
            ->requirePresence('monto_letra', 'create')
            ->notEmpty('monto_letra');

        $validator
            ->scalar('concepto')
            ->maxLength('concepto', 200)
            ->requirePresence('concepto', 'create')
            ->notEmpty('concepto');

        $validator
            ->dateTime('fecha_pago')
            ->allowEmpty('fecha_pago');

        $validator
            ->scalar('referencia')
            ->maxLength('referencia', 150)
            ->requirePresence('referencia', 'create')
            ->notEmpty('referencia');


        $validator
            ->allowEmpty('recibo', 'create');

        return $validator;
    }
}
