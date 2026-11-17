<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * UsersCondominio Controller
 *
 * @property \App\Model\Table\UsersCondominioTable $UsersCondominio
 *
 * @method \App\Model\Entity\UsersCondominio[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class UsersCondominioController extends AppController
{

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $usersCondominio = $this->paginate($this->UsersCondominio);

        $this->set(compact('usersCondominio'));
    }

    /**
     * View method
     *
     * @param string|null $id Users Condominio id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $usersCondominio = $this->UsersCondominio->get($id, [
            'contain' => []
        ]);

        $this->set('usersCondominio', $usersCondominio);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $usersCondominio = $this->UsersCondominio->newEntity();
        if ($this->request->is('post')) {
            $usersCondominio = $this->UsersCondominio->patchEntity($usersCondominio, $this->request->getData());
            if ($this->UsersCondominio->save($usersCondominio)) {
                $this->Flash->success(__('The users condominio has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The users condominio could not be saved. Please, try again.'));
        }
        $this->set(compact('usersCondominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Users Condominio id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $usersCondominio = $this->UsersCondominio->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $usersCondominio = $this->UsersCondominio->patchEntity($usersCondominio, $this->request->getData());
            if ($this->UsersCondominio->save($usersCondominio)) {
                $this->Flash->success(__('The users condominio has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The users condominio could not be saved. Please, try again.'));
        }
        $this->set(compact('usersCondominio'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Users Condominio id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $usersCondominio = $this->UsersCondominio->get($id);
        if ($this->UsersCondominio->delete($usersCondominio)) {
            $this->Flash->success(__('The users condominio has been deleted.'));
        } else {
            $this->Flash->error(__('The users condominio could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
