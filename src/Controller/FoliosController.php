<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Folios Controller
 *
 * @property \App\Model\Table\FoliosTable $Folios
 *
 * @method \App\Model\Entity\Folio[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class FoliosController extends AppController
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
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $query = $this->Folios
            ->find()
            ->select([
                'id',
                'condominio' => 'c.nombre_condominio',
                'serie',
                'incremento',
                'fecha_alta'

            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condominio', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condominio = Folios.id_condominio',
                ]
            ]);
        $this->set('folios', $this->paginate($query));
        $this->set('_serialize', ['folios']);
    }

    /**
     * View method
     *
     * @param string|null $id Folio id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $folio = $this->Folios->get($id, [
            'contain' => []
        ]);

        $this->set('folio', $folio);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add($id_condominio = null)
    {
        $folio = $this->Folios->newEntity();

        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);
//        $this->loadModel('condominio');
//        $optionsCondominio = $this->condominio->find('list', ['keyField' => 'id_condominio',
//            'valueField' => 'nombre_condominio'])->join([
//            'table' => 'folios',
//            'alias' => 'f',
//            'type' => 'left',
//            'conditions' => 'f.id_condominio = condominio.id_condominio',
//        ])->where(['f.id_condominio IS NULL']);
//        $this->set('optionsCondominio', $optionsCondominio);

        if ($this->request->is('post')) {
            $folio = $this->Folios->patchEntity($folio, $this->request->getData());
            if ($this->Folios->save($folio)) {
                $this->Flash->success(__('The folio has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The folio could not be saved. Please, try again.'));
        }
        $this->set(compact('folio', 'condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Folio id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $folio = $this->Folios->get($id, [
            'contain' => []
        ]);
        $this->loadModel('condominio');
        $optionsCondominio = $this->condominio->find('list', ['keyField' => 'id_condominio',
            'valueField' => 'nombre_condominio'])->join([
            'table' => 'folios',
            'alias' => 'f',
            'type' => 'left',
            'conditions' => 'f.id_condominio = condominio.id_condominio',
        ])->where(['f.id_condominio IS NULL']);
        $this->set('optionsCondominio', $optionsCondominio);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $folio = $this->Folios->patchEntity($folio, $this->request->getData());
            if ($this->Folios->save($folio)) {
                $this->Flash->success(__('The folio has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The folio could not be saved. Please, try again.'));
        }
        $this->set(compact('folio'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Folio id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $folio = $this->Folios->get($id);
        if ($this->Folios->delete($folio)) {
            $this->Flash->success(__('The folio has been deleted.'));
        } else {
            $this->Flash->error(__('The folio could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function borrar($id = null){
        $folio = $this->Folios->get($id);
        if ($this->Folios->delete($folio)){
            $message = [['message' => 'Se borro con éxito el condominio - ' . $folio->id . ' - ', 'result' => 'ok']];
        } else {
            $message = [['message' => 'Error al borrar los datos', 'result' => 'error']];
            Log::debug($folio->errors());
        }

        $resultJ = json_encode($message);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;

    }
}
