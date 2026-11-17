<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use Cake\Event\Event;
use Cake\Utility\Security;

/**
 * Calendario Controller
 *
 * @property \App\Model\Table\Calendario $Condominio
 *
 * @method \App\Model\Entity\Condominio[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */

class EventsController extends AppController
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

        }  if (isset($user['id_perfil']) && $user['id_perfil'] === 6) {
            return true;
        }

        return false;

    }


    public function beforeFilter(Event $event)
    {
        $this->Security->setConfig('unlockedActions', ['add','load','edit','delete']);
        //$this->getEventManager()->off($this->Csrf);
    }

    public function index(){
        $this->loadModel('condominio'); //if it's not already loaded
        $optionsCondominio = $this->condominio->find('list', ['keyField' => 'id_condominio',
            'valueField' => 'nombre_condominio']);

        $events = $this->Events->find('all');
        $this->set([
            'events' => $events,
            '_serialize' => ['events']
        ]);
        $token = $this->request->getParam('_csrfToken');
        $this->set('optionsCondominio', $optionsCondominio);
        $this->set('_csrfToken', $token);

    }

     protected function calendario()
    {

    }

    public function load(){
        $id = $this->request->getQuery('id');
        Log::debug("EventsController -> Metodo load() Condominio id: " . $id);
        $eventTable = TableRegistry::get('Events');

         $eventos = $eventTable->find()
            ->select([
                'id',
                'title',
                'start',
                'end',
                'responsable',
                'condominio' => 'd.nombre_condominio',
                'condomino' => 'e.numero_departamento',
                'amenidad' => 'c.amenidad',
                'color' => 'c.color'
            ])
            ->hydrate(false)
            ->join([
                'c' => [
                    'table' => 'amenidad', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_amenidad = Events.id_amenidad',
                ]
            ])
            ->join([
                'd' => [
                    'table' => 'condominio', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condominio = Events.id_condominio',
                ]
            ])
            ->join([
                'e' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'e.id_condomino = Events.id_condomino',
                ]
            ])
             //->where(['d.id_condominio' => $id])
         ;


        // the order of these three lines is very important !!!
        $resultJ = json_encode($eventos);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;
    }

    public function add(){

        $evento = $this->Events->newEntity();

        if ($this->request->is(['patch', 'post', 'put'])) {
            $evento = $this->Events->patchEntity($evento, $this->request->getData());
            //Log::debug("Start: " + $evento->start + ", end: " + $evento->end);
            if ($this->Events->save($evento)) {
                $message = ['message' => 'Se guardo con éxito el evento - ' . $evento->title . ' - '];
            } else {
                $message = ['message' => 'Error al guardar los datos'];
                Log::debug($evento->errors());
            }
        } else {
            Log::debug("No puede ingresar al Save");
        }
        $resultJ = json_encode($message);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;
    }


    public function edit($id = null){
        $evento = $this->Events->get($id, [
                'contain' => []
            ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $evento = $this->Events->patchEntity($evento, $this->request->getData());
            if ($this->Events->save($evento)) {
                $message = ['message' => 'Se guardo con éxito el evento - ' . $evento->title . ' - '];
            } else {
                $message = ['message' => 'Error al guardar los datos'];
                Log::debug($evento->errors());
            }
        } else {
            Log::debug("No puede ingresar al Save");
        }

        $resultJ = json_encode($message);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;
    }

    public function delete($id = null){
        $evento = $this->Events->get($id);
        Log::debug("Se borrará el evento titulo: " . $evento->title);
        if ($this->Events->delete($evento)){
            $message = [['message' => 'Se guardo con éxito el evento - ' . $evento->title . ' - ', 'result' => 'ok']];
        } else {
            $message = [['message' => 'Error al guardar los datos', 'result' => 'error']];
            Log::debug($evento->errors());
        }

        $resultJ = json_encode($message);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;

    }

    public function findById(){
        $id = $this->request->getQuery('id_evento');
        Log::debug("Entro a findById ----------->  Id evento = " + $id);
        $evento = $this->Events->find()->where(['id' => $id]);
        // the order of these three lines is very important !!!
        $resultJ = json_encode($evento);
        Log::debug("Entro a findById ----------->  JSON = " + $resultJ);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;
    }
}
