<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * TipoPago Controller
 *
 * @property \App\Model\Table\TipoPagoTable $TipoPago
 *
 * @method \App\Model\Entity\TipoPago[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TipoPagoController extends AppController
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
        //$tipoPago = $this->paginate($this->TipoPago);
        //$this->set(compact('tipoPago'));

        $tipoPagoTable = TableRegistry::get('tipo_pago');
        $tipoPago = $tipoPagoTable->find()
            ->where(['id_condominio' => $id_condominio]);

        $this->set('tipoPago', $this->paginate($tipoPago));
        $this->set('_serialize', ['tipoPago']);

        $this->set('id_condominio', $id_condominio);

    }

    /**
     * View method
     *
     * @param string|null $id Tipo Pago id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $tipoPago = $this->TipoPago->get($id, [
            'contain' => []
        ]);

        $this->set('tipoPago', $tipoPago);
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

        $tipoPago = $this->TipoPago->newEntity();
        if ($this->request->is('post')) {
            $tipoPago = $this->TipoPago->patchEntity($tipoPago, $this->request->getData());
            if ($this->TipoPago->save($tipoPago)) {
                $this->Flash->success(__('The tipo pago has been saved.'));

                return $this->redirect(['action' => 'index', 'id'=>$id_condominio]);
            }
            $this->Flash->error(__('The tipo pago could not be saved. Please, try again.'));
        }
        $this->set(compact('tipoPago', 'condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Tipo Pago id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null, $id_condominio = null)
    {
        $this->set('id_condominio', $id_condominio);

        $tipoPago = $this->TipoPago->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $tipoPago = $this->TipoPago->patchEntity($tipoPago, $this->request->getData());
            if ($this->TipoPago->save($tipoPago)) {
                $this->Flash->success(__('The tipo pago has been saved.'));

                return $this->redirect(['action' => 'index', 'id'=>$id_condominio]);
            }
            $this->Flash->error(__('The tipo pago could not be saved. Please, try again.'));
        }
        $this->set(compact('tipoPago'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Tipo Pago id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $tipoPago = $this->TipoPago->get($id);
        if ($this->TipoPago->delete($tipoPago)) {
            $this->Flash->success(__('The tipo pago has been deleted.'));
        } else {
            $this->Flash->error(__('The tipo pago could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
