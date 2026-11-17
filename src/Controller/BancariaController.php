<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Bancaria Controller
 *
 * @property \App\Model\Table\BancariaTable $Bancaria
 *
 * @method \App\Model\Entity\Bancarium[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class BancariaController extends AppController
{

    public function isAuthorized($user) {

        // Admin allowed anywhere
        if (isset($user['id_perfil']) && $user['id_perfil'] === 1) {
            return true;
        }

        if (isset($user['id_perfil']) && $user['id_perfil'] === 2) {
            return true;
        }

        if (isset($user['id_perfil']) && $user['id_perfil'] === 3) {
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
        //$bancaria = $this->paginate($this->Bancaria);
        //$this->set(compact('bancaria'));

        $query = $this->Bancaria
            ->find()
            ->select([
                'id_bancaria',
                'id_condominio',
                'nombre_banco',
                'no_cuenta',
                'estatus'

            ])
            ->where([
                'id_condominio' => $id_condominio
            ]);
        //   $pago = $this->paginate($query);
        //   $this->set(compact('pago'));
        //   $pago = $this->paginate($query);
        //   debug($query);
        $this->set('bancaria', $this->paginate($query));
        $this->set('_serialize', ['bancaria']);
        $this->set('id_condominio', $id_condominio);
    }

    /**
     * View method
     *
     * @param string|null $id Bancarium id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $bancarium = $this->Bancaria->get($id, [
            'contain' => []
        ]);

        $this->set('bancarium', $bancarium);
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
        $bancarium = $this->Bancaria->newEntity();
        if ($this->request->is('post')) {
            $bancarium = $this->Bancaria->patchEntity($bancarium, $this->request->getData());
            if ($this->Bancaria->save($bancarium)) {
                $this->Flash->success(__('The bancarium has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The bancarium could not be saved. Please, try again.'));
        }
        $this->set(compact('bancarium', 'condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Bancarium id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $bancarium = $this->Bancaria->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $bancarium = $this->Bancaria->patchEntity($bancarium, $this->request->getData());
            if ($this->Bancaria->save($bancarium)) {
                $this->Flash->success(__('The bancarium has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The bancarium could not be saved. Please, try again.'));
        }
        $this->set(compact('bancarium'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Bancarium id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $bancarium = $this->Bancaria->get($id);
        if ($this->Bancaria->delete($bancarium)) {
            $this->Flash->success(__('The bancarium has been deleted.'));
        } else {
            $this->Flash->error(__('The bancarium could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
