<?php
namespace App\Controller;

use App\Controller\AppController;
use NumerosEnLetras;
use Cake\Filesystem\File;
use Cake\ORM\TableRegistry;

/**
 * Gasto Controller
 *
 * @property \App\Model\Table\GastoTable $Gasto
 *
 * @method \App\Model\Entity\Gasto[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class GastoController extends AppController
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
        $query = $this->Gasto
            ->find()
            ->select([
                'id_condominio',
                'id_gasto',
                'id_categoria',
                'tipo'=>  'c.tipo_gasto',
                'gasto',
                'monto_letra',
                'concepto',
                'fecha_pago',
                'referencia',
                'fecha'
            ])
            ->hydrate(false)
            ->join([
                'c'=>[
                    'table' => 'tipo_gasto', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_tipo = Gasto.id_tipo',
                ]
            ])
            ->where(['Gasto.id_condominio'=>$id_condominio]);

        $this->set('gasto', $this->paginate($query));
        $this->set('_serialize', ['gasto']);

        //$this->set('id_tipo', $id);

        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);
        $this->set('id_condominio', $id_condominio);
        $this->set('condominio', $condominio);
    }

    /**
     * View method
     *
     * @param string|null $id Gasto id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $gasto = $this->Gasto->get($id, [
            'contain' => []
        ]);

        $this->set('gasto', $gasto);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add($id_condominio = null)
    {
        $gasto = $this->Gasto->newEntity();
        $condominioTable = TableRegistry::get('Condominio');

        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);

        $this->loadModel('tipoGasto');
        $this->loadModel('Proveedores');
        $this->loadModel('Categoria');

        $options = $this->tipoGasto->find('list', ['keyField' => 'id_tipo',
            'valueField' => 'tipo_gasto']);
        $this->set('options', $options);

         $optionProveedor = $this->Proveedores->find('list', ['keyField' => 'id_proveedores',
            'valueField' => 'nom_proveedor'])
             ->where(['id_condominio'=>$id_condominio]);
        $this->set('optionProveedor', $optionProveedor);

        $optionCategoria = $this->Categoria->find('list', ['keyField' => 'id_categoria',
            'valueField' => 'categoria'])
            ->where(['id_condominio'=>$id_condominio]);
        $this->set('optionCategoria', $optionCategoria);

        if ($this->request->is('post')) {
            $gasto = $this->Gasto->patchEntity($gasto, $this->request->getData());

            if ($this->Gasto->save($gasto)) {
                $this->saveBanco($gasto);
                $this->saveCierre($gasto);
                $this->Flash->success(__('The gasto has been saved.'));
                return $this->redirect(['action' => 'index','id'=>$id_condominio]);
            }
            $this->Flash->error(__('The gasto could not be saved. Please, try again.'));
        }
        $this->set(compact('gasto','condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Gasto id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null, $id_condominio = null)
    {
        $gasto = $this->Gasto->get($id, [
            'contain' => []
        ]);

        $this->loadModel('tipoGasto');
        $this->loadModel('Proveedores');

        $options = $this->tipoGasto->find('list', ['keyField' => 'id_tipo',
            'valueField' => 'tipo_gasto']);
        $this->set('options', $options);

        $optionProveedor = $this->Proveedores->find('list', ['keyField' => 'id_proveedores',
            'valueField' => 'nom_proveedor']);
        $this->set('optionProveedor', $optionProveedor);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $gasto = $this->Gasto->patchEntity($gasto, $this->request->getData());
                if ($this->Gasto->save($gasto)) {
                    $this->Flash->success(__('The gasto has been saved.'));

                    return $this->redirect(['action' => 'index']);
                }
                $this->Flash->error(__('The gasto could not be saved. Please, try again.'));

        }
        $this->set(compact('gasto'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Gasto id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $gasto = $this->Gasto->get($id);
        if ($this->Gasto->delete($gasto)) {
            $this->Flash->success(__('The gasto has been deleted.'));
        } else {
            $this->Flash->error(__('The gasto could not be deleted. Please, try again.'));
        }

        $this->redirect($this->referer());    }


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
    public function saveBanco($gasto, $id_condominio = null)
    {
        $bancoTable = TableRegistry::get('Banco');
        $banco = $bancoTable->newEntity();

        $banco->id_condominio = $this->request->data['id_condominio'];
        $banco->id_cargo = $gasto->id_gasto;
        $banco->cargo = $gasto->gasto;

        if ($bancoTable->save($banco)){
            debug('Se guardo con éxito en banco');
        }
    }

    public function updateBanco($gasto)
    {
        $bancoTable = TableRegistry::get('Banco');
        debug("ID del Pago --> " + $gasto->id_pago);
        $banco = $bancoTable->find()->where(['id_cargo' => $gasto->id_cargo])->first();

        $banco->cargo = $gasto->gasto;

        if ($bancoTable->save($banco)){
            debug('Se modifico con éxito en banco');
        }
    }

    public function deleteBanco($gasto){
        $bancoTable = TableRegistry::get('Banco');
        debug("ID del Pago --> " + $gasto->id_gasto);
        $banco = $bancoTable->find()->where(['id_cargo' => $gasto->id_gasto])->first();
        if ($banco != null){
            if ($bancoTable->delete($banco)){
                debug('Se borro con éxito el banco');
                return true;
            } else {
                debug('NO Se borro el registro');
                return false;
            }
        }
        return true;
    }
    public function montoletras()
    {
        $gasto= $this->request->getQuery('gasto');
        $letras = NumerosEnLetras::convertir($gasto,'pesos', false, 'centavos');
        //$letras = $letras .  "pesos 00/100 M.N.";
        $resultJ = json_encode(['gasto' => $letras]);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;
    }

    protected function getid($id = null){
        $id = $this->passedArgs['id_condominio'];

    }


    public function borrar($id = null){
        $gasto = $this->Gasto->get($id);
        if ($this->Gasto->delete($gasto)){
            $message = [['message' => 'Se borro con éxito el condominio - ' . $gasto->id_gasto . ' - ', 'result' => 'ok']];
        } else {
            $message = [['message' => 'Error al borrar los datos', 'result' => 'error']];
            Log::debug($gasto->errors());
        }
        $resultJ = json_encode($message);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;

    }

    public function confirmacion($id = null, $id_condominio = null)
    {
        $gasto = $this->Gasto->get($id, [
            'contain' => []
        ]);

        $this->loadModel('tipoGasto');
        $this->loadModel('Proveedores');

        $options = $this->tipoGasto->find('list', ['keyField' => 'id_tipo',
            'valueField' => 'tipo_gasto']);
        $this->set('options', $options);

        $optionProveedor = $this->Proveedores->find('list', ['keyField' => 'id_proveedores',
            'valueField' => 'nom_proveedor']);
        $this->set('optionProveedor', $optionProveedor);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $gasto = $this->Gasto->patchEntity($gasto, $this->request->getData());

                if ($this->Gasto->save($gasto)) {
                    $this->Flash->success(__('The gasto has been saved.'));

                    return $this->redirect(['action' => 'index']);
                }
                $this->Flash->error(__('The gasto could not be saved. Please, try again.'));

        }
        $this->set(compact('gasto'));
    }

    public function saveCierre($gasto)
    {
        $cierreTable = TableRegistry::get('SaldoCierre');

        $fechaPago = new \DateTime($gasto->fecha);
        $mes = $fechaPago->format('m');
        $anio = $fechaPago->format('Y');

        $cierre = $cierreTable->find()
            ->where([
                'id_condominio' => $gasto->id_condominio,
                'MONTH(fecha)' => $mes,
                'YEAR(fecha)' => $anio
            ])
            ->first();

        if ($cierre) {
            $cierre->saldo = $cierre->saldo - $gasto->gasto;
        } else {
            $cierre = $cierreTable->newEntity();
            $cierre->id_condominio = $gasto->id_condominio;
            $cierre->fecha = $gasto->fecha;
            $cierre->saldo = $gasto->gasto;
        }

        if ($cierreTable->save($cierre)) {
            $this->Flash->success(__('El saldo cierre ha sido actualizado.'));
        } else {
            $this->Flash->error(__('El saldo cierre no se pudo guardar. Por favor, intenta de nuevo.'));
        }
    }

}
