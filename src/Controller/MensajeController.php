<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Mensaje Controller
 *
 * @property \App\Model\Table\MensajeTable $Mensaje
 *
 * @method \App\Model\Entity\Mensaje[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class MensajeController extends AppController
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
     * @return \Cake\Http\Response|null
     */
    public function index($id = null)
    {
        //$mensaje = $this->paginate($this->Mensaje);
        //$this->set(compact('mensaje'));

        $mensajesTable = TableRegistry::get('Mensaje');
        $rows = $mensajesTable->find()
            ->select([
                'id_mensaje',
                'condominio'=>'c.nombre_condominio',
                'depto'=>'d.numero_departamento',
                'titulo',
                'fecha',
                'mensaje',
                'estatus'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condominio', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condominio = Mensaje.id_condominio',
                ]

            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Mensaje.id_condomino',
                ]

            ])
            ->where(['Mensaje.id_condominio' => $id])
            ->order(['Mensaje.fecha' => 'DESC']);

        $this->set('mensaje', $this->paginate($rows));
        $this->set('_serialize', ['mensaje']);

    }

    /**
     * View method
     *
     * @param string|null $id Mensaje id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $mensaje = $this->Mensaje->get($id, [
            'contain' => [],
        ]);

        $this->set('mensaje', $mensaje);


        $this->loadModel('Respuestas'); //if it's not already loaded
        $respuesta = $this->Respuestas->newEntity();
        if ($this->request->is('post')) {
            $respuesta = $this->Respuestas->patchEntity($respuesta, $this->request->getData());
            if ($this->Respuestas->save($respuesta)) {
                $this->Flash->success(__('The respuesta has been saved.'));

                return $this->redirect($this->referer());
            }
            $this->Flash->error(__('The respuesta could not be saved. Please, try again.'));
        }
        $this->set(compact('respuesta'));

        $respuestasTable = TableRegistry::get('Respuestas');
        $respuestas = $respuestasTable->find()
            ->select([
                'id_respuestas',
                'id_mensaje',
                'mensaje',
                'dir',
                'adjunto',
                'id_condominio',
                'id_condomino',
                'estatus',
                'fecha'
            ])
            ->where(['id_mensaje' => $id]);
        $rows = $respuestas->all();
        $this->set('respuestas', $rows);


    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $mensaje = $this->Mensaje->newEntity();
        if ($this->request->is('post')) {
            $mensaje = $this->Mensaje->patchEntity($mensaje, $this->request->getData());
            if ($this->Mensaje->save($mensaje)) {
                $this->Flash->success(__('The mensaje has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The mensaje could not be saved. Please, try again.'));
        }
        $this->set(compact('mensaje'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Mensaje id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $mensaje = $this->Mensaje->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $mensaje = $this->Mensaje->patchEntity($mensaje, $this->request->getData());
            if ($this->Mensaje->save($mensaje)) {
                $this->Flash->success(__('The mensaje has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The mensaje could not be saved. Please, try again.'));
        }
        $this->set(compact('mensaje'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Mensaje id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $mensaje = $this->Mensaje->get($id);
        if ($this->Mensaje->delete($mensaje)) {
            $this->Flash->success(__('The mensaje has been deleted.'));
        } else {
            $this->Flash->error(__('The mensaje could not be deleted. Please, try again.'));
        }

        return $this->redirect($this->referer());
    }


    public function leido($id = null)
    {
        $mensajeTable = TableRegistry::getTableLocator()->get('Mensaje');
        $mensaje = $mensajeTable->get($id);

        $mensaje->estatus = '1';
        $mensajeTable->save($mensaje);
        return $this->redirect($this->referer());


    }
}
