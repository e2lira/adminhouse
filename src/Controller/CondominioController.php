<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use Cake\Event\Event;
use Cake\HTTP\ServerRequest;
/**
 * Condominio Controller
 *
 * @property \App\Model\Table\CondominioTable $Condominio
 *
 * @method \App\Model\Entity\Condominio[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CondominioController extends AppController
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
        $query = $this->Condominio
            ->find()
            ->select([
                'id_condominio',
                'nombre_condominio',
                'direccion_condominio',
                'telefono_condominio',
                'logo'

            ])
            ->hydrate(false)
            ->join([
                'uc' => [
                    'table' => 'users_condominio', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'uc.id_condominio = Condominio.id_condominio',
                ]
            ])
            ->where([
                'uc.id_user'=>$id_user
            ]);
        $this->set('condominio', $this->paginate($query));
        $this->set('_serialize', ['condominio']);
        $this->set('id_user', $id_user);
    }

    public function indexCompleto()
    {
        $condominio = $this->paginate($this->Condominio);

        $this->set(compact('condominio'));
    }

    /**
     * View method
     *
     * @param string|null $id Condominio id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $condominio = $this->Condominio->get($id, [
            'contain' => []
        ]);

        $this->set('condominio', $condominio);


        $amenidadTable = TableRegistry::get('Amenidad');
        $amenidades = $amenidadTable->find()
            ->select(['amenidad' => 'a.amenidad'])
            ->join([
                'ac' => [
                    'table' => 'amenidad_condominio',
                    'type' => 'RIGHT',
                    'conditions' => 'ac.id_amenidad = Amenidad.id_amenidad',
                ],
                'a' => [
                    'table' => 'amenidad',
                    'type' => 'RIGHT',
                    'conditions' => 'a.id_amenidad = ac.id_amenidad',
                ]
            ])
            ->where(['ac.id_condominio' => $id]);

        $this->set('amenidades', $amenidades);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $condominio = $this->Condominio->newEntity();

       /* $this->loadModel('amenidad'); //if it's not already loaded
        $options = $this->amenidad->find('list', ['keyField' => 'id_amenidad',
            'valueField' => 'amenidad']);
        $this->set('amenidades', $options); */


        if ($this->request->is('post')) {
            $condominio = $this->Condominio->patchEntity($condominio, $this->request->getData());

          /*  $amenidades = $this->request->getData('amenidad._ids');
            foreach ($amenidades as $amenidad){
                Log::debug("nombre amenidad: ". $amenidad);

            } */

            if ($this->Condominio->save($condominio)) {
               /* $amenidadCondomioniosTable = TableRegistry::get('amenidad_condominio');
                foreach($amenidades as $row){
                    $amenidadcondominio = $amenidadCondomioniosTable->newEntity();
                    $amenidadcondominio->id_condominio = $condominio->id_condominio;
                    $amenidadcondominio->id_amenidad = $row;
                    $amenidadCondomioniosTable->save($amenidadcondominio);
                }*/
                $this->Flash->success(__('The condominio has been saved.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The condominio could not be saved. Please, try again.'));
        }
        $this->set(compact('condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Condominio id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $condominio = $this->Condominio->get($id, [
            'contain' => []
        ]);

       /* $this->loadModel('amenidad'); //if it's not already loaded
        $options = $this->amenidad->find('list', ['keyField' => 'id_amenidad',
            'valueField' => 'amenidad']);
        $this->set('amenidades', $options); */

        if ($this->request->is(['patch', 'post', 'put'])) {
            //$amenidades = $this->request->getData('amenidad._ids');
            $condominio = $this->Condominio->patchEntity($condominio, $this->request->getData());
            if ($this->Condominio->save($condominio)) {
               /* $amenidadCondomioniosTable = TableRegistry::get('amenidad_condominio');
                $query = $amenidadCondomioniosTable->query();
                if($query->delete()
                    ->where()
                    ->execute()){
                    foreach($amenidades as $row) {
                        $amenidadcondominio = $amenidadCondomioniosTable->newEntity();
                        $amenidadcondominio->id_condominio = $condominio->id_condominio;
                        $amenidadcondominio->id_amenidad = $row;
                        $amenidadCondomioniosTable->save($amenidadcondominio);
                      }

                    }else{
                        $this->Flash->error(__('The amenidad could not be saved. Please, try again.'));
                    }
                */
                $this->Flash->success(__('The condominio has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The condominio could not be saved. Please, try again.'));
        }
        $this->set(compact('condominio'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Condominio id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $condominio = $this->Condominio->get($id);
        if ($this->Condominio->delete($condominio)) {
            $this->Flash->success(__('The condominio has been deleted.'));
        } else {
            $this->Flash->error(__('The condominio could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function pa($arr) {
        echo '<pre>';
        print_r($arr);
        echo '</pre>';
    }

    function uploadFiles($folder, $file, $sub_folder) {
        // setup dir names absolute and relative
        $folder_url = WWW_ROOT . $folder;
        $rel_url = $folder;


        // create the folder if it does not exist
        if (!is_dir($folder_url)) {
            mkdir($folder_url);
        }

        // if itemId is set create an item folder
        if ($sub_folder) {
            // set new absolute folder
            $folder_url = WWW_ROOT . $folder . DS . $sub_folder;
            // set new relative folder
            $rel_url = $folder . DS . $sub_folder;
            // create directory
            if (!is_dir($folder_url)) {
                mkdir($folder_url);
            }
        }

        // list of permitted file types, this is only images but documents can be added
        $permitted = array('image' . DS . 'jpg', 'image' . DS . 'JPG', 'image' . DS . 'gif', 'image' . DS . 'jpeg', 'image' . DS . 'pjpeg', 'image' . DS . 'png');

        $filename = str_replace(' ', '_', $file['name']);
        $typeOK = false;
        foreach ($permitted as $type) {
            if ($type == $file['type']) {
                $typeOK = true;
                break;
            }
        }

        // if file type ok upload the file
        if ($typeOK) {
            // switch based on error code
            switch ($file['error']) {
                case 0:
                    // check filename already exists
                    if (!file_exists($folder_url . DS . $filename)) {
                        // create full filename
                        $full_url = $folder_url . DS . $filename;
                        $url = $rel_url . DS . $filename;
                        // upload the file
                        $success = move_uploaded_file($file['tmp_name'], $url);
                        // if upload was successful
                        if ($success) {
                            // save the url of the file
                            $result['urls'][] = $url;
                        } else {
                            $result['errors'][] = "Error.";
                            $this->Flash->error(__('Error uploaded ' . $filename . ' Please try again.'));
                        }
                    } else {
                        // $result['errors'][] = "Error uploaded $filename. Image already loaded, Please try again.";
                        $result['errors'][] = "Error.";
                        $this->Flash->error(__('Error  ' . $filename . ' already loaded, Please try again.'));
                    }
                    break;
                case 3:
                    // an error occured
                    $result['errors'][] = "Error.";
                    $this->Flash->error(__('Error uploading ' . $filename . ' Please try again.'));
                    break;
                default:
                    // an error occured
                    $result['errors'][] = "Error.";
                    $this->Flash->error(__('System error uploading ' . $filename . ' Contact webmaster.'));
                    break;
            }
        } elseif ($file['error'] == 4) {
            // no file was selected for upload
            $result['errors'][] = "Error.";
            $this->Flash->error(__('No file Selected'));
        } else {
            // unacceptable file type
            $result['errors'][] = "Error.";
            $this->Flash->error(__($filename . ' cannot be uploaded. Acceptable file types: gif, jpg, png.'));
        }

        return $result;
    }

    function BorrarFile($imagepath) {
        $sys_path = WWW_ROOT . $imagepath;
        $file = new File($sys_path);
        $this->log($sys_path, 'debug');

        if ($file->exists()) {
            $file->delete();
        }
    }

    public function borrar($id = null){
        $condominio = $this->Condominio->get($id);
        if ($this->Condominio->delete($condominio)){
            $message = [['message' => 'Se borro con éxito el condominio - ' . $condominio->id_condominio . ' - ', 'result' => 'ok']];
        } else {
            $message = [['message' => 'Error al borrar los datos', 'result' => 'error']];
            Log::debug($condominio->errors());
        }

        $resultJ = json_encode($message);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;

    }


    public function getAmenidades(){
        $amenidadesTable = TableRegistry::get('Amenidad'); //Carga los registros de la tabla
        $varFiltro = $this->request->getQuery('search'); //
        $varPage = $this->request->getQuery('page');
        $varLimit = $this->request->getQuery('size');
        if($varPage == 'NaN'){
            $varPage=1;
        }
        Log::debug('Buscar: '.$varFiltro.' page: '. $varPage);
        $query= $amenidadesTable
            ->find()
            ->select([
                'id'=>'id_amenidad',
                'text'=>'amenidad'
            ])
            ->where([
                'amenidad LIKE' => '%'.$varFiltro.'%'
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

public function getCondominios(){
        $condominioTable = TableRegistry::get('Condominio'); //Carga los registros de la tabla
        $filtro = $this->request->getQuery('search'); //
        $page = $this->request->getQuery('page');
        $limit = $this->request->getQuery('size');
        if($page == 'NaN'){
            $varPage=1;
        }
        Log::debug(' Buscar: '.$filtro.' page: '. $page);
        $query= $condominioTable
            ->find()
            ->select([
                'id'=>'id_condominio',
                'text'=>'nombre_condominio'
            ])
            ->where([
                'nombre_condominio LIKE' => '%'.$filtro.'%'
            ])
            ->hydrate(false)
            ->limit($limit)
            ->page($page);
        $more = ['more'=> 'true'];
        //$result = ['result'=>$query, 'pagination'=>$more];
        $result = ['results'=>$query, 'pagination'=>$more];
        $resultJson = json_encode($result);
        //Log::debug('Result '. $resultJson);

        $this->response->type('json');
        $this->response->body($resultJson);

        return $this->response;
    }

    public function bienvenido(){

    }

    public function menu($id = null){
        $this->loadModel('Condominio');
        $condominio = $this->Condominio->get($id, [
            'contain' => [],
        ]);
        $this->set('condominio', $condominio);
    }

}
