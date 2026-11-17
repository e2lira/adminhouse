<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\HTTP\ServerRequest;
use Cake\Mailer\Email;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 *
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */

class AdministradorController extends AppController
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

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $id_user = $this->request->session()->read('Auth.User.id_user');

        $this->loadModel('Users');
        $query = $this->Users
            ->find()
            ->select([
                'id_user',
                'username',
                'password',
                'perfil' => 'c.perfil',
                'email',
                'created',
                'modified'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'perfil', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_perfil = Users.id_perfil',
                ]
            ])
        ->where(['id_user'=> $id_user]);
        $this->set('users', $this->paginate($query));
        $this->set('_serialize', ['users']);
    }

    /**
     * View method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $this->loadModel('Users');
        $query = $this->Users
            ->find()
            ->select([
                'id_user',
                'username',
                'password',
                'perfil' => 'c.perfil',
                'email',
                'created',
                'modified'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'perfil', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_perfil = Users.id_perfil',
                ]
            ])
            ->where(['id_user' => $id]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);

        $condominioTable = TableRegistry::get('Condominio');
        $condominios = $condominioTable->find()
            ->select(['condominio' => 'u.nombre_condominio'])
            ->join([
                'c' => [
                    'table' => 'users_condominio',
                    'type' => 'RIGHT',
                    'conditions' => 'c.id = id_user',
                ],
                'u' => [
                    'table' => 'condominio',
                    'type' => 'RIGHT',
                    'conditions' => 'u.id_condominio = c.id_condominio',
                ]
            ])
            ->where(['c.id_user' => $id]);
        $this->set('condominios', $condominios);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $id_user = $this->request->session()->read('Auth.User.id_user');
        $this->loadModel('Users');
        $user = $this->Users->newEntity();

        $this->loadModel('condominio');
        $optionsCondominio = $this->condominio->find('list', ['keyField' => 'id_condominio',
            'valueField' => 'nombre_condominio'])
            ->where(['']);
        $this->set('condomonios', $optionsCondominio);

        $this->loadModel('perfil'); //if it's not already loaded
        $options = $this->perfil->find('list', ['keyField' => 'id_perfil',
            'valueField' => 'perfil'])
        ->where(['id_perfil !='=> 1]);
        $this->set('options', $options);

        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $condominios = $this->Users->Condominio->find('list',  ['keyField' => 'id_condominio',
            'valueField' => 'nombre_condominio']);
        $this->set(compact('user', 'condominios'));
    }

    /**
     * Edit method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $this->loadModel('Users');

        $user = $this->Users->get($id, [
            'contain' => ['Condominio']
        ]);

        $this->loadModel('condominio');
        $optionsCondominio = $this->condominio->find('list', ['keyField' => 'id_condominio',
            'valueField' => 'nombre_condominio']);
        $this->set('condomonios', $optionsCondominio);

        $this->loadModel('perfil'); //if it's not already loaded
        $options = $this->perfil->find('list', ['keyField' => 'id_perfil',
            'valueField' => 'perfil']);
        $this->set('options', $options);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $condominio = $this->Users->Condominio->find('list');
        $this->set(compact('user', 'condominio'));
    }

    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->loadModel('Users');
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }


    /* public function login()
     {

         $this->viewBuilder()->layout('login');
         if ($this->request->is('post')) {
             $user = $this->Auth->identify();
             if ($user) {
                 $this->Auth->setUser($user);
                 return $this->redirect($this->Auth->redirectUrl());
             }
             $this->Flash->error(__('Usuario o contrasenia invalidos, vuelve a intentarlo'));
         }
     }
 */

    public function login()
    {
        $this->viewBuilder()->layout('login');

        if ($this->request->is('post')) {
            $user = $this->Auth->identify();
            if ($user) {
                $this->Auth->setUser($user);


                $loggedUser = $this->request->getsession()->read('Auth.User');
                if($loggedUser['id_perfil'] == '1'){
                    return $this->redirect('pages/bienvenido');
                }else if($loggedUser['id_perfil'] == '6'){
                    return $this->redirect('usuario/profile');
                }else{
                    return $this->redirect($this->Auth->redirectUrl());
                }


            }

        }
    }



    public function logout()
    {
        return $this->redirect($this->Auth->logout());
    }

    protected function display()
    {

    }

    protected function profile()
    {
        $this->set('profile', $this->User->find('all'));
    }

    public function getCondominios(){
        $condominiosTable = TableRegistry::get('Condominio'); //Carga los registros de la tabla
        $varFiltro = $this->request->getQuery('search'); //
        $varPage = $this->request->getQuery('page');
        $varLimit = $this->request->getQuery('size');
        if($varPage == 'NaN'){
            $varPage=1;
        }
        Log::debug('Buscar: '.$varFiltro.' page: '. $varPage);
        $query= $condominiosTable
            ->find()
            ->select([
                'id'=>'id_condominio',
                'text'=>'nombre_condominio'
            ])
            ->where([
                'nombre_condominio LIKE' => '%'.$varFiltro.'%'
            ])
            ->hydrate(false)
            ->limit($varLimit)
            ->page($varPage);
        $more = ['more'=> 'true'];
        //$result = ['result'=>$query, 'pagination'=>$more];
        $result = ['results'=>$query, 'pagination'=>$more];
        $resultJson = json_encode($result);
        Log::debug('Result '. $resultJson);

        $this->response->type('json');
        $this->response->body($resultJson);

        return $this->response;
    }

    public function getPerfiles(){
        $perfilTable = TableRegistry::get('Perfil'); //Carga los registros de la tabla
        $filtro = $this->request->getQuery('search'); //
        $page = $this->request->getQuery('page');
        $limit = $this->request->getQuery('size');
        if($page == 'NaN'){
            $page=1;
        }
        Log::debug('Buscar: '.$filtro.' page: '. $page);
        $query= $perfilTable
            ->find()
            ->select([
                'id'=>'id_perfil',
                'text'=>'perfil'
            ])
            ->where(['id_perfil >'=> 1])
            ->where([
                'perfil LIKE' => '%'.$filtro.'%'
            ])
            ->enableHydration(false)
            ->limit($limit)
            ->page($page);
        $more = ['more'=> 'false'];
        //$result = ['result'=>$query, 'pagination'=>$more];
        $result = ['results'=>$query, 'pagination'=>$more];
        $resultJson = json_encode($result);
        Log::debug('Result '. $resultJson);

        $this->response->withType('json');

        $this->response->Body($resultJson);

        return $this->response;
    }

    public function borrar($id = null){
        $users = $this->Users->get($id);
        if ($this->Users->delete($users)){
            $message = [['message' => 'Se borro con éxito el usuario - ' . $users->id . ' - ', 'result' => 'ok']];
        } else {
            $message = [['message' => 'Error al borrar los datos', 'result' => 'error']];
            Log::debug($users->errors());
        }

        $resultJ = json_encode($message);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;

    }

    public function password(){
        $this->viewBuilder()->layout('');

    }

    public function asigna()
    {
        $this->loadModel('Asigna');
        $asigna = $this->paginate($this->Asigna);
        $this->set(compact('asigna'));

        $this->loadModel('condominio');
        $options = $this->condominio->find('list', ['keyField' => 'id_condominio',
            'valueField' => 'nombre_condominio']);
        $this->set('options', $options);

        $this->loadModel('condomino');
        $condomino = $this->condomino->find('list', ['keyfield'=>'id_condomino',
            'valueField'=> 'numero_departamento']);
        $this->set('condomino', $condomino);

        $this->loadModel('users');
        $users = $this->users->find('list', ['keyfield'=>'id_user',
            'valueField'=> 'username']);
        $this->set('users', $users);


    }

}
