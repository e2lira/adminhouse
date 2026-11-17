<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * Favor Controller
 *
 * @property \App\Model\Table\FavorTable $Favor
 *
 * @method \App\Model\Entity\Favor[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class FavorController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $favor = $this->paginate($this->Favor);

        $this->set(compact('favor'));
    }

    /**
     * View method
     *
     * @param string|null $id Favor id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $favor = $this->Favor->get($id, [
            'contain' => [],
        ]);

        $this->set('favor', $favor);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $favor = $this->Favor->newEntity();
        if ($this->request->is('post')) {
            $favor = $this->Favor->patchEntity($favor, $this->request->getData());
            if ($this->Favor->save($favor)) {
                $this->Flash->success(__('The favor has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The favor could not be saved. Please, try again.'));
        }
        $this->set(compact('favor'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Favor id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $favor = $this->Favor->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $favor = $this->Favor->patchEntity($favor, $this->request->getData());
            if ($this->Favor->save($favor)) {
                $this->Flash->success(__('The favor has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The favor could not be saved. Please, try again.'));
        }
        $this->set(compact('favor'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Favor id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $favor = $this->Favor->get($id);
        if ($this->Favor->delete($favor)) {
            $this->Flash->success(__('The favor has been deleted.'));
        } else {
            $this->Flash->error(__('The favor could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
