<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * Condomino Controller
 *
 * @property \App\Model\Table\CondominoTable $Condomino
 *
 * @method \App\Model\Entity\Condomino[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CondominoController extends AppController {

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
    public function index($id_condomino = null) {
        $query = $this->Condomino
                ->find()
                ->select([
                    'id_condomino',
                    'condominio' => 'c.nombre_condominio',
                    'numero_departamento',
                    'saldo_inicial',
                    'cuota',
                    'residente_condominio',
                    'numero_contacto',
                    'correo_contacto',
                    'estatus'
                ])
                ->hydrate(false)     // pasarlo como un arreglo
                ->join([
            'c' => [
                'table' => 'condominio', // nombre de tabla en MySQL
                'type' => 'INNER',
                'conditions' => 'c.id_condominio = Condomino.id_condominio',
            ]

        ])
        ->where([
            'c.id_condominio'=>$id_condomino
        ])
        ->order([\App\Database\SqlDialect::forConnection()->leadingInteger('numero_departamento') => 'ASC']);
        $this->set('condomino', $query);

        $this->set('id_condominio', $id_condomino);

    }

    /**
     * View method
     *
     * @param string|null $id Condomino id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null, $id_condomino = null) {

        $query = $this->Condomino
            ->find()
            ->select([
                'id_condomino',
                'condominio' => 'c.nombre_condominio',
                'numero_departamento',
                'residente_condominio',
                'numero_contacto',
                'correo_contacto'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condominio', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condominio = Condomino.id_condominio',
                ]

            ])
            ->where([
                'c.id_condominio'=>$id_condomino
            ]);
        $row = $query->first();


        $this->set('condomino', $row);
        $this->set('_serialize', ['condomino']);


        $this->viewBuilder()->options([
            'pdfConfig' => [
                'pageSize' => 'A5',
                'download' => false,
                'orientation' => 'landscape',
                'filename' => 'Condomino_' . $id . '.pdf'
            ]
        ]);

        $condomino = $this->Condomino->get($id, [
            'contain' => []
        ]);

        $this->set('condomino', $condomino);
}
    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add($id_condominio = null) {
        $condomino = $this->Condomino->newEntity();

        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);
/*      $this->loadModel('condominio'); //if it's not already loaded
        $options = $this->condominio->find('list', ['keyField' => 'id_condominio',
            'valueField' => 'nombre_condominio']);
        $this->set('options', $options);*/

        if ($this->request->is('post')) {
            $condomino = $this->Condomino->patchEntity($condomino, $this->request->getData());

            if ($this->Condomino->save($condomino)) {
                $this->Flash->success(__('The condomino has been saved.'));

                return $this->redirect(['action' => 'index', 'id'=>$condomino->id_condominio]);
            }
            $this->Flash->error(__('The condomino could not be saved. Please, try again.'));
        }
        $this->set(compact('condomino', 'condominio'));

    }

    /**
     * Edit method
     *
     * @param string|null $id Condomino id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null) {
        $this->loadModel('condominio'); //if it's not already loaded

        $condomino = $this->Condomino->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $condomino = $this->Condomino->patchEntity($condomino, $this->request->getData());
            if ($this->Condomino->save($condomino)) {
                $this->Flash->success(__('The condomino has been saved.'));

                return $this->redirect(['action' => 'index', 'id'=>$condomino->id_condominio]);
            }
            $this->Flash->error(__('The condomino could not be saved. Please, try again.'));
        }
        $this->set(compact('condomino'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Condomino id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null) {
        $this->request->allowMethod(['post', 'delete']);
        $condomino = $this->Condomino->get($id);
        if ($this->Condomino->delete($condomino)) {
            $this->Flash->success(__('The condomino has been deleted.'));
        } else {
            $this->Flash->error(__('The condomino could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index', 'id'=>$condomino->id_condominio]);
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
        $permitted = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png');

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
        $condomino = $this->Condomino->get($id);
        if ($this->Condomino->delete($condomino)){
            $message = [['message' => 'Se borro con éxito el condomino - ' . $condomino->id_condomino . ' - ', 'result' => 'ok']];
        } else {
            $message = [['message' => 'Error al borrar los datos', 'result' => 'error']];
            Log::debug($condomino->errors());
        }

        $resultJ = json_encode($message);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;

    }

    public function estatus($id = null)
    {
        $this->loadModel('condominio'); //if it's not already loaded

        $condomino = $this->Condomino->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $condomino = $this->Condomino->patchEntity($condomino, $this->request->getData());
            if ($this->Condomino->save($condomino)) {
                $this->Flash->success(__('The condomino has been saved.'));

                return $this->redirect(['action' => 'index', 'id'=>$condomino->id_condominio]);
            }
            $this->Flash->error(__('The condomino could not be saved. Please, try again.'));
        }
        $this->set(compact('condomino'));
    }

}
