<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Asigna Controller
 *
 * @property \App\Model\Table\AsignaTable $Asigna
 *
 * @method \App\Model\Entity\Asigna[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class AsignaController extends AppController
{
    public function isAuthorized($user) {

        // Admin allowed anywhere
        if (isset($user['id_perfil']) && $user['id_perfil'] === 1) {
            return true;
        }

        return false;

    }
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $asigna = $this->paginate($this->Asigna);

        $this->set(compact('asigna'));
    }

    /**
     * View method
     *
     * @param string|null $id Asigna id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $asigna = $this->Asigna->get($id, [
            'contain' => [],
        ]);

        $this->set('asigna', $asigna);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add($id_condomino = null)
    {
        $this->loadModel('users');
        $users = $this->users->find('list', ['keyfield'=>'id_user',
            'valueField'=> 'username']);
        $this->set('users', $users);

        $condominioTable = TableRegistry::get('condominio');
        $condominio = $condominioTable->find()->first();
        $this->set('condominio', $condominio);

        $condominoTable = TableRegistry::get('Condomino');
        $condomino = $condominoTable->get($id_condomino,[
            'contain'=>[]
        ]);

        $asigna = $this->Asigna->newEntity();
        if ($this->request->is('post')) {
            $asigna = $this->Asigna->patchEntity($asigna, $this->request->getData());
            if ($this->Asigna->save($asigna)) {
                $this->Flash->success(__('The asigna has been saved.'));
                return $this->redirect(['controller'=>'Condominio','action' => 'index']);
            }
                $this->Flash->error(__('The asigna could not be saved. Please, try again.'));

        }
        $this->set(compact('asigna','condomino'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Asigna id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $asigna = $this->Asigna->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $asigna = $this->Asigna->patchEntity($asigna, $this->request->getData());
            if ($this->Asigna->save($asigna)) {
                $this->Flash->success(__('The asigna has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The asigna could not be saved. Please, try again.'));
        }
        $this->set(compact('asigna'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Asigna id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $asigna = $this->Asigna->get($id);
        if ($this->Asigna->delete($asigna)) {
            $this->Flash->success(__('The asigna has been deleted.'));
        } else {
            $this->Flash->error(__('The asigna could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
