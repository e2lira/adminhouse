<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Formulario Model
 *
 * @method \App\Model\Entity\Formulario get($primaryKey, $options = [])
 * @method \App\Model\Entity\Formulario newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Formulario[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Formulario|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Formulario patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Formulario[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Formulario findOrCreate($search, callable $callback = null, $options = [])
 */
class FormularioTable extends Table
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

        $this->setTable('formulario');
        $this->setDisplayField('id_formulario');
        $this->setPrimaryKey('id_formulario');
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
            ->integer('id_formulario')
            ->allowEmpty('id_formulario', 'create');

        $validator
            ->dateTime('fecha_creacion')
            ->allowEmpty('fecha_creacion');

        $validator
            ->scalar('nombre_elaboro')
            ->maxLength('nombre_elaboro', 45)
            ->allowEmpty('nombre_elaboro');

        $validator
            ->integer('id_condominio')
            ->allowEmpty('id_condominio');

        $validator
            ->boolean('estancia')
            ->allowEmpty('estancia');

        $validator
            ->scalar('imagen_estancia')
            ->maxLength('imagen_estancia', 100)
            ->allowEmpty('imagen_estancia');

        $validator
            ->scalar('observaciones_estancia')
            ->allowEmpty('observaciones_estancia');

        $validator
            ->boolean('escalera')
            ->allowEmpty('escalera');

        $validator
            ->scalar('observaciones_escalera')
            ->allowEmpty('observaciones_escalera');

        $validator
            ->scalar('imagen_escalera')
            ->maxLength('imagen_escalera', 100)
            ->allowEmpty('imagen_escalera');

        $validator
            ->boolean('pasillo')
            ->allowEmpty('pasillo');

        $validator
            ->scalar('imagen_pasillo')
            ->maxLength('imagen_pasillo', 100)
            ->allowEmpty('imagen_pasillo');

        $validator
            ->scalar('observaciones_pasillo')
            ->allowEmpty('observaciones_pasillo');

        $validator
            ->boolean('elevadores')
            ->allowEmpty('elevadores');

        $validator
            ->scalar('observaciones_elevadores')
            ->allowEmpty('observaciones_elevadores');

        $validator
            ->boolean('planta_luz')
            ->allowEmpty('planta_luz');

        $validator
            ->scalar('imagen_planta_luz')
            ->maxLength('imagen_planta_luz', 45)
            ->allowEmpty('imagen_planta_luz');

        $validator
            ->scalar('observaciones_planta_luz')
            ->allowEmpty('observaciones_planta_luz');

        $validator
            ->boolean('cisterna')
            ->allowEmpty('cisterna');

        $validator
            ->scalar('imagen_cisterna')
            ->maxLength('imagen_cisterna', 45)
            ->allowEmpty('imagen_cisterna');

        $validator
            ->scalar('observaciones_cisterna')
            ->maxLength('observaciones_cisterna', 45)
            ->allowEmpty('observaciones_cisterna');

        $validator
            ->boolean('bomba')
            ->allowEmpty('bomba');

        $validator
            ->scalar('imagen_bomba')
            ->maxLength('imagen_bomba', 45)
            ->allowEmpty('imagen_bomba');

        $validator
            ->scalar('observaciones_bomba')
            ->allowEmpty('observaciones_bomba');

        $validator
            ->boolean('estacionamiento')
            ->allowEmpty('estacionamiento');

        $validator
            ->scalar('imagen_estacionamiento')
            ->maxLength('imagen_estacionamiento', 45)
            ->allowEmpty('imagen_estacionamiento');

        $validator
            ->scalar('observaciones_estacionamiento')
            ->allowEmpty('observaciones_estacionamiento');

        $validator
            ->boolean('caldera')
            ->allowEmpty('caldera');

        $validator
            ->scalar('imagen_caldera')
            ->maxLength('imagen_caldera', 45)
            ->allowEmpty('imagen_caldera');

        $validator
            ->scalar('observaciones_caldera')
            ->allowEmpty('observaciones_caldera');

        $validator
            ->boolean('gimnasio')
            ->allowEmpty('gimnasio');

        $validator
            ->scalar('imagen_gimnasio')
            ->maxLength('imagen_gimnasio', 45)
            ->allowEmpty('imagen_gimnasio');

        $validator
            ->scalar('observaciones_gimnasio')
            ->maxLength('observaciones_gimnasio', 45)
            ->allowEmpty('observaciones_gimnasio');

        $validator
            ->boolean('salon')
            ->allowEmpty('salon');

        $validator
            ->scalar('imagen_salon')
            ->maxLength('imagen_salon', 45)
            ->allowEmpty('imagen_salon');

        $validator
            ->scalar('observaciones_salon')
            ->allowEmpty('observaciones_salon');

        $validator
            ->boolean('roof')
            ->allowEmpty('roof');

        $validator
            ->scalar('imagen_roof')
            ->maxLength('imagen_roof', 45)
            ->allowEmpty('imagen_roof');

        $validator
            ->scalar('observaciones_roof')
            ->allowEmpty('observaciones_roof');

        return $validator;
    }
}
