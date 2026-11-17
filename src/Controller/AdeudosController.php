<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * Adeudos Controller
 *
 * @property \App\Model\Table\AdeudosTable $Adeudos
 *
 * @method \App\Model\Entity\Adeudo[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class AdeudosController extends AppController
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
    public function index($id_condomino = null)
    {
        //$adeudos = $this->paginate($this->Adeudos);
        //$this->set(compact('adeudos'));

        $query = $this->Adeudos
            ->find()
            ->select([
                'id_adeudo',
                'id_condominio',
                'id_condomino',
                'mes',
                'monto',
                'concepto',
                'estatus',
                'departamento' => 'c.numero_departamento',
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = T.id_condomino',
                ]

            ])
            ->where([
                'T.id_condomino'=>$id_condomino
            ])
            ->where([
                'T.estatus'=>1
            ]);
        $this->set('adeudos', $this->paginate($query));
        $this->set('_serialize', ['adeudos']);
        $this->set('id_condomino', $id_condomino);

        $query2 = $this->Adeudos->find();
        $query2->select([
                'sum' => $query2->func()->sum('T.monto')])
            ->where([
                'id_condomino' => $id_condomino
            ])
            ->where([
                'T.estatus'=>1
            ]);
        $total = $query2->first();
        $this->set('query2', $total);

        $query3 = $this->Adeudos->find();
        $query3->select([
            'min' => $query2->func()->min('T.monto')])
            ->where([
                'id_condomino' => $id_condomino
            ])
            ->where([
                'T.estatus'=>1
            ]);
        $min = $query3->first();
        $this->set('query3', $min);

        $favorTable = TableRegistry::get('Favor');
        $favor = $favorTable->find();
        $favor->select([
            'sum' => $favor->func()->sum('abono')])
            ->where(['id_condomino'=> $id_condomino]);
        //$this->set('favor', $favor);
        $favorSaldo = $favor->first();
        $this->set('favor', $favorSaldo);

    }

    /**
     * View method
     *
     * @param string|null $id Adeudo id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $adeudo = $this->Adeudos->get($id, [
            'contain' => [],
        ]);

        $this->set('adeudo', $adeudo);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $adeudo = $this->Adeudos->newEntity();
        if ($this->request->is('post')) {
            $adeudo = $this->Adeudos->patchEntity($adeudo, $this->request->getData());
            if ($this->Adeudos->save($adeudo)) {
                $this->Flash->success(__('The adeudo has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The adeudo could not be saved. Please, try again.'));
        }
        $this->set(compact('adeudo'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Adeudo id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $adeudo = $this->Adeudos->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $adeudo = $this->Adeudos->patchEntity($adeudo, $this->request->getData());
            if ($this->Adeudos->save($adeudo)) {
                $this->Flash->success(__('The adeudo has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The adeudo could not be saved. Please, try again.'));
        }
        $this->set(compact('adeudo'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Adeudo id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $adeudo = $this->Adeudos->get($id);
        if ($this->Adeudos->delete($adeudo)) {
            $this->Flash->success(__('The adeudo has been deleted.'));
        } else {
            $this->Flash->error(__('The adeudo could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function restarAdeudos($id_condomino = null){
        $adeudo = $this->Adeudos->newEntity();
        if ($this->request->is('post')) {
            $adeudo = $this->Adeudos->patchEntity($adeudo, $this->request->getData());
            if ($this->Adeudos->save($adeudo)) {
                $this->Flash->success(__('The adeudo has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The adeudo could not be saved. Please, try again.'));
        }
        $this->set(compact('adeudo'));
    }

    protected function restaAdeudo($id_condomino = null, $pago = null){

        $favorTable = TableRegistry::get('Favor');
        $favor = $favorTable->find()->where(['id_condomino'=> $id_condomino])
            ->last();
        $this->set('favor', $favor);

        $query2 = $this->Adeudos->find();
        $query2->select([
            'sum' => $query2->func()->sum('T.monto')])
            ->where([
                'id_condomino' => $id_condomino
            ])
            ->where([
                'T.estatus'=>1
            ]);
        $total = $query2->first();


    }

    public function pagoParcial($id = null){
        $adeudosTable = TableRegistry::get('Adeudos');
        $adeudo = $adeudosTable->get($id);

        $favorTable = TableRegistry::get('Favor');
        $favor = $favorTable->find()->where(['id_condomino'=> $adeudo->id_condomino])
            ->first();

        if ($adeudo->monto > $favor->abono){
            $resta = $adeudo->monto - $favor->abono;

            $favor = TableRegistry::get('Adeudos');
            $query = $favor->query();
            $query->update()
                ->set(['monto' => $resta])
                ->where(['id_condomino'=> $adeudo->id_condomino])
                ->where(['id_adeudo'=> $adeudo->id_adeudo])
                ->execute();

        }else{
            $adeudosTable = TableRegistry::get('Adeudos');
            $adeudo = $adeudosTable->get($id);

            $adeudo->estatus = 0;
            $adeudosTable->save($adeudo);

            $result['message'][] = "Actualizado.";
            return $result;
        }


    }

    function pagoCerrado($monto, $id_condomino, $id_condominio){
        $adeudosTable = TableRegistry::getTableLocator()->get('Adeudos');
        $adeudo = $adeudosTable->newEntities();

        $adeudo->estatus = 1;
        $adeudo->id_condominio = $id_condominio;
        $adeudo->id_condomino = $id_condomino;
        $adeudo->monto = $monto;
        $adeudosTable->save($adeudo);

        $result['message'][] = "Actualizado.";
        return $result;
    }

    public function cerrar($id = null){
        $adeudosTable = TableRegistry::get('Adeudos');
        $adeudo = $adeudosTable->get($id);

            $adeudo->estatus = 0;
            $adeudosTable->save($adeudo);

            $result['message'][] = "Actualizado.";
            return $result;
    }

    public function ceros($id = null){
        $adeudosTable = TableRegistry::get('Adeudos');
        $adeudo = $adeudosTable->get($id);

        $favorTable = TableRegistry::get('Favor');
        $favor = $favorTable->find()->where(['id_condomino'=> $adeudo->id_condomino])
            ->first();

        if($favor < 0){
            echo "Error";
        }else{
            $favor = TableRegistry::get('Favor');
            $query = $favor->query();
            $query->update()
                ->set(['abono' => 0])
                ->where(['id_condomino'=> $adeudo->id_condomino])
                ->execute();
        }

    }


    public function actualizaSaldo($id = null){

        $adeudosTable = TableRegistry::getTableLocator()->get('Adeudos');
        $adeudo = $adeudosTable->get($id);

        $favorTable = TableRegistry::getTableLocator()->get('Favor');
        $favor = $favorTable->find()->where(['id_condomino'=> $adeudo->id_condomino])
            ->first();

        if($favor === 0 || is_null($favor) || $favor == '' || $favor < 0){
          echo "Error";
        }elseif($adeudo->monto == $favor->abono || $adeudo->monto > $favor->abono){

            $suma = $adeudo->monto - $favor->abono;
            debug.log("Resta del abono --> " + $suma);
            $favor = TableRegistry::getTableLocator()->get('Favor');

            $query = $favor->query();
            $query->update()
                ->set(['abono' => 0])
                ->where(['id_condomino'=> $adeudo->id_condomino])
                ->execute();

            $adeudo->estatus = 0;
            $adeudo->monto = $suma;
            $adeudosTable->save($adeudo); //actualice estatus

            if ($suma != 0) {
                $adeudoNew = $adeudosTable->newEntity();
                $adeudoNew->estatus = 1;
                $adeudoNew->id_condominio = $adeudo->id_condominio;
                $adeudoNew->id_condomino = $adeudo->id_condomino;
                $adeudoNew->concepto = $adeudo->concepto."-faltante";
                $adeudoNew->mes = $adeudo->mes;
                $adeudoNew->id_tipo = $adeudo->id_tipo;
                $adeudoNew->monto = $suma;

                if ($adeudosTable->save($adeudoNew)) {
                    $message = ['message' => 'Se guardo con éxito el adeudo - ' . $adeudoNew->id . ' - '];
                } else {
                    $message = ['message' => 'Error al guardar los datos'];
                    Log::debug($adeudoNew->errors());
                }
            }

            // the order of these three lines is very important !!
            $message = ['message' => 'Error al guardar los datos'];
            $resultJ = json_encode($message);
            $this->response->type('json');
            $this->response->body($resultJ);



            }elseif ($adeudo->monto < $favor->abono){

            $suma = $favor->abono - $adeudo->monto;
            debug("Resta del abono --> " + $suma);
            $favor = TableRegistry::get('Favor');
            $query = $favor->query();
            $query->update()
                ->set(['abono' => $suma])
                ->where(['id_condomino'=> $adeudo->id_condomino])
                ->execute();

            $adeudosTable = TableRegistry::get('Adeudos');
            $adeudo = $adeudosTable->get($id);

            $adeudo->estatus = 0;
            $adeudosTable->save($adeudo);

            $message = ['message' => 'Se guardo con éxito el adeudo - ' . $adeudo->id . ' - '];

            $resultJ = json_encode($message);
            $this->response->type('json');
            $this->response->body($resultJ);
       }

        return $this->response;

   }

   public function restaCuota($id = null){
       $adeudosTable = TableRegistry::get('Adeudos');
       $adeudo = $adeudosTable->get($id);

       $favorTable = TableRegistry::get('Favor');
       $favor = $favorTable->find()->where(['id_condomino'=> $adeudo->id_condomino])
           ->last();

       if($favor === 0 || is_null($favor) || $favor == ''){
           echo "Error";
       }else{
           $suma = $favor->abono - $adeudo->monto;

           $adeudo = TableRegistry::get('Adeudo');
           $query = $favor->query();
           $query->update()
               ->set(['abono' => $suma])
               ->where(['id_condomino'=> $adeudo->id_condomino])
               ->execute();
       }
   }

   public function movimientoPagos($id = null){
       $adeudosTable = TableRegistry::get('Adeudos');
       $adeudo = $adeudosTable->get($id);

       $favorTable = TableRegistry::get('Favor');
       $favor = $favorTable->find()->where(['id_condomino'=> $adeudo->id_condomino])
           ->last();

       if($favor === 0 || is_null($favor) || $favor == '' || $favor < 0){
           echo "Error";
       }else{
           $suma = $favor->abono - $adeudo->monto;

           $favor = TableRegistry::get('Favor');
           $query = $favor->query();
           $query->update()
               ->set(['abono' => $suma])
               ->where(['id_condomino'=> $adeudo->id_condomino])
               ->execute();

           $adeudosTable = TableRegistry::get('Adeudos');
           $adeudo = $adeudosTable->get($id);

           $adeudo->estatus = 0;
           $adeudosTable->save($adeudo);

           $result['message'][] = "Actualizado.";
           return $result;
       }




   }


}
