<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Log\Log;
use NumerosEnLetras;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\I18n\I18n;
use function Sodium\add;
use function Sodium\increment;

/**
 * Bloque Controller
 *
 * @property \App\Model\Table\BloqueTable $Bloque
 *
 * @method \App\Model\Entity\Bloque[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class BloqueController extends AppController
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

    public function beforeFilter(Event $event)
    {
        $this->Security->setConfig('unlockedActions', ['index','saveAdeudo']);
        //$this->getEventManager()->off($this->Csrf);
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index($id_condominio = null)
    {
        $bloque = $this->paginate($this->Bloque);

        $this->set(compact('bloque'));
        $this->set('id_condominio', $id_condominio);

        $token = $this->request->getParam('_csrfToken');
        $this->set('_csrfToken', $token);

        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);
        $this->set('id_condominio', $id_condominio);


    }

    /**
     * View method
     *
     * @param string|null $id Bloque id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $bloque = $this->Bloque->get($id, [
            'contain' => [],
        ]);

        $this->set('bloque', $bloque);
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

        $this->loadModel('condomino'); //if it's not already loaded
        $optionsCondomino = $this->condomino->find('list', ['keyField' => 'id_condomino',
            'valueField' => 'numero_departamento'])
        ->where(['id_condominio'=>$id_condominio]);
        $this->set('optionsCondomino', $optionsCondomino);

        $this->loadModel('tipoCuota');
        $options = $this->tipoCuota->find('list', ['keyField' => 'id_tipo',
            'valueField' => 'tipo_cuota']);
        $this->set('options', $options);

        $bloque = $this->Bloque->newEntity();
        if ($this->request->is('post')) {
            $bloque = $this->Bloque->patchEntity($bloque, $this->request->getData());
            if ($this->Bloque->save($bloque)) {
                $this->Flash->success(__('The bloque has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The bloque could not be saved. Please, try again.'));
        }
        $this->set(compact('bloque','condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Bloque id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $bloque = $this->Bloque->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $bloque = $this->Bloque->patchEntity($bloque, $this->request->getData());
            if ($this->Bloque->save($bloque)) {
                $this->Flash->success(__('The bloque has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The bloque could not be saved. Please, try again.'));
        }
        $this->set(compact('bloque'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Bloque id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $bloque = $this->Bloque->get($id);
        if ($this->Bloque->delete($bloque)) {
            $this->Flash->success(__('The bloque has been deleted.'));
        } else {
            $this->Flash->error(__('The bloque could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    protected function saveAdeudo($id_condominio = null)
    {


    }



}
