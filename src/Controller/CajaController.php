<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Caja Controller
 *
 * @property \App\Model\Table\CajaTable $Caja
 *
 * @method \App\Model\Entity\Caja[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CajaController extends AppController
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
    public function index($id_condominio = null, $id = null)
    {


        $condominioTable = TableRegistry::get('Condominio');

        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);

        //$caja = $this->paginate($this->Caja);
        //$this->set(compact('caja'));


            $query = $this->Caja
                ->find()
                ->select([
                    'id_gasto',
                    'id_condominio',
                    'tipo'=>  'c.tipo_gasto',
                    'fecha',
                    'fecha_pago',
                ])
                ->hydrate(false)
                ->join([
                    'c'=>[
                        'table' => 'tipo_gasto', // nombre de tabla en MySQL
                        'type' => 'INNER',
                        'conditions' => \App\Database\SqlDialect::forConnection()->integerAsText('c.id_tipo') . ' = caja.id_tipo',
                    ]
                ])
                ->where(['caja.id_condominio'=>$id_condominio]);

            $this->set('caja', $this->paginate($query));
            $this->set('_serialize', ['caja']);
            $this->set('id_tipo', $id);
            $this->set('condominio', $condominio);
    }

    /**
     * View method
     *
     * @param string|null $id Caja id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
      $caja = $this->Caja->get($id, [
            'contain' => [],
        ]);
        $this->set('caja', $caja);

        $this->loadModel('Conceptos'); //if it's not already loaded
        $conceptos = $this->Conceptos
            ->find()
            ->select([
                'id_gasto',
                'id_condominio',
                'concept',
                'gasto',
                'monto_letra',
                'fecha'

            ])
        ->where(['id_gasto'=>$id]);
        $this->set('conceptos', $conceptos);

        $query2 = $this->Conceptos->find();
        $query2->select([
            'sum' => $query2->func()->sum(\App\Database\SqlDialect::forConnection()->textAsNumber('gasto'))])
            ->where(['id_gasto'=>$id]);
        $total = $query2->first();
        $this->set('query2', $total);


    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add($id_condominio = null)
    {

        $condominioTable = TableRegistry::get('Condominio');

        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);
        $this->loadModel('tipoGasto');
        $this->loadModel('Proveedores');
        $this->loadModel('Categoria');

        $options = $this->tipoGasto->find('list', ['keyField' => 'id_tipo',
            'valueField' => 'tipo_gasto'])
            ->where(['id_condominio'=>$id_condominio]);
        $this->set('options', $options);

        $optionProveedor = $this->Proveedores->find('list', ['keyField' => 'id_proveedores',
            'valueField' => 'nom_proveedor'])
            ->where(['id_condominio'=>$id_condominio]);
        $this->set('optionProveedor', $optionProveedor);

        $optionCategoria = $this->Categoria->find('list', ['keyField' => 'id_categoria',
            'valueField' => 'categoria'])
            ->where(['id_condominio'=>$id_condominio]);
        $this->set('optionCategoria', $optionCategoria);

        $caja = $this->Caja->newEntity();
        if ($this->request->is('post')) {
            $caja = $this->Caja->patchEntity($caja, $this->request->getData());
            if ($this->Caja->save($caja)) {
                $this->Flash->success(__('The caja has been saved.'));

                return $this->redirect(['controller'=>'conceptos', 'action' => 'add', 'id'=>$caja->id_gasto]);
            }
            $this->Flash->error(__('The caja could not be saved. Please, try again.'));
        }
        $this->set(compact('caja','condominio'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Caja id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $caja = $this->Caja->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $caja = $this->Caja->patchEntity($caja, $this->request->getData());
            if ($this->Caja->save($caja)) {
                $this->Flash->success(__('The caja has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The caja could not be saved. Please, try again.'));
        }
        $this->set(compact('caja'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Caja id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $caja = $this->Caja->get($id);
        if ($this->Caja->delete($caja)) {
            $this->Flash->success(__('The caja has been deleted.'));
        } else {
            $this->Flash->error(__('The caja could not be deleted. Please, try again.'));
        }

        return $this->redirect($this->referer());
    }

}
