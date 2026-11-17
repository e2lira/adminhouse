<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * SaldoCierre Controller
 *
 * @property \App\Model\Table\SaldoCierreTable $SaldoCierre
 *
 * @method \App\Model\Entity\SaldoCierre[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class SaldoCierreController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $saldoCierre = $this->paginate($this->SaldoCierre);

        $this->set(compact('saldoCierre'));
    }

    /**
     * View method
     *
     * @param string|null $id Saldo Cierre id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $saldoCierre = $this->SaldoCierre->get($id, [
            'contain' => [],
        ]);

        $this->set('saldoCierre', $saldoCierre);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $saldoCierre = $this->SaldoCierre->newEntity();
        if ($this->request->is('post')) {
            $saldoCierre = $this->SaldoCierre->patchEntity($saldoCierre, $this->request->getData());
            if ($this->SaldoCierre->save($saldoCierre)) {
                $this->Flash->success(__('The saldo cierre has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The saldo cierre could not be saved. Please, try again.'));
        }
        $this->set(compact('saldoCierre'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Saldo Cierre id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $saldoCierre = $this->SaldoCierre->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $saldoCierre = $this->SaldoCierre->patchEntity($saldoCierre, $this->request->getData());
            if ($this->SaldoCierre->save($saldoCierre)) {
                $this->Flash->success(__('The saldo cierre has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The saldo cierre could not be saved. Please, try again.'));
        }
        $this->set(compact('saldoCierre'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Saldo Cierre id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $saldoCierre = $this->SaldoCierre->get($id);
        if ($this->SaldoCierre->delete($saldoCierre)) {
            $this->Flash->success(__('The saldo cierre has been deleted.'));
        } else {
            $this->Flash->error(__('The saldo cierre could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
