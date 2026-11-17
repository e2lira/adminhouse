<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Proveedores Controller
 *
 * @property \App\Model\Table\ProveedoresTable $Proveedores
 *
 * @method \App\Model\Entity\Proveedore[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ProveedoresController extends AppController
{
    public function isAuthorized($user) {

        // Admin allowed anywhere
        if (isset($user['id_perfil']) && $user['id_perfil'] === 1) {
            return true;
        }

        if (isset($user['id_perfil']) && $user['id_perfil'] === 2) {
            return true;
        }

        if (isset($user['id_perfil']) && $user['id_perfil'] === 5) {
            return true;
        }

        return false;

    }
    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index($id_condominio = null)
    {
        $query = $this->Proveedores
            ->find()
            ->select([
                'id_proveedores',
                'condominio' => 'c.nombre_condominio',
                'nom_proveedor',
                'no_cuenta_proveedor',
                'rubro_proveedor',
                'nota'

            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condominio', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condominio = Proveedores.id_condominio',
                ]
            ])
            ->where([
                'c.id_condominio' => $id_condominio
            ]);
        $this->set('proveedores', $this->paginate($query));
        $this->set('_serialize', ['proveedores']);
        $this->set('id_condominio', $id_condominio);
    }

    /**
     * View method
     *
     * @param string|null $id Proveedore id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $query = $this->Proveedores
            ->find()
            ->select([
                'id_proveedores',
                'condominio' => 'c.nombre_condominio',
                'nom_proveedor',
                'no_cuenta_proveedor',
                'rubro_proveedor',
                'nota'

            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condominio', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condominio = Proveedores.id_condominio',
                ]
            ]);
        $row = $query->first();


        $this->set('proveedore', $row);
        $this->set('_serialize', ['proveedore']);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add($id_condominio = null)
    {
        $proveedore = $this->Proveedores->newEntity();

        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);

        $this->loadModel('rubro'); //if it's not already loaded
        $options = $this->rubro->find('list', ['keyField' => 'id_rubro',
            'valueField' => 'rubro']);
        $this->set('options', $options);

        if ($this->request->is('post')) {
            $proveedore = $this->Proveedores->patchEntity($proveedore, $this->request->getData());
            if ($this->Proveedores->save($proveedore)) {
                $this->Flash->success(__('The proveedore has been saved.'));

                return $this->redirect(['action' => 'index', 'id'=>$id_condominio]);
            }
            $this->Flash->error(__('The proveedore could not be saved. Please, try again.'));
        }
        $this->set(compact('proveedore', 'condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Proveedore id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $proveedore = $this->Proveedores->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $proveedore = $this->Proveedores->patchEntity($proveedore, $this->request->getData());
            if ($this->Proveedores->save($proveedore)) {
                $this->Flash->success(__('The proveedore has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The proveedore could not be saved. Please, try again.'));
        }
        $this->set(compact('proveedore'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Proveedore id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $proveedore = $this->Proveedores->get($id);
        if ($this->Proveedores->delete($proveedore)) {
            $this->Flash->success(__('The proveedore has been deleted.'));
        } else {
            $this->Flash->error(__('The proveedore could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }



    public function borrar($id = null){
        $proveedore = $this->Proveedores->get($id);
        if ($this->Proveedores->delete($proveedore)){
            $message = [['message' => 'Se borro con éxito el proveedor - ' . $proveedore->id_proveedores . ' - ', 'result' => 'ok']];
        } else {
            $message = [['message' => 'Error al borrar los datos', 'result' => 'error']];
            Log::debug($proveedore->errors());
        }

        $resultJ = json_encode($message);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;

    }
}
