<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Categoria Controller
 *
 * @property \App\Model\Table\CategoriaTable $Categoria
 *
 * @method \App\Model\Entity\Categorium[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CategoriaController extends AppController
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
        //$categoria = $this->paginate($this->Categoria);
        //$this->set(compact('categoria'));

        $categoriaTable = TableRegistry::get('categoria');
        $categoria = $categoriaTable->find()
            ->where(['id_condominio' => $id_condominio]);

        $this->set('categoria', $this->paginate($categoria));
        $this->set('_serialize', ['categoria']);

        $this->set('id_condominio', $id_condominio);
    }

    /**
     * View method
     *
     * @param string|null $id Categorium id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $categorium = $this->Categoria->get($id, [
            'contain' => [],
        ]);

        $this->set('categorium', $categorium);
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

        $categorium = $this->Categoria->newEntity();
        if ($this->request->is('post')) {
            $categorium = $this->Categoria->patchEntity($categorium, $this->request->getData());
            if ($this->Categoria->save($categorium)) {
                $this->Flash->success(__('The categorium has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The categorium could not be saved. Please, try again.'));
        }
        $this->set(compact('categorium', 'condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Categorium id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $categorium = $this->Categoria->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $categorium = $this->Categoria->patchEntity($categorium, $this->request->getData());
            if ($this->Categoria->save($categorium)) {
                $this->Flash->success(__('The categorium has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The categorium could not be saved. Please, try again.'));
        }
        $this->set(compact('categorium'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Categorium id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $categorium = $this->Categoria->get($id);
        if ($this->Categoria->delete($categorium)) {
            $this->Flash->success(__('The categorium has been deleted.'));
        } else {
            $this->Flash->error(__('The categorium could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
