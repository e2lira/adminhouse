<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * SaldoCondomino Controller
 *
 * @property \App\Model\Table\SaldoCondominoTable $SaldoCondomino
 *
 * @method \App\Model\Entity\SaldoCondomino[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class SaldoCondominoController extends AppController
{
    public function isAuthorized($user)
    {

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
        //$saldoCondomino = $this->paginate($this->SaldoCondomino);
        //$this->set(compact('saldoCondomino'));

        $saldoCondominoTable = TableRegistry::get('saldo_condomino');
        $saldoCondomino = $saldoCondominoTable->find()
            ->where(['id_condomino' => $id_condomino]);

        $this->set('saldoCondomino', $this->paginate($saldoCondomino));
        $this->set('_serialize', ['saldoCondomino']);

        $this->set('id_condomino', $id_condomino);
    }

    /**
     * View method
     *
     * @param string|null $id Saldo Condomino id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $saldoCondomino = $this->SaldoCondomino->get($id, [
            'contain' => [],
        ]);

        $this->set('saldoCondomino', $saldoCondomino);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add($id_condomino = null, $id_condominio = null)
    {
        $saldoCondomino = $this->SaldoCondomino->newEntity();

        // Load the Condomino table
        $condominoTable = TableRegistry::get('Condomino');
        $condomino = $condominoTable->get($id_condomino, [
            'contain' => []
        ]);

        if ($this->request->is('post')) {
            // Patch the entity with the request data
            $saldoCondomino = $this->SaldoCondomino->patchEntity($saldoCondomino, $this->request->getData());

            // Save the SaldoCondomino entity
            if ($this->SaldoCondomino->save($saldoCondomino)) {
                // Load the CierreCondomino table and create a new entity
                $saldoCierreTable = TableRegistry::get('CierreCondomino');
                $saldoCierre = $saldoCierreTable->newEntity();

                // Set the values for saldoCierre from saldoCondomino
                $saldoCierre->id_condomino = $saldoCondomino->id_condomino;
                $saldoCierre->id_condominio = $condomino->id_condominio;
                $saldoCierre->saldo = $saldoCondomino->saldo;
                $saldoCierre->fecha = $saldoCondomino->fecha;

                // Save the CierreCondomino entity
                if ($saldoCierreTable->save($saldoCierre)) {
                    $this->Flash->success(__('The cierre condomino has been saved.'));

                    // Now save the favor entity
                    $favorTable = TableRegistry::get('Favor');
                    $favor = $favorTable->newEntity();
                    $favor->id_pago = $saldoCondomino->id_pago;
                    $favor->id_condominio = $id_condominio;
                    $favor->id_condomino = $id_condomino;
                    $favor->abono = $saldoCondomino->saldo;
                    $favor->fecha = $saldoCondomino->fecha;
                    $favor->estatus = 1;

                    if ($favorTable->save($favor)) {
                        $this->Flash->success(__('The favor has been saved.'));
                    } else {
                        $this->Flash->error(__('The favor could not be saved. Please, try again.'));
                        debug($favor->getErrors());
                    }

                } else {
                    $this->Flash->error(__('The cierre condomino could not be saved. Please, try again.'));
                    // Debugging: Output validation errors for CierreCondomino
                    debug($saldoCierre->getErrors());
                }

                // If contra field is set, create an adeudo
                if ($saldoCondomino->contra) {
                    $adeudo = TableRegistry::get('Adeudos');
                    $adeudo->query()
                        ->insert(['id_condominio', 'id_condomino', 'mes', 'monto', 'concepto'])
                        ->values([
                            'id_condominio' => $id_condominio,
                            'id_condomino' => $saldoCondomino->id_condomino,
                            'mes' => $saldoCondomino->fecha,
                            'monto' => $saldoCondomino->contra,
                            'concepto' => "Saldo_inicial_" . $saldoCondomino->fecha
                        ])
                        ->execute();
                }

                $this->Flash->success(__('The saldo condomino has been saved.'));
                return $this->redirect(['action' => 'index', 'id' => $id_condomino]);
            }

            $this->Flash->error(__('The saldo condomino could not be saved. Please, try again.'));
            debug($saldoCondomino->getErrors());
        }

        $this->set(compact('saldoCondomino', 'condomino', 'id_condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Saldo Condomino id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $saldoCondomino = $this->SaldoCondomino->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $saldoCondomino = $this->SaldoCondomino->patchEntity($saldoCondomino, $this->request->getData());
            if ($this->SaldoCondomino->save($saldoCondomino)) {
                $this->Flash->success(__('The saldo condomino has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The saldo condomino could not be saved. Please, try again.'));
        }
        $this->set(compact('saldoCondomino'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Saldo Condomino id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null, $id_condomino = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $saldoCondomino = $this->SaldoCondomino->get($id);
        if ($this->SaldoCondomino->delete($saldoCondomino)) {

            if ($saldoCondomino->saldo != 0) {
                $favorTable = TableRegistry::get('Favor');
                $favor = $favorTable->find()->where(['id_condomino' => $saldoCondomino->id_condomino])
                    ->last();
                $resta = abs($saldoCondomino->saldo) - abs($favor->abono);
                $favor = TableRegistry::get('Favor');
                $query = $favor->query();
                $query->update()
                    ->set(['abono' => abs($resta)])
                    ->where(['id_condomino' => $saldoCondomino->id_condomino])
                    ->execute();
            }

            if ($saldoCondomino->contra != 0) {

                $adeudoTable = TableRegistry::get('Adeudos');
                $adeudo = $adeudoTable->find()->where(['id_condomino' => $saldoCondomino->id_condomino])
                    ->last();

                $adeudo = TableRegistry::get('Adeudos');
                $query = $adeudo->query()->first();
                $query->update()
                    ->set(['estatus' => 0])
                    ->where(['id_condomino' => $saldoCondomino->id_condomino])
                    ->where(['monto' => $saldoCondomino->contra])
                    ->execute();

            }

            $this->Flash->success(__('The saldo condomino has been deleted.'));
        } else {
            $this->Flash->error(__('The saldo condomino could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index', 'id' => $id_condomino]);
    }

    public function actualizaSaldo($saldoCondomino, $id_condominio = null)
    {

        $saldoFavor = TableRegistry::getTableLocator()->get('Favor');
        $saldo = $saldoFavor->newEntity();

        $favorTable = TableRegistry::get('Favor');
        $favor = $favorTable->find()->where(['id_condomino' => $saldoCondomino->id_condomino])
            ->last();

        if ($favor === 0 || is_null($favor) || $favor == '') {
            $saldo->abono = $saldoCondomino->saldo;
            $saldo->id_condominio = $this->request->data['id_condominio'];
            $saldo->id_condomino = $saldoCondomino->id_condomino;
            $saldo->fecha = $saldoCondomino->fecha;
            $saldo->estatus = 1;
            $saldoFavor->save($saldo);
        } else {
            $suma = $saldoCondomino->saldo + $favor->abono;

            $favor = TableRegistry::get('Favor');
            $query = $favor->query();
            $query->update()
                ->set(['abono' => $suma])
                ->where(['id_condomino' => $saldoCondomino->id_condomino])
                ->execute();
        }
    }

    public function actualizaAdeudo($saldoCondomino, $id_condominio = null)
    {

        $adeudosFavor = TableRegistry::getTableLocator()->get('Adeudos');
        $adeudo = $adeudosFavor->newEntity();

        $adeudosFavor = TableRegistry::get('Adeudos');
        $contra = $adeudosFavor->find()->where(['id_condomino' => $saldoCondomino->id_condomino])
            ->last();

        if ($adeudo === 0 || is_null($adeudo) || $adeudo == '') {
            $adeudo->monto = $saldoCondomino->saldo;
            $adeudo->id_condominio = $this->request->data['id_condominio'];
            $adeudo->id_condomino = $saldoCondomino->id_condomino;
            $adeudo->fecha = $saldoCondomino->fecha;
            $adeudo->estatus = 1;
            $adeudosFavor->save($adeudo);
        } else {
            $suma = $saldoCondomino->saldo + $adeudo->monto;

            $favor = TableRegistry::get('Favor');
            $query = $favor->query();
            $query->update()
                ->set(['abono' => $suma])
                ->where(['id_condomino' => $saldoCondomino->id_condomino])
                ->execute();
        }
    }


}
