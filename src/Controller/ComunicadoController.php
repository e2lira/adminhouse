<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Comunicado Controller
 *
 * @property \App\Model\Table\ComunicadoTable $Comunicado
 *
 * @method \App\Model\Entity\Comunicado[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ComunicadoController extends AppController
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
    public function index($id_condomino = null)
    {
        //$comunicado = $this->paginate($this->Comunicado);
        //$this->set(compact('comunicado'));
        $query = $this->Comunicado
            ->find()
            ->select([
                'id_comunicado',
                'titulo',
                'comunicado',
                'adjunto',
                'id_condominio'

            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condominio', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condominio = Comunicado.id_condominio',
                ]

            ])
            ->where([
                'c.id_condominio'=>$id_condomino
            ]);
        $this->set('comunicado', $this->paginate($query));
        $this->set('_serialize', ['comunicado']);

        $this->set('id_condominio', $id_condomino);

    }

    /**
     * View method
     *
     * @param string|null $id Comunicado id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $comunicado = $this->Comunicado->get($id, [
            'contain' => [],
        ]);

        $this->set('comunicado', $comunicado);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add($id_condominio = null)
    {
        $comunicado = $this->Comunicado->newEntity();

        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);

        if ($this->request->is('post')) {
            $comunicado = $this->Comunicado->patchEntity($comunicado, $this->request->getData());
            if ($this->Comunicado->save($comunicado)) {
                $this->Flash->success(__('The comunicado has been saved.'));

                return $this->redirect(['action' => 'index', 'id'=>$condominio->id_condominio]);
            }
            $this->Flash->error(__('The comunicado could not be saved. Please, try again.'));
        }
        $this->set(compact('comunicado', 'condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Comunicado id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $comunicado = $this->Comunicado->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $comunicado = $this->Comunicado->patchEntity($comunicado, $this->request->getData());
            if ($this->Comunicado->save($comunicado)) {
                $this->Flash->success(__('The comunicado has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The comunicado could not be saved. Please, try again.'));
        }
        $this->set(compact('comunicado'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Comunicado id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $comunicado = $this->Comunicado->get($id);
        if ($this->Comunicado->delete($comunicado)) {
            $this->Flash->success(__('The comunicado has been deleted.'));
        } else {
            $this->Flash->error(__('The comunicado could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
