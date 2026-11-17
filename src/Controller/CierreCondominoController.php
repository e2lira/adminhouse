<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * CierreCondomino Controller
 *
 * @property \App\Model\Table\CierreCondominoTable $CierreCondomino
 *
 * @method \App\Model\Entity\CierreCondomino[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CierreCondominoController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $cierreCondomino = $this->paginate($this->CierreCondomino);

        $this->set(compact('cierreCondomino'));
    }

    /**
     * View method
     *
     * @param string|null $id Cierre Condomino id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $cierreCondomino = $this->CierreCondomino->get($id, [
            'contain' => [],
        ]);

        $this->set('cierreCondomino', $cierreCondomino);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $cierreCondomino = $this->CierreCondomino->newEntity();
        if ($this->request->is('post')) {
            $cierreCondomino = $this->CierreCondomino->patchEntity($cierreCondomino, $this->request->getData());
            if ($this->CierreCondomino->save($cierreCondomino)) {
                $this->Flash->success(__('The cierre condomino has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The cierre condomino could not be saved. Please, try again.'));
        }
        $this->set(compact('cierreCondomino'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Cierre Condomino id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $cierreCondomino = $this->CierreCondomino->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $cierreCondomino = $this->CierreCondomino->patchEntity($cierreCondomino, $this->request->getData());
            if ($this->CierreCondomino->save($cierreCondomino)) {
                $this->Flash->success(__('The cierre condomino has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The cierre condomino could not be saved. Please, try again.'));
        }
        $this->set(compact('cierreCondomino'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Cierre Condomino id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $cierreCondomino = $this->CierreCondomino->get($id);
        if ($this->CierreCondomino->delete($cierreCondomino)) {
            $this->Flash->success(__('The cierre condomino has been deleted.'));
        } else {
            $this->Flash->error(__('The cierre condomino could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
