<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * TipoGasto Controller
 *
 * @property \App\Model\Table\TipoGastoTable $TipoGasto
 *
 * @method \App\Model\Entity\TipoGasto[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TipoGastoController extends AppController
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
        //$tipoGasto = $this->paginate($this->TipoGasto);
        //$this->set(compact('tipoGasto'));

        $tipoGastoTable = TableRegistry::get('tipo_gasto');
        $tipoGasto = $tipoGastoTable->find()
            ->where(['id_condominio' => $id_condominio]);

        $this->set('tipoGasto', $this->paginate($tipoGasto));
        $this->set('_serialize', ['tipoGasto']);

        $this->set('id_condominio', $id_condominio);

    }

    /**
     * View method
     *
     * @param string|null $id Tipo Gasto id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $tipoGasto = $this->TipoGasto->get($id, [
            'contain' => []
        ]);

        $this->set('tipoGasto', $tipoGasto);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add($id_condominio = null)
    {
        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);

        $this->set('id_condominio', $id_condominio);

        $tipoGasto = $this->TipoGasto->newEntity();
        if ($this->request->is('post')) {
            $tipoGasto = $this->TipoGasto->patchEntity($tipoGasto, $this->request->getData());
            if ($this->TipoGasto->save($tipoGasto)) {
                $this->Flash->success(__('The tipo gasto has been saved.'));

                return $this->redirect(['action' => 'index', 'id'=>$id_condominio]);
            }
            $this->Flash->error(__('The tipo gasto could not be saved. Please, try again.'));
        }
        $this->set(compact('tipoGasto', 'condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Tipo Gasto id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $tipoGasto = $this->TipoGasto->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $tipoGasto = $this->TipoGasto->patchEntity($tipoGasto, $this->request->getData());
            if ($this->TipoGasto->save($tipoGasto)) {
                $this->Flash->success(__('The tipo gasto has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The tipo gasto could not be saved. Please, try again.'));
        }
        $this->set(compact('tipoGasto'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Tipo Gasto id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $tipoGasto = $this->TipoGasto->get($id);
        if ($this->TipoGasto->delete($tipoGasto)) {
            $this->Flash->success(__('The tipo gasto has been deleted.'));
        } else {
            $this->Flash->error(__('The tipo gasto could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
