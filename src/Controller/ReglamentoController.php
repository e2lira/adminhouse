<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Reglamento Controller
 *
 * @property \App\Model\Table\ReglamentoTable $Reglamento
 *
 * @method \App\Model\Entity\Reglamento[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ReglamentoController extends AppController
{
    public function isAuthorized($user) {

        // Admin allowed anywhere
        if (isset($user['id_perfil']) && $user['id_perfil'] === 1) {
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
        $reglamento = $this->paginate($this->Reglamento);

        $this->set(compact('reglamento'));
        $this->set('id_condominio', $id_condominio);

    }

    /**
     * View method
     *
     * @param string|null $id Reglamento id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $reglamento = $this->Reglamento->get($id, [
            'contain' => [],
        ]);

        $this->set('reglamento', $reglamento);
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

        $reglamento = $this->Reglamento->newEntity();
        if ($this->request->is('post')) {
            $reglamento = $this->Reglamento->patchEntity($reglamento, $this->request->getData());
            if ($this->Reglamento->save($reglamento)) {
                $this->Flash->success(__('The reglamento has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The reglamento could not be saved. Please, try again.'));
        }
        $this->set(compact('reglamento','condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Reglamento id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $reglamento = $this->Reglamento->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $reglamento = $this->Reglamento->patchEntity($reglamento, $this->request->getData());
            if ($this->Reglamento->save($reglamento)) {
                $this->Flash->success(__('The reglamento has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The reglamento could not be saved. Please, try again.'));
        }
        $this->set(compact('reglamento'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Reglamento id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $reglamento = $this->Reglamento->get($id);
        if ($this->Reglamento->delete($reglamento)) {
            $this->Flash->success(__('The reglamento has been deleted.'));
        } else {
            $this->Flash->error(__('The reglamento could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
