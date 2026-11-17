<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * SaldoCondominio Controller
 *
 * @property \App\Model\Table\SaldoCondominioTable $SaldoCondominio
 *
 * @method \App\Model\Entity\SaldoCondominio[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class SaldoCondominioController extends AppController
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
    public function index($id_condominio = null)
    {
        //$saldoCondominio = $this->paginate($this->SaldoCondominio);
        //$this->set(compact('saldoCondominio'));

        $saldoCondominioTable = TableRegistry::get('saldo_condominio');
        $saldoCondominio = $saldoCondominioTable->find();
        // PostgreSQL requires every selected column to be grouped or aggregated; MySQL keeps
        // its original query, which returns one arbitrary row per month.
        $postgres = \App\Database\SqlDialect::forConnection()->isPostgres();
        $saldoCondominio->select([
            'id_saldo' => $postgres ? $saldoCondominio->newExpr('MIN(id_saldo)') : 'id_saldo',
            'saldo' => $postgres ? $saldoCondominio->newExpr('MIN(saldo)') : 'saldo',
            'fecha' => $postgres ? $saldoCondominio->newExpr('MIN(fecha)') : 'fecha',
            'sum' => $saldoCondominio->func()->sum('saldo')])
            ->where(['id_condominio' => $id_condominio])
            ->group('month(fecha)');

        $this->set('saldoCondominio', $this->paginate($saldoCondominio));
        $this->set('_serialize', ['saldoCondominio']);

        $this->set('id_condominio', $id_condominio);
    }

    /**
     * View method
     *
     * @param string|null $id Saldo Condominio id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $saldoCondominio = $this->SaldoCondominio->get($id, [
            'contain' => [],
        ]);

        $this->set('saldoCondominio', $saldoCondominio);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add($id_condominio = null)
    {
        $saldoCondominio = $this->SaldoCondominio->newEntity();
        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio, [
            'contain' => []
        ]);

        $this->set('id_condominio', $id_condominio);

        if ($this->request->is('post')) {
            $saldoCondominio = $this->SaldoCondominio->patchEntity($saldoCondominio, $this->request->getData());

            if ($this->SaldoCondominio->save($saldoCondominio)) {
                $saldoCierreTable = TableRegistry::get('SaldoCierre');
                $saldoCierre = $saldoCierreTable->newEntity();

                $saldoCierre->id_condominio = $saldoCondominio->id_condominio;
                $saldoCierre->saldo = $saldoCondominio->saldo;
                $saldoCierre->fecha = $saldoCondominio->fecha;

                if ($saldoCierreTable->save($saldoCierre)) {
                    $this->Flash->success(__('El saldo condominio y saldo cierre han sido guardados.'));
                } else {
                    $this->Flash->error(__('El saldo cierre no se pudo guardar. Por favor, intenta de nuevo.'));
                }

                return $this->redirect(['action' => 'index', 'id' => $id_condominio]);
            }
            $this->Flash->error(__('El saldo condominio no pudo ser guardado. Por favor, intenta de nuevo.'));
        }
        $this->set(compact('saldoCondominio', 'condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Saldo Condominio id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $saldoCondominio = $this->SaldoCondominio->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $saldoCondominio = $this->SaldoCondominio->patchEntity($saldoCondominio, $this->request->getData());
            if ($this->SaldoCondominio->save($saldoCondominio)) {
                $this->Flash->success(__('The saldo condominio has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The saldo condominio could not be saved. Please, try again.'));
        }
        $this->set(compact('saldoCondominio'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Saldo Condominio id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $saldoCondominio = $this->SaldoCondominio->get($id);
        if ($this->SaldoCondominio->delete($saldoCondominio)) {
            $this->Flash->success(__('The saldo condominio has been deleted.'));
        } else {
            $this->Flash->error(__('The saldo condominio could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
