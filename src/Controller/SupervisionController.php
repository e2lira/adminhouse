<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Supervision Controller
 *
 * @property \App\Model\Table\SupervisionTable $Supervision
 *
 * @method \App\Model\Entity\Supervision[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class SupervisionController extends AppController
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
        $supervision = $this->paginate($this->Supervision->find('all')->where(['id_condominio' => $id_condominio]));

        $this->set(compact('supervision', 'id_condominio'));
    }

    /**
     * View method
     *
     * @param string|null $id Supervision id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $supervision = $this->Supervision->get($id, [
            'contain' => [],
        ]);

        $this->set('supervision', $supervision);

        $this->loadModel('Areas');
        $queryArea = $this->Areas
            ->find()
            ->select([
                'id_area',
                'id_supervision',
                'id_condominio',
                'concepto',
                'estado',
                'imagen',
                'observaciones',
                'fecha',
            ])
            ->join([
                's' => [
                    'table' => 'supervision',
                    'type' => 'INNER',
                    'conditions' => 's.id_supervision = Areas.id_supervision',
                ]
            ])
            ->where(['Areas.id_supervision' => $id]);
        $area = $queryArea->all();
        $this->set('area', $area);


    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add($id_condominio = null)
    {
        $supervision = $this->Supervision->newEntity();
        if ($this->request->is('post')) {
            $supervision = $this->Supervision->patchEntity($supervision, $this->request->getData());
            if ($this->Supervision->save($supervision)) {
                $this->Flash->success(__('The supervision has been saved.'));

                return $this->redirect(['action' => 'areas', 'id'=> $supervision->id_supervision]);
            }
            $this->Flash->error(__('The supervision could not be saved. Please, try again.'));
        }
        $this->set(compact('supervision','id_condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Supervision id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $supervision = $this->Supervision->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $supervision = $this->Supervision->patchEntity($supervision, $this->request->getData());
            if ($this->Supervision->save($supervision)) {
                $this->Flash->success(__('The supervision has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The supervision could not be saved. Please, try again.'));
        }
        $this->set(compact('supervision'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Supervision id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $supervision = $this->Supervision->get($id);
        if ($this->Supervision->delete($supervision)) {
            $this->Flash->success(__('The supervision has been deleted.'));
        } else {
            $this->Flash->error(__('The supervision could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function areas($id = null){
        $supervision = $this->Supervision->get($id, [
            'contain' => [],
        ]);

        $this->set('supervision', $supervision);

        $this->loadModel('Areas');
        $area = $this->Areas->newEntity();
        if ($this->request->is('post')) {
            $area = $this->Areas->patchEntity($area, $this->request->getData());
            if ($this->Areas->save($area)) {
                $this->Flash->success(__('The area has been saved.'));

                return $this->redirect($this->referer());
            }
            $this->Flash->error(__('The area could not be saved. Please, try again.'));
        }
        $this->set(compact('area','id'));

    }

}
