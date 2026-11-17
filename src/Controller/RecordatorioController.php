<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Mailer\Email;

/**
 * Recordatorio Controller
 *
 * @property \App\Model\Table\RecordatorioTable $Recordatorio
 *
 * @method \App\Model\Entity\Recordatorio[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class RecordatorioController extends AppController
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
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $recordatorio = $this->paginate($this->Recordatorio);

        $this->set(compact('recordatorio'));
    }

    /**
     * View method
     *
     * @param string|null $id Recordatorio id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $recordatorio = $this->Recordatorio->get($id, [
            'contain' => [],
        ]);

        $this->set('recordatorio', $recordatorio);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {

        $recordatorio = $this->Recordatorio->newEntity();
        if ($this->request->is('post')) {
            $recordatorio = $this->Recordatorio->patchEntity($recordatorio, $this->request->getData());
            if ($this->Recordatorio->save($recordatorio)) {
                $this->Flash->success(__('The recordatorio has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The recordatorio could not be saved. Please, try again.'));
        }
        $this->set(compact('recordatorio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Recordatorio id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $recordatorio = $this->Recordatorio->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $recordatorio = $this->Recordatorio->patchEntity($recordatorio, $this->request->getData());
            if ($this->Recordatorio->save($recordatorio)) {
                $this->Flash->success(__('The recordatorio has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The recordatorio could not be saved. Please, try again.'));
        }
        $this->set(compact('recordatorio'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Recordatorio id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $recordatorio = $this->Recordatorio->get($id);
        if ($this->Recordatorio->delete($recordatorio)) {
            $this->Flash->success(__('The recordatorio has been deleted.'));
        } else {
            $this->Flash->error(__('The recordatorio could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function recordatorio($id_condominio = null){

        $this->loadModel('condomino');
        $optionsCorreos = $this->condomino->find('list', ['keyField' => 'correo_contacto',
            'valueField' => 'correo_contacto'])
            ->where([
            'id_condominio'=>$id_condominio
        ]);
        $this->set('correos', $optionsCorreos);

        $this->loadModel('Condomino');
        $query = $this->Condomino
            ->find()
            ->select([
                'id_condomino',
                'id_condominio',
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
                'c.id_condominio'=>$id_condominio
            ]);
        $this->set('condomino', $this->paginate($query));
        $this->set('_serialize', ['condomino']);

        $this->set('id_condominio', $id_condominio);

        $recordatorio = $this->Recordatorio->newEntity();
        if ($this->request->is('post')) {
            $recordatorio = $this->Recordatorio->patchEntity($recordatorio, $this->request->getData());
            if ($this->Recordatorio->save($recordatorio)) {
                $this->Flash->success(__('The recordatorio has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The recordatorio could not be saved. Please, try again.'));
        }
        $this->set(compact('recordatorio'));

    }


    public function getCorreos($id_condominio = null){
        $id = $this->request->getQuery('id_condominio');

        $condominoTable = TableRegistry::get('Condomino');
        $varFiltro = $this->request->getQuery('search');
        $varPage = $this->request->getQuery('page');
        $varLimit = $this->request->getQuery('size');
        if($varPage == 'NaN'){
            $varPage=1;
        }
        Log::debug('Buscar: '.$varFiltro.' page: '. $varPage);
        $query= $condominoTable
            ->find()
            ->select([
                'id'=>'id_condomino',
                'text'=>'correo_contacto'
            ])
            ->where([
                'correo_contacto LIKE' => '%'.$varFiltro.'%',
            ])
            //->where([
            //    'id_condominio' => $id_condominio
            //])
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


    public function sendEmail($id_condominio = null){
        $condominoTable = TableRegistry::get('Condomino');
        $condomino = $condominoTable->find()
                                    ->where(['id_condomino' => $id_condominio])
                                    ->first();

        foreach ($condomino as $correos){

        }

        $email = new Email();
        $email->setTransport('alpa')
            ->setEmailFormat('html')
            ->setTo($correos->correo_contacto)
            ->setCc('recibos@alpa-condominios.com')
            ->setCc('bitacora@alpaadministraciones.com')
            ->setFrom('administracion@pagoadministracion.com');
        $email->setAttachments([
            'adjunto.pdf' => [
                'file' => WWW_ROOT. 'estado.pdf',
                'mimetype' => 'application/pdf'
            ]
        ]);
        $message = '<h2> Estimado '  . $condomino->residente_condominio . '</h2>'
            .  '<br><p>Nos permitimos enviarle su estado de cuenta, le recordamos que la fecha limite de pago es el día 5. </p>'
            . '<br><p>En caso de faltar alguno, le solicitamos envié el comprobante del faltante '
            . 'al correo de su condominio.</p>';
        $email->setSubject('Recibo de pago de administracion')
            ->send($message);
    }

}
