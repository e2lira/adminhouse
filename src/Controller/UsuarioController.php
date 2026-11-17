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

class UsuarioController extends AppController
{
    public function isAuthorized($user) {

        // AZ-1: the guard visit page is for staff profiles only, never for residents.
        if ($this->request->getParam('action') === 'vigilante') {
            return isset($user['id_perfil']) && in_array($user['id_perfil'], [1, 2, 3, 5], true);
        }

        // Admin allowed anywhere
        if (isset($user['id_perfil']) && $user['id_perfil'] === 1) {
            return true;
        }
        if (isset($user['id_perfil']) && $user['id_perfil'] === 6) {
            return true;
        }

        return false;

    }

    public function view($id = null)
    {
        $this->viewBuilder()->setLayout('');
        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto'
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ]);
        //->where(['id_user' => $this->request->getSession()->read('Auth.Users.id_user')]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);

        $this->loadModel('Ticket');
        $ticket = $this->Ticket->get($id, [
            'contain' => [],
        ]);

        $this->set('ticket', $ticket);
    }

    public function profile($id = null)
    {
        $this->viewBuilder()->setLayout('');

        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'condominio'=>'d.id_condominio',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto'
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
            ->where(['id_user' => $id_user ]);
        $row = $query->first();

        // Without an assigned unit there is no resident data to show (the queries below need it).
        if ($row === null) {
            return $this->render('sin_unidad');
        }

        $this->set('user', $row);
        $this->set('_serialize', ['user']);


        $this->loadModel('Adeudos'); //if it's not already loaded
        $condominioTable = TableRegistry::get('Adeudos');
        $query1 = $condominioTable
            ->find()
            ->select([
                'id_adeudo',
                'id_condominio',
                'id_condomino',
                'mes',
                'monto',
                'concepto',
                'estatus',
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'asigna', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino ='. $row->id_condomino,
                ]

            ])
            ->where(['estatus' => 1 ])
            ->where(['T.id_condomino' => $row->id_condomino ]);
        $this->set('adeudos', $this->paginate($query1));
        $this->set('_serialize', ['adeudos']);

        $query2 = $this->Adeudos->find();
        $query2->select([
            'sum' => $query2->func()->sum('T.monto')])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'asigna', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = '. $row->id_condomino,
                ]

            ])
            ->where(['estatus' => 1 ])
            ->where(['T.id_condomino' => $row->id_condomino ]);
        $total = $query2->first();
        $this->set('query2', $total);

        /* ----------------------------- comunicados -------------------------------- */

        $this->loadModel('Comunicado'); //if it's not already loaded
        $query3 = $this->Comunicado
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
            ->limit(5)
            ->order(['id_comunicado'=>'DESC']);
        $this->set('comunicado', $query3);
        $this->set('_serialize', ['comunicado']);

        /* ----------------------------- informacion condominio -------------------------------- */


        $this->loadModel('Asigna'); //if it's not already loaded
        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $condoID = $this->Asigna
            ->find()
            ->select([
                'Asigna.id_user',
                'Asigna.id_condomino',
                'condominio'=>'c.id_condominio'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Asigna.id_condomino',
                ]

            ])
            ->where(['Asigna.id_user'=> $id_user]);
        $ids = $condoID->first();
        $this->set('ids', $ids);


        $this->loadModel('Condominio'); //if it's not already loaded
        $condoInf = $this->Condominio
            ->find()
            ->select([
                'id_condominio',
                'nombre_condominio',
                'logo',
                'direccion_condominio',
                'telefono_condominio',
            ])
            ->where(['id_condominio'=> $ids['condominio']]);
        $inf = $condoInf->first();
        $this->set('inf', $inf);

        $mensajesTable = TableRegistry::get('Mensaje');
        $this->loadModel('Mensaje');
        $Query = $mensajesTable->find('all');
        $Query->select([
            'count' => $Query->func()->count('*')
        ])
            ->where(['id_condomino' => $id]);

        $total = $Query->first();
        $this->set('Query', $total);



    }

    public function configuracion($id = null)
    {
        $this->viewBuilder()->setLayout('');

        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'condominio'=>'d.id_condominio',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto'
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
            ->where(['id_user' => $id_user ]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);

        $this->loadModel('Asigna'); //if it's not already loaded
        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $condoID = $this->Asigna
            ->find()
            ->select([
                'Asigna.id_condomino',
                'condominio'=>'c.id_condominio'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Asigna.id_condomino',
                ]

            ])
            ->where(['Asigna.id_condomino'=> $row->id_condomino]);
        $ids = $condoID->first();
        $this->set('ids', $ids);


        $this->loadModel('Condominio'); //if it's not already loaded
        $condoInf = $this->Condominio
            ->find()
            ->select([
                'id_condominio',
                'nombre_condominio',
                'logo',
                'direccion_condominio',
                'telefono_condominio',
            ])
            ->where(['id_condominio'=> $ids['condominio']]);
        $inf = $condoInf->first();
        $this->set('inf', $inf);

        $mensajesTable = TableRegistry::get('Mensaje');
        $this->loadModel('Mensaje');
        $Query = $mensajesTable->find('all');
        $Query->select([
            'count' => $Query->func()->count('*')
        ])
            ->where(['id_condomino' => $id]);

        $total = $Query->first();
        $this->set('Query', $total);


        $this->loadModel('Condomino');
        $condomino = $this->Condomino->newEntity();
        if ($this->request->is(['patch', 'post', 'put'])) {
            $condomino = $this->Condomino->patchEntity($condomino, $this->request->getData());
            if ($this->Condomino->save($condomino)) {
                $this->Flash->success(__('The condomino has been saved.'));

                return $this->redirect(['action' => 'index', 'id'=>$condomino->id_condominio]);
            }
            $this->Flash->error(__('The condomino could not be saved. Please, try again.'));
        }
        $this->set(compact('condomino'));

        $id_user = $this->request->getSession()->read('Auth.User.id_user');

        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto',
                'condominio'=>'d.id_condominio',
                'residente' => 'd.residente_condominio'

            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
            ->where(['id_user' => $id_user ]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);



    }

    public function ticket($id = null){
        $this->viewBuilder()->setLayout('');

        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'condominio'=>'d.id_condominio',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto'
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
            ->where(['id_user' => $id_user ]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);

        $this->loadModel('Asigna'); //if it's not already loaded
        $condoID = $this->Asigna
            ->find()
            ->select([
                'Asigna.id_condomino',
                'condominio'=>'c.id_condominio'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Asigna.id_condomino',
                ]

            ])
            ->where(['Asigna.id_condomino'=> $row->id_condomino]);
        $ids = $condoID->first();
        $this->set('ids', $ids);


        $this->loadModel('Condominio'); //if it's not already loaded
        $condoInf = $this->Condominio
            ->find()
            ->select([
                'id_condominio',
                'nombre_condominio',
                'logo',
                'direccion_condominio',
                'telefono_condominio',
            ])
            ->where(['id_condominio'=> $ids['condominio']]);
        $inf = $condoInf->first();
        $this->set('inf', $inf);

        $id_user = $this->request->getSession()->read('Auth.User.id_user');

        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto',
                'condominio'=>'d.id_condominio',
                'residente' => 'd.residente_condominio'

            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
        ->where(['id_user' => $id_user ]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);

        $this->loadModel('Ticket');
        $ticket = $this->Ticket->newEntity();
        if ($this->request->is('post')) {
            $ticket = $this->Ticket->patchEntity($ticket, $this->request->getData());
            if ($this->Ticket->save($ticket)) {
                $this->Flash->success(__('The ticket has been saved.'));

                return $this->redirect($this->referer());
            }
            $this->Flash->error(__('The ticket could not be saved. Please, try again.'));
        }
        $this->set(compact('ticket'));

        $mensajesTable = TableRegistry::get('Mensaje');
        $this->loadModel('Mensaje');
        $Query = $mensajesTable->find('all');
        $Query->select([
            'count' => $Query->func()->count('*')
        ])
            ->where(['id_condomino' => $id]);

        $total = $Query->first();
        $this->set('Query', $total);

    }

    public function allt($id = null)
    {
        $this->viewBuilder()->setLayout('');
        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'condominio'=>'d.id_condominio',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto'
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
            ->where(['id_user' => $id_user ]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);

        $this->loadModel('Asigna'); //if it's not already loaded
        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $condoID = $this->Asigna
            ->find()
            ->select([
                'Asigna.id_condomino',
                'condominio'=>'c.id_condominio'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Asigna.id_condomino',
                ]

            ])
            ->where(['Asigna.id_condomino'=> $row->id_condomino]);
        $ids = $condoID->first();
        $this->set('ids', $ids);


        $this->loadModel('Condominio'); //if it's not already loaded
        $condoInf = $this->Condominio
            ->find()
            ->select([
                'id_condominio',
                'nombre_condominio',
                'logo',
                'direccion_condominio',
                'telefono_condominio',
            ])
            ->where(['id_condominio'=> $ids['condominio']]);
        $inf = $condoInf->first();
        $this->set('inf', $inf);
        $this->loadModel('Ticket');
        $ticket = $this->paginate($this->Ticket);

        $this->set(compact('ticket'));

        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto',
                'condominio'=>'d.id_condominio',
                'residente' => 'd.residente_condominio'

            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
            ->where(['id_user' => $id_user ]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);

        $mensajesTable = TableRegistry::get('Mensaje');
        $this->loadModel('Mensaje');
        $Query = $mensajesTable->find('all');
        $Query->select([
            'count' => $Query->func()->count('*')
        ])
            ->where(['id_condomino' => $id]);

        $total = $Query->first();
        $this->set('Query', $total);

    }

    public function calendario($id = null)
    {
        $this->viewBuilder()->setLayout('');

        $this->loadModel('condominio');
        $optionsCondominio = $this->condominio->find('list', ['keyField' => 'id_condominio',
            'valueField' => 'nombre_condominio'])
            ->where(['']);
        $this->set('condomonios', $optionsCondominio);

        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'condominio'=>'d.id_condominio',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto',
                'estatus'=>'d.estatus'
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
            ->where(['id_user' => $id_user ]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);

        $this->loadModel('Asigna'); //if it's not already loaded
        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $condoID = $this->Asigna
            ->find()
            ->select([
                'Asigna.id_condomino',
                'condominio'=>'c.id_condominio'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Asigna.id_condomino',
                ]

            ])
            ->where(['Asigna.id_condomino'=> $row->id_condomino]);
        $ids = $condoID->first();
        $this->set('ids', $ids);


        $this->loadModel('Condominio'); //if it's not already loaded
        $condoInf = $this->Condominio
            ->find()
            ->select([
                'id_condominio',
                'nombre_condominio',
                'logo',
                'direccion_condominio',
                'telefono_condominio',
            ])
            ->where(['id_condominio'=> $ids['condominio']]);
        $inf = $condoInf->first();
        $this->set('inf', $inf);
        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto',
                'condominio'=>'d.id_condominio',
                'residente' => 'd.residente_condominio',
                'estatus'=>'d.estatus'

            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
            ->where(['id_user' => $id_user ]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);

        $mensajesTable = TableRegistry::get('Mensaje');
        $this->loadModel('Mensaje');
        $Query = $mensajesTable->find('all');
        $Query->select([
            'count' => $Query->func()->count('*')
        ])
            ->where(['id_condomino' => $id]);

        $total = $Query->first();
        $this->set('Query', $total);

        $this->loadModel('amenidad'); //if it's not already loaded
        $options = $this->amenidad->find('list', ['keyField' => 'id_amenidad',
            'valueField' => 'amenidad'])
            ->where(['id_condominio'=>$ids['condominio']]);
        $this->set('amenidades', $options);

    }

    public function reglamento($id = null){
        $this->viewBuilder()->setLayout('');

        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'condominio'=>'d.id_condominio',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto'
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
            ->where(['id_user' => $id_user ]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);


        $this->loadModel('Asigna'); //if it's not already loaded
        $condoID = $this->Asigna
            ->find()
            ->select([
                'Asigna.id_condomino',
                'condominio'=>'c.id_condominio'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Asigna.id_condomino',
                ]

            ])
            ->where(['Asigna.id_condomino'=> $row->id_condomino]);
        $ids = $condoID->first();
        $this->set('ids', $ids);


        $this->loadModel('Condominio'); //if it's not already loaded
        $condoInf = $this->Condominio
            ->find()
            ->select([
                'id_condominio',
                'nombre_condominio',
                'logo',
                'direccion_condominio',
                'telefono_condominio'
            ])
            ->where(['id_condominio'=> $ids['condominio']]);
        $inf = $condoInf->first();
        $this->set('inf', $inf);

        $this->loadModel('Reglamento'); //if it's not already loaded
        $reglamento = $this->Reglamento
            ->find()
            ->select([
                'id_reglamento',
                'id_condominio',
                'nombre',
                'reglamento'
            ]);
        $this->set('reglamento', $this->paginate($reglamento));
        $this->set('_serialize', ['reglamento']);

        $mensajesTable = TableRegistry::get('Mensaje');
        $this->loadModel('Mensaje');
        $Query = $mensajesTable->find('all');
        $Query->select([
            'count' => $Query->func()->count('*')
        ])
            ->where(['id_condomino' => $id]);

        $total = $Query->first();
        $this->set('Query', $total);
    }

     public function comunicado($id = null)
     {
         $this->viewBuilder()->setLayout('');

         $id_user = $this->request->getSession()->read('Auth.User.id_user');
         $this->loadModel('Asigna');
         $query = $this->Asigna
             ->find()
             ->select([
                 'id_asigna',
                 'id_user',
                 'id_condomino',
                 'condominio'=>'d.id_condominio',
                 'residente'=>'d.residente_condominio',
                 'telefono'=>'d.numero_contacto',
                 'mail'=>'d.correo_contacto',
                 'marca'=>'d.marca_auto',
                 'modelo'=>'d.modelo_auto',
                 'color'=>'d.color_auto',
                 'placas'=>'d.placas_auto'
             ])
             ->join([
                 'd' => [
                     'table' => 'condomino', // nombre de tabla en MySQL
                     'type' => 'INNER',
                     'conditions' => 'd.id_condomino = Asigna.id_condomino',
                 ]
             ])
             ->where(['id_user' => $id_user ]);
         $row = $query->first();

         $this->set('user', $row);
         $this->set('_serialize', ['user']);


         $this->loadModel('Asigna'); //if it's not already loaded
         $condoID = $this->Asigna
             ->find()
             ->select([
                 'Asigna.id_condomino',
                 'condominio'=>'c.id_condominio'
             ])
             ->hydrate(false)     // pasarlo como un arreglo
             ->join([
                 'c' => [
                     'table' => 'condomino', // nombre de tabla en MySQL
                     'type' => 'INNER',
                     'conditions' => 'c.id_condomino = Asigna.id_condomino',
                 ]

             ])
             ->where(['Asigna.id_condomino'=> $row->id_condomino]);
         $ids = $condoID->first();
         $this->set('ids', $ids);


         $this->loadModel('Condominio'); //if it's not already loaded
         $condoInf = $this->Condominio
             ->find()
             ->select([
                 'id_condominio',
                 'nombre_condominio',
                 'logo',
                 'direccion_condominio',
                 'telefono_condominio'
             ])
             ->where(['id_condominio'=> $ids['condominio']]);
         $inf = $condoInf->first();
         $this->set('inf', $inf);


         $this->loadModel('Comunicado'); //if it's not already loaded
         $query3 = $this->Comunicado
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
             ->order(['id_comunicado'=>'DESC']);

         $this->set('comunicado', $this->paginate($query3));
         $this->set('_serialize', ['comunicado']);


         $mensajesTable = TableRegistry::get('Mensaje');
         $this->loadModel('Mensaje');
         $Query = $mensajesTable->find('all');
         $Query->select([
             'count' => $Query->func()->count('*')
         ])
             ->where(['id_condomino' => $id]);

         $total = $Query->first();
         $this->set('Query', $total);
     }

    public function mensajes($id = null)
    {
        $this->viewBuilder()->setLayout('');
        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'condominio'=>'d.id_condominio',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto'
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
        ->where(['id_user' => $id_user ]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);


        $this->loadModel('Asigna'); //if it's not already loaded
        $condoID = $this->Asigna
            ->find()
            ->select([
                'Asigna.id_condomino',
                'condominio'=>'c.id_condominio'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Asigna.id_condomino',
                ]

            ])
            ->where(['Asigna.id_condomino'=> $row->id_condomino]);
        $ids = $condoID->first();
        $this->set('ids', $ids);


        $this->loadModel('Condominio'); //if it's not already loaded
        $condoInf = $this->Condominio
            ->find()
            ->select([
                'id_condominio',
                'nombre_condominio',
                'logo',
                'direccion_condominio',
                'telefono_condominio'
            ])
            ->where(['id_condominio'=> $ids['condominio']]);
        $inf = $condoInf->first();
        $this->set('inf', $inf);

        $mensajesTable = TableRegistry::get('Mensaje');
        $mensajes = $mensajesTable->find()
            ->select([
                'id_mensaje',
                'titulo',
                'mensaje',
                'dir',
                'adjunto',
                'fecha',
                'id_condominio',
                'id_condomino'
            ])
            ->where(['id_condomino' => $row->id_condomino]);
        $mens = $mensajes->all();

        $this->set('mensajes', $mens);

        $mensajesTable = TableRegistry::get('Mensaje');
        $this->loadModel('Mensaje');
        $Query = $mensajesTable->find('all');
        $Query->select([
            'count' => $Query->func()->count('*')
        ])
            ->where(['id_condomino' => $id]);

        $total = $Query->first();
        $this->set('Query', $total);
    }

        /* ----------------------------- Mensajes -------------------------------- */

        public function nuevo($id = null)
    {
        $this->viewBuilder()->setLayout('');

        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'condominio'=>'d.id_condominio',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto'
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ]);
        //->where(['id_user' => $this->request->getSession()->read('Auth.Users.id_user')]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);


        $this->loadModel('Asigna'); //if it's not already loaded
        $condoID = $this->Asigna
            ->find()
            ->select([
                'Asigna.id_condomino',
                'condominio'=>'c.id_condominio'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Asigna.id_condomino',
                ]

            ])
            ->where(['Asigna.id_condomino'=> $row->id_condomino]);
        $ids = $condoID->first();
        $this->set('ids', $ids);


        $this->loadModel('Condominio'); //if it's not already loaded
        $condoInf = $this->Condominio
            ->find()
            ->select([
                'id_condominio',
                'nombre_condominio',
                'logo',
                'direccion_condominio',
                'telefono_condominio'
            ])
            ->where(['id_condominio'=> $ids['condominio']]);
        $inf = $condoInf->first();
        $this->set('inf', $inf);

        $this->loadModel('Mensaje');
        $mensaje = $this->Mensaje->newEntity();
        if ($this->request->is('post')) {
            $mensaje = $this->Mensaje->patchEntity($mensaje, $this->request->getData());
            if ($this->Mensaje->save($mensaje)) {
                $this->Flash->success(__('The mensaje has been saved.'));

                return $this->redirect($this->referer());
            }
            $this->Flash->error(__('The mensaje could not be saved. Please, try again.'));
        }
        $this->set(compact('mensaje'));


        $mensajesTable = TableRegistry::get('Mensaje');
        $this->loadModel('Mensaje');
        $Query = $mensajesTable->find('all');
        $Query->select([
            'count' => $Query->func()->count('*')
        ])
            ->where(['id_condomino' => $id]);

        $total = $Query->first();
        $this->set('Query', $total);
    }

    public function regla($id = null)
    {
        $this->viewBuilder()->setLayout('');

        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'condominio'=>'d.id_condominio',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto'
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
            ->where(['id_user' => $id_user ]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);


        $this->loadModel('Asigna'); //if it's not already loaded
        $condoID = $this->Asigna
            ->find()
            ->select([
                'Asigna.id_condomino',
                'condominio'=>'c.id_condominio'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Asigna.id_condomino',
                ]

            ])
            ->where(['Asigna.id_condomino'=> $row->id_condomino]);
        $ids = $condoID->first();
        $this->set('ids', $ids);


        $this->loadModel('Condominio'); //if it's not already loaded
        $condoInf = $this->Condominio
            ->find()
            ->select([
                'id_condominio',
                'nombre_condominio',
                'logo',
                'direccion_condominio',
                'telefono_condominio'
            ])
            ->where(['id_condominio'=> $ids['condominio']]);
        $inf = $condoInf->first();
        $this->set('inf', $inf);

        $mensajesTable = TableRegistry::get('Mensaje');
        $this->loadModel('Mensaje');
        $Query = $mensajesTable->find('all');
        $Query->select([
            'count' => $Query->func()->count('*')
        ])
            ->where(['id_condomino' => $id]);

        $total = $Query->first();
        $this->set('Query', $total);

        $this->loadModel('Reglamento');
        $reglamento = $this->Reglamento->get($id, [
            'contain' => [],
        ]);

        $this->set('reglamento', $reglamento);

    }

    public function pagos($id = null){
        $this->viewBuilder()->setLayout('');

        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'condominio'=>'d.id_condominio',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto'
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
            ->where(['id_user' => $id_user ]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);


        $this->loadModel('Asigna'); //if it's not already loaded
        $condoID = $this->Asigna
            ->find()
            ->select([
                'Asigna.id_condomino',
                'condominio'=>'c.id_condominio'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Asigna.id_condomino',
                ]

            ])
            ->where(['Asigna.id_condomino'=> $row->id_condomino]);
        $ids = $condoID->first();
        $this->set('ids', $ids);


        $this->loadModel('Condominio'); //if it's not already loaded
        $condoInf = $this->Condominio
            ->find()
            ->select([
                'id_condominio',
                'nombre_condominio',
                'logo',
                'direccion_condominio',
                'telefono_condominio'
            ])
            ->where(['id_condominio'=> $ids['condominio']]);
        $inf = $condoInf->first();
        $this->set('inf', $inf);


        $this->loadModel('Comunicado'); //if it's not already loaded
        $query3 = $this->Comunicado
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
            ->order(['id_comunicado'=>'DESC']);

        $this->set('comunicado', $this->paginate($query3));
        $this->set('_serialize', ['comunicado']);


        $mensajesTable = TableRegistry::get('Mensaje');
        $this->loadModel('Mensaje');
        $Query = $mensajesTable->find('all');
        $Query->select([
            'count' => $Query->func()->count('*')
        ])
            ->where(['id_condomino' => $id]);

        $total = $Query->first();
        $this->set('Query', $total);

        //------------- PAgo
        $this->loadModel('Pago'); //if it's not already loaded
        $quer = $this->Pago
            ->find()
            ->select([
                'id_pago',
                'condominio' => 'c.nombre_condominio',
                'condomino' => 'd.numero_departamento',
                'abono',
                'monto_letra',
                'concepto',
                'referencia',
                'tipo' => 't.nombre',
                'fecha_pago',
                'fecha'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condominio', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condominio = Pago.id_condominio',
                ]
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Pago.id_condomino',
                ]
            ])
            ->join([
                't' => [
                    'table' => 'tipo_pago', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 't.id_tipo = Pago.id_tipo',
                ]
            ])
            ->join([
                'a' => [
                    'table' => 'asigna', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'a.id_condomino = Pago.id_condomino',
                ]
            ])
            ->where([
                'a.id_condomino' => $row->id_condomino
            ])
        ;
        //   $pago = $this->paginate($query);
        //   $this->set(compact('pago'));
        //   $pago = $this->paginate($query);
        //   debug($query);
        $this->set('pago', $this->paginate($quer));
        $this->set('_serialize', ['pago']);

    }

    public function visita($id = null)
    {
        $this->viewBuilder()->setLayout('');

        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'condominio'=>'d.id_condominio',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto'
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
            ->where(['id_user' => $id_user ]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);


        $this->loadModel('Asigna'); //if it's not already loaded
        $condoID = $this->Asigna
            ->find()
            ->select([
                'Asigna.id_condomino',
                'condominio'=>'c.id_condominio'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Asigna.id_condomino',
                ]

            ])
            ->where(['Asigna.id_condomino'=> $row->id_condomino]);
        $ids = $condoID->first();
        $this->set('ids', $ids);


        $this->loadModel('Condominio'); //if it's not already loaded
        $condoInf = $this->Condominio
            ->find()
            ->select([
                'id_condominio',
                'nombre_condominio',
                'logo',
                'direccion_condominio',
                'telefono_condominio'
            ])
            ->where(['id_condominio'=> $ids['condominio']]);
        $inf = $condoInf->first();
        $this->set('inf', $inf);


        $this->loadModel('Comunicado'); //if it's not already loaded
        $query3 = $this->Comunicado
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
            ->order(['id_comunicado'=>'DESC']);

        $this->set('comunicado', $this->paginate($query3));
        $this->set('_serialize', ['comunicado']);


        $mensajesTable = TableRegistry::get('Mensaje');
        $this->loadModel('Mensaje');
        $Query = $mensajesTable->find('all');
        $Query->select([
            'count' => $Query->func()->count('*')
        ])
            ->where(['id_condomino' => $id]);

        $total = $Query->first();
        $this->set('Query', $total);


        $this->loadModel('Visitas'); //if it's not already loaded
        $visitas = $this->Visitas
            ->find();
        $this->set('visitas', $this->paginate($visitas));
        $this->set('_serialize', ['visitas']);

    }

    public function qr($id = null, $id_visita = null)
    {
        $this->viewBuilder()->setLayout('');

        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'condominio'=>'d.id_condominio',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto'
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
            ->where(['id_user' => $id_user ]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);


        $this->loadModel('Asigna'); //if it's not already loaded
        $condoID = $this->Asigna
            ->find()
            ->select([
                'Asigna.id_condomino',
                'condominio'=>'c.id_condominio'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Asigna.id_condomino',
                ]

            ])
            ->where(['Asigna.id_condomino'=> $row->id_condomino]);
        $ids = $condoID->first();
        $this->set('ids', $ids);


        $this->loadModel('Condominio'); //if it's not already loaded
        $condoInf = $this->Condominio
            ->find()
            ->select([
                'id_condominio',
                'nombre_condominio',
                'logo',
                'direccion_condominio',
                'telefono_condominio'
            ])
            ->where(['id_condominio'=> $ids['condominio']]);
        $inf = $condoInf->first();
        $this->set('inf', $inf);


        $this->loadModel('Comunicado'); //if it's not already loaded
        $query3 = $this->Comunicado
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
            ->order(['id_comunicado'=>'DESC']);

        $this->set('comunicado', $this->paginate($query3));
        $this->set('_serialize', ['comunicado']);


        $mensajesTable = TableRegistry::get('Mensaje');
        $this->loadModel('Mensaje');
        $Query = $mensajesTable->find('all');
        $Query->select([
            'count' => $Query->func()->count('*')
        ])
            ->where(['id_condomino' => $id]);

        $total = $Query->first();
        $this->set('Query', $total);



        $this->loadModel('Visitas'); //if it's not already loaded
          $quer = $this->Visitas
            ->find()
            ->where([
                'id_condomino' => $row->id_condomino
            ]);
        $qr = $quer->last();
        $this->set('visita', $qr);

    /*    $visita = $this->Visitas->get($id_visita, [
            'contain' => [],
        ]); */


    }

    public function qrList($id = null, $id_visita = null)
    {
        $this->viewBuilder()->setLayout('');

        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'condominio'=>'d.id_condominio',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto'
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
            ->where(['id_user' => $id_user ]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);


        $this->loadModel('Asigna'); //if it's not already loaded
        $condoID = $this->Asigna
            ->find()
            ->select([
                'Asigna.id_condomino',
                'condominio'=>'c.id_condominio'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Asigna.id_condomino',
                ]

            ])
            ->where(['Asigna.id_condomino'=> $row->id_condomino]);
        $ids = $condoID->first();
        $this->set('ids', $ids);


        $this->loadModel('Condominio'); //if it's not already loaded
        $condoInf = $this->Condominio
            ->find()
            ->select([
                'id_condominio',
                'nombre_condominio',
                'logo',
                'direccion_condominio',
                'telefono_condominio'
            ])
            ->where(['id_condominio'=> $ids['condominio']]);
        $inf = $condoInf->first();
        $this->set('inf', $inf);


        $this->loadModel('Comunicado'); //if it's not already loaded
        $query3 = $this->Comunicado
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
            ->order(['id_comunicado'=>'DESC']);

        $this->set('comunicado', $this->paginate($query3));
        $this->set('_serialize', ['comunicado']);


        $mensajesTable = TableRegistry::get('Mensaje');
        $this->loadModel('Mensaje');
        $Query = $mensajesTable->find('all');
        $Query->select([
            'count' => $Query->func()->count('*')
        ])
            ->where(['id_condomino' => $id]);

        $total = $Query->first();
        $this->set('Query', $total);

        $this->loadModel('Visitas'); //if it's not already loaded
        $visita = $this->Visitas->get($id, [
                'contain' => [],
            ]);
        $this->set('visita', $visita);



    }

    public function registrar($id = null, $id_visita = null)
    {
        $this->viewBuilder()->setLayout('');

        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'condominio'=>'d.id_condominio',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto'
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
            ->where(['id_user' => $id_user ]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);


        $this->loadModel('Asigna'); //if it's not already loaded
        $condoID = $this->Asigna
            ->find()
            ->select([
                'Asigna.id_condomino',
                'condominio'=>'c.id_condominio'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Asigna.id_condomino',
                ]

            ])
            ->where(['Asigna.id_condomino'=> $row->id_condomino]);
        $ids = $condoID->first();
        $this->set('ids', $ids);


        $this->loadModel('Condominio'); //if it's not already loaded
        $condoInf = $this->Condominio
            ->find()
            ->select([
                'id_condominio',
                'nombre_condominio',
                'logo',
                'direccion_condominio',
                'telefono_condominio'
            ])
            ->where(['id_condominio'=> $ids['condominio']]);
        $inf = $condoInf->first();
        $this->set('inf', $inf);


        $this->loadModel('Comunicado'); //if it's not already loaded
        $query3 = $this->Comunicado
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
            ->order(['id_comunicado'=>'DESC']);

        $this->set('comunicado', $this->paginate($query3));
        $this->set('_serialize', ['comunicado']);


        $mensajesTable = TableRegistry::get('Mensaje');
        $this->loadModel('Mensaje');
        $Query = $mensajesTable->find('all');
        $Query->select([
            'count' => $Query->func()->count('*')
        ])
            ->where(['id_condomino' => $id]);

        $total = $Query->first();
        $this->set('Query', $total);

        $this->loadModel('tipo_visita'); //if it's not already loaded
        $optionsTipo = $this->tipo_visita->find('list', ['keyField' => 'tipo_visita',
            'valueField' => 'tipo_visita']);
        $this->set('optionsTipo', $optionsTipo);

        $this->loadModel('Visitas');
        $visita = $this->Visitas->newEntity();
        if ($this->request->is('post')) {
            $visita = $this->Visitas->patchEntity($visita, $this->request->getData());
            if ($this->Visitas->save($visita)) {
                $this->Flash->success(__('The visita has been saved.'));

                return $this->redirect(['action' => 'qr', 'id'=> $id_visita]);
            }
            $this->Flash->error(__('The visita could not be saved. Please, try again.'));
        }
        $this->set(compact('visita'));
    }

    public function vigilante($id = null, $id_visita = null){

        $this->viewBuilder()->setLayout('');

        $id_user = $this->request->getSession()->read('Auth.User.id_user');
        $this->loadModel('Asigna');
        $query = $this->Asigna
            ->find()
            ->select([
                'id_asigna',
                'id_user',
                'id_condomino',
                'condominio'=>'d.id_condominio',
                'residente'=>'d.residente_condominio',
                'telefono'=>'d.numero_contacto',
                'mail'=>'d.correo_contacto',
                'marca'=>'d.marca_auto',
                'modelo'=>'d.modelo_auto',
                'color'=>'d.color_auto',
                'placas'=>'d.placas_auto'
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Asigna.id_condomino',
                ]
            ])
            ->where(['id_user' => $id_user ]);
        $row = $query->first();

        $this->set('user', $row);
        $this->set('_serialize', ['user']);


        $this->loadModel('Asigna'); //if it's not already loaded
        $condoID = $this->Asigna
            ->find()
            ->select([
                'Asigna.id_condomino',
                'condominio'=>'c.id_condominio'
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Asigna.id_condomino',
                ]

            ])
            ->where(['Asigna.id_condomino'=> $row->id_condomino]);
        $ids = $condoID->first();
        $this->set('ids', $ids);


        $this->loadModel('Condominio'); //if it's not already loaded
        $condoInf = $this->Condominio
            ->find()
            ->select([
                'id_condominio',
                'nombre_condominio',
                'logo',
                'direccion_condominio',
                'telefono_condominio'
            ])
            ->where(['id_condominio'=> $ids['condominio']]);
        $inf = $condoInf->first();
        $this->set('inf', $inf);


        $this->loadModel('Comunicado'); //if it's not already loaded
        $query3 = $this->Comunicado
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
            ->order(['id_comunicado'=>'DESC']);

        $this->set('comunicado', $this->paginate($query3));
        $this->set('_serialize', ['comunicado']);


        $mensajesTable = TableRegistry::get('Mensaje');
        $this->loadModel('Mensaje');
        $Query = $mensajesTable->find('all');
        $Query->select([
            'count' => $Query->func()->count('*')
        ])
            ->where(['id_condomino' => $id]);

        $total = $Query->first();
        $this->set('Query', $total);

        $this->loadModel('Visitas'); //if it's not already loaded
        $visita = $this->Visitas->get($id, [
            'contain' => [],
        ]);
        $this->set('visita', $visita);


    }

}
