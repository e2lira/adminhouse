<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use NumerosEnLetras;

/**
 * Conceptos Controller
 *
 * @property \App\Model\Table\ConceptosTable $Conceptos
 *
 * @method \App\Model\Entity\Concepto[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ConceptosController extends AppController
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
    public function index($id = null, $id_gasto = null)
    {
        $conceptos = $this->paginate($this->Conceptos);
        $this->set(compact('conceptos'));

        $query = $this->Conceptos
            ->find()
            ->select([
                'id_conceptos',
                'id_gasto',
                'id_condominio',
                'gasto',
                'monto_letra',
                'concept',
                'fecha',
                'fecha_pago'
            ])
            ->where(['id_gasto'=>$id]);

        $concepto = $this->Conceptos->newEntity();
        if ($this->request->is('post')) {
            $concepto = $this->Conceptos->patchEntity($concepto, $this->request->getData());
            if ($this->Conceptos->save($concepto)) {
                $this->saveCierre($concepto);
                $this->Flash->success(__('The concepto has been saved.'));

                return $this->redirect($this->referer());
            }
            $this->Flash->error(__('The concepto could not be saved. Please, try again.'));
        }

        $this->set(compact('concepto'));

        $this->set('conceptos', $this->paginate($query));
        $this->set('_serialize', ['conceptos']);
        $this->set('id_gasto', $id_gasto);

        // Defaults for the "new concept" form when the list has no rows to take them from.
        $caja = $id === null ? null : TableRegistry::get('Caja')->find()->where(['id_gasto' => $id])->first();
        $this->set('formGastoId', $id);
        $this->set('formCondominioId', $caja ? $caja->id_condominio : null);

        $query2 = $this->Conceptos->find();
        $query2->select([
            'sum' => $query2->func()->sum(\App\Database\SqlDialect::forConnection()->textAsNumber('gasto'))])
            ->where(['id_gasto'=>$id]);
        $total = $query2->first();
        $this->set('query2', $total);

    }

    /**
     * View method
     *
     * @param string|null $id Concepto id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $concepto = $this->Conceptos->get($id, [
            'contain' => [],
        ]);

        $this->set('concepto', $concepto);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add($id_gasto = null)
    {
        $cajaTable = TableRegistry::get('Caja');

        $gasto = $cajaTable->get($id_gasto,[
            'contain'=>[]
        ]);


        $concepto = $this->Conceptos->newEntity();
        if ($this->request->is('post')) {
            $concepto = $this->Conceptos->patchEntity($concepto, $this->request->getData());
            if ($this->Conceptos->save($concepto)) {
                $this->saveCierre($concepto);

                $this->Flash->success(__('The concepto has been saved.'));

                return $this->redirect($this->referer());
            }
            $this->Flash->error(__('The concepto could not be saved. Please, try again.'));
        }
        $this->set(compact('concepto','gasto'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Concepto id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $concepto = $this->Conceptos->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $concepto = $this->Conceptos->patchEntity($concepto, $this->request->getData());
            if ($this->Conceptos->save($concepto)) {
                $this->Flash->success(__('The concepto has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The concepto could not be saved. Please, try again.'));
        }
        $this->set(compact('concepto'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Concepto id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $concepto = $this->Conceptos->get($id);
        if ($this->Conceptos->delete($concepto)) {
            $this->Flash->success(__('The concepto has been deleted.'));
        } else {
            $this->Flash->error(__('The concepto could not be deleted. Please, try again.'));
        }

        return $this->redirect($this->referer());
    }

    public function montoletras()
    {
        $gasto = $this->request->getQuery('gasto');
        $letras = NumerosEnLetras::convertir($gasto,'pesos', false, 'centavos');
        //$letras = $letras .  "pesos 00/100 M.N.";
        $resultJ = json_encode(['gasto' => $letras]);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;
    }

    public function saveCierre($concepto)
    {
        $cierreTable = TableRegistry::get('SaldoCierre');

        $fechaPago = new \DateTime($concepto->fecha);
        $mes = $fechaPago->format('m');
        $anio = $fechaPago->format('Y');

        $cierre = $cierreTable->find()
            ->where([
                'id_condominio' => $concepto->id_condominio,
                'MONTH(fecha)' => $mes,
                'YEAR(fecha)' => $anio
            ])
            ->first();

        if ($cierre) {
            $cierre->saldo = $cierre->saldo - $concepto->gasto;
        } else {
            $cierre = $cierreTable->newEntity();
            $cierre->id_condominio = $concepto->id_condominio;
            $cierre->fecha = $concepto->fecha;
            $cierre->saldo = $concepto->gasto;
        }

        if ($cierreTable->save($cierre)) {
            $this->Flash->success(__('El saldo cierre ha sido actualizado.'));
        } else {
            $this->Flash->error(__('El saldo cierre no se pudo guardar. Por favor, intenta de nuevo.'));
        }
    }
}
