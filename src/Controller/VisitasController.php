<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * Visitas Controller
 *
 * @property \App\Model\Table\VisitasTable $Visitas
 *
 * @method \App\Model\Entity\Visita[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class VisitasController extends AppController
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
        $visitas = $this->paginate($this->Visitas);

        $this->set(compact('visitas'));
        $this->set('id_condominio', $id_condominio);

    }

    /**
     * View method
     *
     * @param string|null $id Visita id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {

        $visita = $this->Visitas->get($id, [
            'contain' => [],
        ]);

        $this->set('visita', $visita);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->loadModel('tipo_visita'); //if it's not already loaded
        $optionsTipo = $this->tipo_visita->find('list', ['keyField' => 'tipo_visita',
            'valueField' => 'tipo_visita']);
        $this->set('optionsTipo', $optionsTipo);


        $visita = $this->Visitas->newEntity();
        if ($this->request->is('post')) {
            $visita = $this->Visitas->patchEntity($visita, $this->request->getData());
            if ($this->Visitas->save($visita)) {
                $this->Flash->success(__('The visita has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The visita could not be saved. Please, try again.'));
        }
        $this->set(compact('visita'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Visita id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $visita = $this->Visitas->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $visita = $this->Visitas->patchEntity($visita, $this->request->getData());
            if ($this->Visitas->save($visita)) {
                $this->Flash->success(__('The visita has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The visita could not be saved. Please, try again.'));
        }
        $this->set(compact('visita'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Visita id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $visita = $this->Visitas->get($id);
        if ($this->Visitas->delete($visita)) {
            $this->Flash->success(__('The visita has been deleted.'));
        } else {
            $this->Flash->error(__('The visita could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
