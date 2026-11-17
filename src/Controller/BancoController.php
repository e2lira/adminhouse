<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * Banco Controller
 *
 * @property \App\Model\Table\BancoTable $Banco
 *
 * @method \App\Model\Entity\Banco[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class BancoController extends AppController
{
    public function isAuthorized($user) {

        // Admin allowed anywhere
        if (isset($user['id_perfil']) && $user['id_perfil'] === 1) {
            return true;
        }

        if (isset($user['id_perfil']) && $user['id_perfil'] === 2) {
            return true;
        }

        if (isset($user['id_perfil']) && $user['id_perfil'] === 4) {
            return true;
        }

        return false;

    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index($id_condomino = null)
    {
        $id_user = $this->request->session()->read('Auth.User.id_user');
        $query = $this->Banco
            ->find()
            ->select([
                'id_movimiento',
                'condominio' => 'nombre_condominio',
                'cargo',
                'abono',
                'cargo',
                'nombre_banco',
                'no_cuenta',
                'fecha'

            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'uc' => [
                    'table' => 'condominio', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'uc.id_condominio = Banco.id_condominio',
                ]

            ])
            ->where([
                'uc.id_condominio'=>$id_condomino
            ]);

        $this->set('id_condominio', $id_condomino);
        $this->set('banco', $this->paginate($query));
        $this->set('_serialize', ['banco']);
    }

    /**
     * View method
     *
     * @param string|null $id Banco id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $banco = $this->Banco->get($id, [
            'contain' => []
        ]);

        $this->set('banco', $banco);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $banco = $this->Banco->newEntity();
        if ($this->request->is('post')) {
            $banco = $this->Banco->patchEntity($banco, $this->request->getData());
            if ($this->Banco->save($banco)) {
                $this->Flash->success(__('The banco has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The banco could not be saved. Please, try again.'));
        }
        $this->set(compact('banco'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Banco id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $banco = $this->Banco->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $banco = $this->Banco->patchEntity($banco, $this->request->getData());
            if ($this->Banco->save($banco)) {
                $this->Flash->success(__('The banco has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The banco could not be saved. Please, try again.'));
        }
        $this->set(compact('banco'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Banco id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $banco = $this->Banco->get($id);
        if ($this->Banco->delete($banco)) {
            $this->Flash->success(__('The banco has been deleted.'));
        } else {
            $this->Flash->error(__('The banco could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
