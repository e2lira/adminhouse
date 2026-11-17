<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Recordatorio Model
 *
 * @method \App\Model\Entity\Recordatorio get($primaryKey, $options = [])
 * @method \App\Model\Entity\Recordatorio newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Recordatorio[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Recordatorio|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Recordatorio saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Recordatorio patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Recordatorio[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Recordatorio findOrCreate($search, callable $callback = null, $options = [])
 */
class RecordatorioTable extends Table
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

        $this->setTable('recordatorio');
        $this->setDisplayField('id_recordatorio');
        $this->setPrimaryKey('id_recordatorio');

        $this->addBehavior('Josegonzalez/Upload.Upload', [
            'adjunto'=> []
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
            ->integer('id_recordatorio')
            ->allowEmptyString('id_recordatorio', null, 'create');

        $validator
            ->scalar('destinatarios')
            ->maxLength('destinatarios', 100)
            ->allowEmptyString('destinatarios');

        $validator
            ->scalar('asunto')
            ->maxLength('asunto', 45)
            ->allowEmptyString('asunto');

        $validator
            ->scalar('mensaje')
            ->allowEmptyString('mensaje');

        $validator
            ->scalar('adjunto')
            ->maxLength('adjunto', 255)
            ->allowEmptyString('adjunto');

        $validator
            ->scalar('dir')
            ->maxLength('dir', 255)
            ->allowEmptyString('dir');

        return $validator;
    }
}
