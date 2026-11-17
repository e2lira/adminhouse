<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * TipoCuota Controller
 *
 * @property \App\Model\Table\TipoCuotaTable $TipoCuota
 *
 * @method \App\Model\Entity\TipoCuotum[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TipoCuotaController extends AppController
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
        //$tipoCuota = $this->paginate($this->TipoCuota);
        //$this->set(compact('tipoCuota'));

        $tipoCuotaTable = TableRegistry::get('tipo_cuota');
        $tipoCuota = $tipoCuotaTable->find()
            ->where(['id_condominio' => $id_condominio]);

        $this->set('tipoCuota', $this->paginate($tipoCuota));
        $this->set('_serialize', ['tipoCuota']);

        $this->set('id_condominio', $id_condominio);
    }

    /**
     * View method
     *
     * @param string|null $id Tipo Cuotum id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $tipoCuotum = $this->TipoCuota->get($id, [
            'contain' => []
        ]);

        $this->set('tipoCuotum', $tipoCuotum);
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


        $tipoCuotum = $this->TipoCuota->newEntity();
        if ($this->request->is('post')) {
            $tipoCuotum = $this->TipoCuota->patchEntity($tipoCuotum, $this->request->getData());
            if ($this->TipoCuota->save($tipoCuotum)) {
                $this->Flash->success(__('The tipo cuotum has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The tipo cuotum could not be saved. Please, try again.'));
        }
        $this->set(compact('tipoCuotum', 'condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Tipo Cuotum id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $tipoCuotum = $this->TipoCuota->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $tipoCuotum = $this->TipoCuota->patchEntity($tipoCuotum, $this->request->getData());
            if ($this->TipoCuota->save($tipoCuotum)) {
                $this->Flash->success(__('The tipo cuotum has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The tipo cuotum could not be saved. Please, try again.'));
        }
        $this->set(compact('tipoCuotum'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Tipo Cuotum id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $tipoCuotum = $this->TipoCuota->get($id);
        if ($this->TipoCuota->delete($tipoCuotum)) {
            $this->Flash->success(__('The tipo cuotum has been deleted.'));
        } else {
            $this->Flash->error(__('The tipo cuotum could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
