<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Caja Model
 *
 * @method \App\Model\Entity\Caja get($primaryKey, $options = [])
 * @method \App\Model\Entity\Caja newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Caja[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Caja|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Caja saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Caja patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Caja[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Caja findOrCreate($search, callable $callback = null, $options = [])
 */
class CajaTable extends Table
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

        $this->setTable('caja');
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
            ->allowEmptyString('id_gasto', null, 'create');

        $validator
            ->integer('id_condominio')
            ->allowEmptyString('id_condominio');

        $validator
            ->scalar('beneficiario')
            ->maxLength('beneficiario', 200)
            ->allowEmptyString('beneficiario');

        $validator
            ->scalar('id_tipo')
            ->maxLength('id_tipo', 45)
            ->allowEmptyString('id_tipo');

        $validator
            ->scalar('id_categoria')
            ->maxLength('id_categoria', 45)
            ->allowEmptyString('id_categoria');

        $validator
            ->dateTime('fecha_pago')
            ->allowEmptyDateTime('fecha_pago');

        $validator
            ->date('fecha')
            ->allowEmptyDate('fecha');

        $validator
            ->allowEmptyString('recibo');

        return $validator;
    }
}
