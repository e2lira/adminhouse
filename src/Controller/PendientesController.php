<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * Pendientes Controller
 *
 * @property \App\Model\Table\PendientesTable $Pendientes
 *
 * @method \App\Model\Entity\Pendiente[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class PendientesController extends AppController
{

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $pendientes = $this->paginate($this->Pendientes);

        $this->set(compact('pendientes'));
    }

    /**
     * View method
     *
     * @param string|null $id Pendiente id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $pendiente = $this->Pendientes->get($id, [
            'contain' => []
        ]);

        $this->set('pendiente', $pendiente);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $pendiente = $this->Pendientes->newEntity();
        if ($this->request->is('post')) {
            $pendiente = $this->Pendientes->patchEntity($pendiente, $this->request->getData());
            if ($this->Pendientes->save($pendiente)) {
                $this->Flash->success(__('The pendiente has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The pendiente could not be saved. Please, try again.'));
        }
        $this->set(compact('pendiente'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Pendiente id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $pendiente = $this->Pendientes->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $pendiente = $this->Pendientes->patchEntity($pendiente, $this->request->getData());
            if ($this->Pendientes->save($pendiente)) {
                $this->Flash->success(__('The pendiente has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The pendiente could not be saved. Please, try again.'));
        }
        $this->set(compact('pendiente'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Pendiente id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $pendiente = $this->Pendientes->get($id);
        if ($this->Pendientes->delete($pendiente)) {
            $this->Flash->success(__('The pendiente has been deleted.'));
        } else {
            $this->Flash->error(__('The pendiente could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
