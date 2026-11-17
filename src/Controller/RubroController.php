<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * Rubro Controller
 *
 * @property \App\Model\Table\RubroTable $Rubro
 *
 * @method \App\Model\Entity\Rubro[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class RubroController extends AppController
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
    public function index()
    {
        $rubro = $this->paginate($this->Rubro);

        $this->set(compact('rubro'));
    }

    /**
     * View method
     *
     * @param string|null $id Rubro id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $rubro = $this->Rubro->get($id, [
            'contain' => []
        ]);

        $this->set('rubro', $rubro);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $rubro = $this->Rubro->newEntity();
        if ($this->request->is('post')) {
            $rubro = $this->Rubro->patchEntity($rubro, $this->request->getData());
            if ($this->Rubro->save($rubro)) {
                $this->Flash->success(__('The rubro has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The rubro could not be saved. Please, try again.'));
        }
        $this->set(compact('rubro'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Rubro id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $rubro = $this->Rubro->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $rubro = $this->Rubro->patchEntity($rubro, $this->request->getData());
            if ($this->Rubro->save($rubro)) {
                $this->Flash->success(__('The rubro has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The rubro could not be saved. Please, try again.'));
        }
        $this->set(compact('rubro'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Rubro id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $rubro = $this->Rubro->get($id);
        if ($this->Rubro->delete($rubro)) {
            $this->Flash->success(__('The rubro has been deleted.'));
        } else {
            $this->Flash->error(__('The rubro could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
