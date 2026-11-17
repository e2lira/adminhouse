<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * RecibosPago Model
 *
 * @method \App\Model\Entity\RecibosPago get($primaryKey, $options = [])
 * @method \App\Model\Entity\RecibosPago newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\RecibosPago[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\RecibosPago|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\RecibosPago saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\RecibosPago patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\RecibosPago[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\RecibosPago findOrCreate($search, callable $callback = null, $options = [])
 */
class RecibosPagoTable extends Table
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

        $this->setTable('recibos_pago');
        $this->setDisplayField('id_reporte');
        $this->setPrimaryKey('id_reporte');
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
            ->integer('id_reporte')
            ->allowEmptyString('id_reporte', null, 'create');

        $validator
            ->integer('id_condominio')
            ->allowEmptyString('id_condominio');

        $validator
            ->date('fecha')
            ->allowEmptyDate('fecha');

        $validator
            ->dateTime('fecha_creacion')
            ->allowEmptyDateTime('fecha_creacion');

        return $validator;
    }
}
