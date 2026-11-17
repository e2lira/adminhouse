<?php
namespace App\Controller;
use Burzum\SimpleRbac\src\Auth\SimpleRbacAuthorize;
use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

/**
 * Amenidad Controller
 *
 * @property \App\Model\Table\AmenidadTable $Amenidad
 *
 * @method \App\Model\Entity\Amenidad[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class AmenidadController extends AppController
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
    public function index($id_condominio = null)
    {
        //$amenidad = $this->paginate($this->Amenidad);
        //$this->set(compact('amenidad'));
        $this->set('id_condominio', $id_condominio);

        $amenidadTable = TableRegistry::get('Amenidad');
        $amenidad = $amenidadTable->find()
                                  ->where(['Amenidad.id_condominio' => $id_condominio]);
        $this->set('amenidad', $this->paginate($amenidad));
        $this->set('_serialize', ['amenidad']);

    }

    /**
     * View method
     *
     * @param string|null $id Amenidad id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $amenidad = $this->Amenidad->get($id, [
            'contain' => ['Condominio']
        ]);

        $this->set('amenidad', $amenidad);
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


        $amenidad = $this->Amenidad->newEntity();
        if ($this->request->is('post')) {
            $amenidad = $this->Amenidad->patchEntity($amenidad, $this->request->getData());
            if ($this->Amenidad->save($amenidad)) {
                $this->Flash->success(__('The amenidad has been saved.'));

                return $this->redirect(['action' => 'index',
                    'id'=>$id_condominio]);
            }
            $this->Flash->error(__('The amenidad could not be saved. Please, try again.'));
        }
        //$condominio = $this->Amenidad->Condominio->find('list', ['limit' => 200]);
        $this->set(compact('amenidad', 'condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Amenidad id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null, $id_condominio = null)
    {
        $this->set('id_condominio', $id_condominio);

        $amenidad = $this->Amenidad->get($id, [
            'contain' => ['Condominio']
        ]);


        if ($this->request->is(['patch', 'post', 'put'])) {
            $amenidad = $this->Amenidad->patchEntity($amenidad, $this->request->getData());
            if ($this->Amenidad->save($amenidad)) {
                $this->Flash->success(__('The amenidad has been saved.'));

                return $this->redirect(['action' => 'index',
                    'id'=>$id_condominio]);
            }
            $this->Flash->error(__('The amenidad could not be saved. Please, try again.'));
        }
        //$condominio = $this->Amenidad->Condominio->find('list', ['limit' => 200]);
        $this->set(compact('amenidad'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Amenidad id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $amenidad = $this->Amenidad->get($id);
        if ($this->Amenidad->delete($amenidad)) {
            $this->Flash->success(__('The amenidad has been deleted.'));
        } else {
            $this->Flash->error(__('The amenidad could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }


    public function getAmenidadByCondominio($id_condominio = null){
        $amenidadTable = TableRegistry::get('Amenidad');
        $filtro = $this->request->getQuery('search'); //
        $page = $this->request->getQuery('page');
        $limit = $this->request->getQuery('size');
        if($page == 'NaN'){
            $varPage=1;
        }
        Log::debug('Condominio Id:' . $id_condominio . ' Buscar: '.$filtro.' page: '. $page);
        $query= $amenidadTable
            ->find()
            ->select([
                'id'=> 'c.id_amenidad',
                'text'=>'amenidad'
            ])
            ->where([
                'amenidad LIKE' => '%'.$filtro.'%',
            ])
            ->hydrate(false)
            ->join([
                'c' => [
                    'table' => 'amenidad_condominio', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_amenidad = Amenidad.id_amenidad',
                ]
            ])
            ->limit($limit)
            ->page($page);
        $more = ['more'=> 'true'];

        $result = ['results'=>$query, 'pagination'=>$more];
        $resultJson = json_encode($result);

        $this->response->type('json');
        $this->response->body($resultJson);

        return $this->response;
    }

    public function borrar($id = null){
        $amenidad = $this->Amenidad->get($id);
        if ($this->Amenidad->delete($amenidad)){
            $message = [['message' => 'Se borro con éxito el proveedor - ' . $amenidad->id_amenidad . ' - ', 'result' => 'ok']];
        } else {
            $message = [['message' => 'Error al borrar los datos', 'result' => 'error']];
            Log::debug($amenidad->errors());
        }

        $resultJ = json_encode($message);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;

    }
}
