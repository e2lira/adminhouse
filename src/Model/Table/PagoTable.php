<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Pago Model
 *
 * @method \App\Model\Entity\Pago get($primaryKey, $options = [])
 * @method \App\Model\Entity\Pago newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Pago[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Pago|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Pago|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Pago patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Pago[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Pago findOrCreate($search, callable $callback = null, $options = [])
 */
class PagoTable extends Table
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

        $this->setTable('pago');
        $this->setDisplayField('id_pago');
        $this->setPrimaryKey('id_pago');

        $this->hasMany('Condominio')
            ->setForeignKey('id_condominio')
            ->setDependent(true);


       /* $this->hasMany('Condomino')
            ->setForeignKey('id_condomino')
            ->setDependent(true); */

            $this->addBehavior('Robotusers/Excel.Excel', [
                'columnMap' => [
                    'A' => 'id_pago',
                    'B' => 'id_condominio',
                    'C' => 'id_condomino',
                    'D' => 'abono',
                    'E' => 'monto_letra',
                    'F' => 'concepto',
                    'G' => 'fecha_pago',
                    'H' => 'fecha',
                    'I' => 'referencia',
                    'K' => 'id_cuota',
                    'L' => 'folio'
                ]
            ]);

    /*  $this->hasMany('TipoPago')
            ->setForeignKey('id_tipo')
            ->setDependent(true);

        $this->hasMany(
            'banco', [
                'foreignKey' => 'id_pago',
                'dependent'  => true,
                'cascadeCallbacks' => true
            ]
        ); */

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
            ->integer('id_pago')
            ->allowEmpty('id_pago', 'create');

        $validator
            ->integer('id_condominio')
            ->requirePresence('id_condominio', 'create')
            ->notEmpty('id_condominio');

        $validator
            ->integer('id_condomino')
            ->requirePresence('id_condomino', 'create')
            ->notEmpty('id_condomino');

        $validator
            ->numeric('abono')
            ->requirePresence('abono', 'create')
            ->notEmpty('abono');

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
            ->date('fecha')
            ->requirePresence('fecha', 'create')
            ->notEmpty('fecha');

        $validator
            ->scalar('referencia')
            ->maxLength('referencia', 150)
            ->allowEmpty('referencia');

        $validator
            ->integer('folio')
            ->requirePresence('folio', 'create')
            ->notEmpty('folio');

        return $validator;
    }
}
