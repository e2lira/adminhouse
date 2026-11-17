<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * UsuarioCondomino Controller
 *
 * @property \App\Model\Table\UsuarioCondominoTable $UsuarioCondomino
 *
 * @method \App\Model\Entity\UsuarioCondomino[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class UsuarioCondominoController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $usuarioCondomino = $this->paginate($this->UsuarioCondomino);

        $this->set(compact('usuarioCondomino'));
    }

    /**
     * View method
     *
     * @param string|null $id Usuario Condomino id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $usuarioCondomino = $this->UsuarioCondomino->get($id, [
            'contain' => [],
        ]);

        $this->set('usuarioCondomino', $usuarioCondomino);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $usuarioCondomino = $this->UsuarioCondomino->newEntity();
        if ($this->request->is('post')) {
            $usuarioCondomino = $this->UsuarioCondomino->patchEntity($usuarioCondomino, $this->request->getData());
            if ($this->UsuarioCondomino->save($usuarioCondomino)) {
                $this->Flash->success(__('The usuario condomino has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The usuario condomino could not be saved. Please, try again.'));
        }
        $this->set(compact('usuarioCondomino'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Usuario Condomino id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $usuarioCondomino = $this->UsuarioCondomino->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $usuarioCondomino = $this->UsuarioCondomino->patchEntity($usuarioCondomino, $this->request->getData());
            if ($this->UsuarioCondomino->save($usuarioCondomino)) {
                $this->Flash->success(__('The usuario condomino has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The usuario condomino could not be saved. Please, try again.'));
        }
        $this->set(compact('usuarioCondomino'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Usuario Condomino id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $usuarioCondomino = $this->UsuarioCondomino->get($id);
        if ($this->UsuarioCondomino->delete($usuarioCondomino)) {
            $this->Flash->success(__('The usuario condomino has been deleted.'));
        } else {
            $this->Flash->error(__('The usuario condomino could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
