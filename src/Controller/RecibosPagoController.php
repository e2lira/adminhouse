<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * RecibosPago Controller
 *
 * @property \App\Model\Table\RecibosPagoTable $RecibosPago
 *
 * @method \App\Model\Entity\RecibosPago[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class RecibosPagoController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $recibosPago = $this->paginate($this->RecibosPago);

        $this->set(compact('recibosPago'));
    }

    /**
     * View method
     *
     * @param string|null $id Recibos Pago id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $recibosPago = $this->RecibosPago->get($id, [
            'contain' => [],
        ]);

        $this->set('recibosPago', $recibosPago);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $recibosPago = $this->RecibosPago->newEntity();
        if ($this->request->is('post')) {
            $recibosPago = $this->RecibosPago->patchEntity($recibosPago, $this->request->getData());
            if ($this->RecibosPago->save($recibosPago)) {
                $this->Flash->success(__('The recibos pago has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The recibos pago could not be saved. Please, try again.'));
        }
        $this->set(compact('recibosPago'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Recibos Pago id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $recibosPago = $this->RecibosPago->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $recibosPago = $this->RecibosPago->patchEntity($recibosPago, $this->request->getData());
            if ($this->RecibosPago->save($recibosPago)) {
                $this->Flash->success(__('The recibos pago has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The recibos pago could not be saved. Please, try again.'));
        }
        $this->set(compact('recibosPago'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Recibos Pago id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $recibosPago = $this->RecibosPago->get($id);
        if ($this->RecibosPago->delete($recibosPago)) {
            $this->Flash->success(__('The recibos pago has been deleted.'));
        } else {
            $this->Flash->error(__('The recibos pago could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
