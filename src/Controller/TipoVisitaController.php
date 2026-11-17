<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * TipoVisita Controller
 *
 * @property \App\Model\Table\TipoVisitaTable $TipoVisita
 *
 * @method \App\Model\Entity\TipoVisitum[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TipoVisitaController extends AppController
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
     * @return \Cake\Http\Response|null
     */
    public function index($id_condominio = null)
    {
        $tipoVisita = $this->paginate($this->TipoVisita);

        $this->set(compact('tipoVisita'));
        $this->set('id_condominio', $id_condominio);

    }

    /**
     * View method
     *
     * @param string|null $id Tipo Visitum id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $tipoVisitum = $this->TipoVisita->get($id, [
            'contain' => [],
        ]);

        $this->set('tipoVisitum', $tipoVisitum);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $tipoVisitum = $this->TipoVisita->newEntity();
        if ($this->request->is('post')) {
            $tipoVisitum = $this->TipoVisita->patchEntity($tipoVisitum, $this->request->getData());
            if ($this->TipoVisita->save($tipoVisitum)) {
                $this->Flash->success(__('The tipo visitum has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The tipo visitum could not be saved. Please, try again.'));
        }
        $this->set(compact('tipoVisitum'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Tipo Visitum id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $tipoVisitum = $this->TipoVisita->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $tipoVisitum = $this->TipoVisita->patchEntity($tipoVisitum, $this->request->getData());
            if ($this->TipoVisita->save($tipoVisitum)) {
                $this->Flash->success(__('The tipo visitum has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The tipo visitum could not be saved. Please, try again.'));
        }
        $this->set(compact('tipoVisitum'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Tipo Visitum id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $tipoVisitum = $this->TipoVisita->get($id);
        if ($this->TipoVisita->delete($tipoVisitum)) {
            $this->Flash->success(__('The tipo visitum has been deleted.'));
        } else {
            $this->Flash->error(__('The tipo visitum could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
