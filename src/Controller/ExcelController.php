<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * Excel Controller
 *
 * @property \App\Model\Table\ExcelTable $Excel
 *
 * @method \App\Model\Entity\Excel[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ExcelController extends AppController
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
    public function index()
    {
        $excel = $this->paginate($this->Excel);

        $this->set(compact('excel'));
    }

    /**
     * View method
     *
     * @param string|null $id Excel id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $excel = $this->Excel->get($id, [
            'contain' => [],
        ]);

        $this->set('excel', $excel);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $excel = $this->Excel->newEntity();
        if ($this->request->is('post')) {
            $excel = $this->Excel->patchEntity($excel, $this->request->getData());
            if ($this->Excel->save($excel)) {
                $this->Flash->success(__('The excel has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The excel could not be saved. Please, try again.'));
        }
        $this->set(compact('excel'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Excel id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $excel = $this->Excel->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $excel = $this->Excel->patchEntity($excel, $this->request->getData());
            if ($this->Excel->save($excel)) {
                $this->Flash->success(__('The excel has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The excel could not be saved. Please, try again.'));
        }
        $this->set(compact('excel'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Excel id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $excel = $this->Excel->get($id);
        if ($this->Excel->delete($excel)) {
            $this->Flash->success(__('The excel has been deleted.'));
        } else {
            $this->Flash->error(__('The excel could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
