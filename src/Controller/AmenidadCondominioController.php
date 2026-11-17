<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * AmenidadCondominio Controller
 *
 * @property \App\Model\Table\AmenidadCondominioTable $AmenidadCondominio
 *
 * @method \App\Model\Entity\AmenidadCondominio[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class AmenidadCondominioController extends AppController
{

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $amenidadCondominio = $this->paginate($this->AmenidadCondominio);

        $this->set(compact('amenidadCondominio'));

    }

    /**
     * View method
     *
     * @param string|null $id Amenidad Condominio id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $amenidadCondominio = $this->AmenidadCondominio->get($id, [
            'contain' => []
        ]);

        $this->set('amenidadCondominio', $amenidadCondominio);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $amenidadCondominio = $this->AmenidadCondominio->newEntity();
        if ($this->request->is('post')) {
            $amenidadCondominio = $this->AmenidadCondominio->patchEntity($amenidadCondominio, $this->request->getData());
            if ($this->AmenidadCondominio->save($amenidadCondominio)) {
                $this->Flash->success(__('The amenidad condominio has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The amenidad condominio could not be saved. Please, try again.'));
        }
        $this->set(compact('amenidadCondominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Amenidad Condominio id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $amenidadCondominio = $this->AmenidadCondominio->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $amenidadCondominio = $this->AmenidadCondominio->patchEntity($amenidadCondominio, $this->request->getData());
            if ($this->AmenidadCondominio->save($amenidadCondominio)) {
                $this->Flash->success(__('The amenidad condominio has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The amenidad condominio could not be saved. Please, try again.'));
        }
        $this->set(compact('amenidadCondominio'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Amenidad Condominio id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $amenidadCondominio = $this->AmenidadCondominio->get($id);
        if ($this->AmenidadCondominio->delete($amenidadCondominio)) {
            $this->Flash->success(__('The amenidad condominio has been deleted.'));
        } else {
            $this->Flash->error(__('The amenidad condominio could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
