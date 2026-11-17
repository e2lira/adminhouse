<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * Indiviso Controller
 *
 * @property \App\Model\Table\IndivisoTable $Indiviso
 *
 * @method \App\Model\Entity\Indiviso[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class IndivisoController extends AppController
{

    public function isAuthorized($user) {

        // Admin allowed anywhere
        if (isset($user['id_perfil']) && $user['id_perfil'] === 1) {
            return true;
        }

        if (isset($user['id_perfil']) && $user['id_perfil'] === 2) {
            return true;
        }

        if (isset($user['id_perfil']) && $user['id_perfil'] === 4) {
            return true;
        }

        return false;

    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index($id_condominio = null)
    {
        //$indiviso = $this->paginate($this->Indiviso);
        //$this->set(compact('indiviso'));


        $query = $this->Indiviso
            ->find()
            ->select([
                'id_cuotas',
                'condominio' => 'c.nombre_condominio',
                'tipo' => 'd.tipo_cuota',
                'cuota',
                'cuota_letra',
                'fecha_cargo',
                'fecha',
                'concepto',
                'departamento'=>'e.numero_departamento'

            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condominio', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condominio = Indiviso.id_condominio',
                ]
            ])
            ->join([
                'd' => [
                    'table' => 'tipo_cuota', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_tipo = Indiviso.id_tipo',
                ]
            ])
            ->join([
                'e' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => \App\Database\SqlDialect::forConnection()->integerAsText('e.id_condomino') . ' = Indiviso.id_condomino',
                ]
            ])
            ->where(['Indiviso.id_condominio'=>$id_condominio]);

        $this->set('indiviso', $this->paginate($query));
        $this->set('_serialize', ['indiviso']);
    }

    /**
     * View method
     *
     * @param string|null $id Indiviso id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $indiviso = $this->Indiviso->get($id, [
            'contain' => [],
        ]);

        $this->set('indiviso', $indiviso);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add($id_condominio = null)
    {
        $this->loadModel('tipoCuota');
        $options = $this->tipoCuota->find('list', ['keyField' => 'id_tipo',
            'valueField' => 'tipo_cuota']);
        $this->set('options', $options);

        $this->loadModel('condomino');
        $condomino = $this->condomino
            ->find('list', ['keyField' => 'id_condomino',
            'valueField' => 'numero_departamento'])
            ->where(['id_condominio'=> $id_condominio]);
        $this->set('condomino', $condomino);


        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);

        $indiviso = $this->Indiviso->newEntity();
        if ($this->request->is('post')) {
            $indiviso = $this->Indiviso->patchEntity($indiviso, $this->request->getData());
            if ($this->Indiviso->save($indiviso)) {

                $activo = 1;
                $adeudo = TableRegistry::get('Adeudos');
                $adeudo->query()
                    ->insert(['id_condominio', 'id_condomino','id_tipo' ,'mes' ,'monto', 'concepto'])
                    ->values([
                        'id_condominio' => $indiviso->id_condominio,
                        'id_condomino' => $indiviso->id_condomino,
                        'id_tipo' => $indiviso->id_tipo,
                        'monto' => $indiviso->cuota,
                        'concepto' => $indiviso->concepto,
                        'mes' => $indiviso->fecha,
                        'estatus'=> $activo,
                    ])
                    ->execute();

                $this->Flash->success(__('The indiviso has been saved.'));

                return $this->redirect($this->referer());
            }
            $this->Flash->error(__('The indiviso could not be saved. Please, try again.'));
        }
        $this->set(compact('indiviso','condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Indiviso id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $indiviso = $this->Indiviso->get($id, [
            'contain' => [],
        ]);

        $this->loadModel('tipoCuota');
        $optionCuota = $this->tipoCuota->find('list', ['keyField' => 'id_tipo',
            'valueField' => 'tipo_cuota'])
            ->where(['id_tipo !=' => 1]);
        $this->set('optionCuota', $optionCuota);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $indiviso = $this->Indiviso->patchEntity($indiviso, $this->request->getData());
            if ($this->Indiviso->save($indiviso)) {
                $this->Flash->success(__('The indiviso has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The indiviso could not be saved. Please, try again.'));
        }
        $this->set(compact('indiviso'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Indiviso id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $indiviso = $this->Indiviso->get($id);
        if ($this->Indiviso->delete($indiviso)) {
            $adeudo = TableRegistry::get('Adeudos');
            $adeudo->deleteAll(
                [
                    'mes' => $indiviso->fecha,
                    'id_condominio' => $indiviso->id_condominio,
                    'id_condomino' => $indiviso->id_condomino,
                ]
            );
            $this->Flash->success(__('The indiviso has been deleted.'));
        } else {
            $this->Flash->error(__('The indiviso could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'add']);
    }


    public function borrar($id = null){
        $indiviso = $this->Indiviso->get($id);
        if ($this->Indiviso->delete($indiviso)){
            $adeudo = TableRegistry::get('Adeudos');
            $adeudo->deleteAll(
                [
                    'mes' => $indiviso->fecha,
                    'id_condominio' => $indiviso->id_condominio,
                    'concepto' => $indiviso->concepto,
                ]
            );
            $message = [['message' => 'Se borro con éxito el condominio - ' . $indiviso->id_cuotas . ' - ', 'result' => 'ok']];
        } else {
            $message = [['message' => 'Error al borrar los datos', 'result' => 'error']];
            Log::debug($indiviso->errors());
        }

        $resultJ = json_encode($message);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;

    }
}
