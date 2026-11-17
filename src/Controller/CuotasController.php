<?php
namespace App\Controller;

use App\Controller\AppController;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Cake\Filesystem\File;
use Cake\Http\CallbackStream;
use Cake\Log\Log;
use NumerosEnLetras;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use App\Model\Table\Adeudos;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\I18n;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Helper;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Robotusers\Excel\Excel\Manager;


use function Sodium\add;
use function Sodium\increment;

I18n::locale('en_ES');
/**
 * Cuotas Controller
 *
 * @property \App\Model\Table\CuotasTable $Cuotas
 *
 * @method \App\Model\Entity\Cuota[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CuotasController extends AppController
{
    public function isAuthorized($user) {

        // Admin allowed anywhere
        if (isset($user['id_perfil']) && $user['id_perfil'] === 1) {
            return true;
        }

        if (isset($user['id_perfil']) && $user['id_perfil'] === 2) {
            return true;
        }

        if (isset($user['id_perfil']) && $user['id_perfil'] === 4) {
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
        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);
        $this->set('id_condominio', $id_condominio);
        $this->set('condominio', $condominio);

        $query = $this->Cuotas
            ->find()
            ->select([
                'id_cuotas',
                'condominio' => 'c.nombre_condominio',
                'tipo' => 'd.tipo_cuota',
                'cuota',
                'cuota_letra',
                'fecha_cargo',
                'fecha'

            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'condominio', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condominio = Cuotas.id_condominio',
                ]
            ])
            ->join([
                'd' => [
                    'table' => 'tipo_cuota', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_tipo = Cuotas.id_tipo',
                ]
            ])
            ->where(['Cuotas.id_condominio'=>$id_condominio]);

        $this->set('cuotas', $this->paginate($query));
        $this->set('_serialize', ['cuotas']);
    }

    /**
     * View method
     *
     * @param string|null $id Cuota id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $cuota = $this->Cuotas->get($id, [
            'contain' => []
        ]);

        $this->set('cuota', $cuota);


    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
     public function add($id_condominio = null)
    {
        $cuota = $this->Cuotas->newEntity();

        $this->loadModel('tipoCuota');
        $options = $this->tipoCuota->find('list', ['keyField' => 'id_tipo',
            'valueField' => 'tipo_cuota']);
        $this->set('options', $options);


        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);

        if ($this->request->is('post')) {
            $cuota = $this->Cuotas->patchEntity($cuota, $this->request->getData());
                   if ($this->Cuotas->save($cuota)) {

                       $this->loadModel('condomino');
                       $condominos = $this->condomino->find()
                           ->select(['id_condomino'])
                           ->where([
                               'id_condominio' => $id_condominio
                           ]);
                       //$this->set('condominos', $condominos);

                       $adeudo = TableRegistry::get('Adeudos');
                       //$adeudo = $adeudoTable->newEntity();
                       foreach($condominos as $condominos){

                           $adeudo->query()
                               ->insert(['id_condominio', 'id_condomino','id_tipo' ,'mes' ,'monto', 'concepto'])
                               ->values([
                                   'id_condominio' => $id_condominio,
                                   'id_condomino' => $condominos->id_condomino,
                                   'id_tipo' => $cuota->id_tipo,
                                   'mes' => $cuota->fecha,
                                   'monto' => $cuota->cuota,
                                   'concepto' => $cuota->concepto
                               ])
                               ->execute();
                       }
                       $this->Flash->success(__('The adeudo has been saved.'));

                       return $this->redirect(['action' => 'index',
                           'id'=>$id_condominio]);

                   }
            $this->Flash->error(__('The cuota could not be saved. Please, try again.'));

        }
            //return $this->redirect(['action' => 'index', 'id_cuotas'=>$cuota->id_condominio]
        $this->set(compact('cuota','condominio'));
    }


    /**
     * Edit method
     *
     * @param string|null $id Cuota id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $cuota = $this->Cuotas->get($id, [
            'contain' => []
        ]);


        $this->loadModel('condominio'); //if it's not already loaded
        $condominio = $this->condominio->find();
        $this->set('condominio', $condominio);

        $this->loadModel('tipoCuota');
        $optionCuota = $this->tipoCuota->find('list', ['keyField' => 'id_tipo',
            'valueField' => 'tipo_cuota'])
            ->where(['id_tipo !=' => 1]);
        $this->set('optionCuota', $optionCuota);

        if ($this->request->is(['patch', 'post', 'PUT'])) {
            $cuota = $this->Cuotas->patchEntity($cuota, $this->request->getData());
            if ($this->Cuotas->save($cuota)) {
                $this->Flash->success(__('The cuota has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The cuota could not be saved. Please, try again.'));
        }
        $this->set(compact('cuota'));
    }

    public function montoletras()
    {
        $monto = $this->request->getQuery('monto');
        $letras = NumerosEnLetras::convertir($monto,'pesos', false, 'centavos');
        //$letras = $letras .  "pesos 00/100 M.N.";
        $resultJ = json_encode(['monto' => $letras]);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;
    }

    /**
     * Delete method
     *
     * @param string|null $id Cuota id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $cuota = $this->Cuotas->get($id);
        if ($this->Cuotas->delete($cuota)) {
            $adeudo = TableRegistry::get('Adeudos');
            $adeudo->deleteAll(
                [
                    'mes' => $cuota->fecha,
                    'id_condominio' => $cuota->id_condominio,
                    ]
            );

            $this->Flash->success(__('The cuota has been deleted.'));
        } else {
            $this->Flash->error(__('The cuota could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function borrar($id = null){
        $cuota = $this->Cuotas->get($id);
        if ($this->Cuotas->delete($cuota)){
            $adeudo = TableRegistry::get('Adeudos');
            $adeudo->deleteAll(
                [
                    'mes' => $cuota->fecha,
                    'id_condominio' => $cuota->id_condominio,
                ]
            );
            $message = [['message' => 'Se borro con éxito el condominio - ' . $cuota->id_cuotas . ' - ', 'result' => 'ok']];
        } else {
            $message = [['message' => 'Error al borrar los datos', 'result' => 'error']];
            Log::debug($cuota->errors());
        }

        $resultJ = json_encode($message);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;

    }

    public function saveAdeudo($cuota, $id_condominio = null)
    {

        $this->loadModel('condomino');
        $condominos = $this->condomino->find()
            ->select(['id_condomino'])
            ->where([
                'id_condominio' => $id_condominio
            ]);
        $this->set('condominos', $condominos);

        foreach($condominos as $condominos){

        }


        $adeudoTable = TableRegistry::get('Adeudo');
        $adeudo = $adeudoTable->newEntity();

        $adeudo->insert(['id_condominio', 'id_condomino', 'mes', 'monto', 'concepto'])
            ->values([
                'id_condominio' => $id_condominio,
                'id_condomino' => $condominos,
                'mes' => $cuota->fecha_cargo,
                'monto' => $cuota->cuota,
                'concepto' => $cuota->concepto
            ])
            ->execute();

    }

    public function xlsx()
    {
        $condominio = $_GET['condominio'];

        $adeudos = TableRegistry::getTableLocator()->get('Condomino');
        $todosAdeudos = $adeudos->find()
            ->select()
            ->where([
                'id_condominio' => $condominio
            ]);

        $totales = $todosAdeudos->all();


        /*
         *
        $adeudos = TableRegistry::getTableLocator()->get('Adeudos');
        $todosAdeudos = $adeudos->find()
            ->select([
                'id_adeudo',
                'id_condominio',
                'id_condomino',
                'mes',
                'monto',
                'concepto',
                'fecha',
                'estatus',
            ])
            ->where([
                'id_condominio' => $condominio
            ])
            ->where([
                'fecha' => 'NOW() - INTERVAL 1 MONTH'
            ]);
         * */

        $condominos = TableRegistry::getTableLocator()->get('Condomino');
        $condomino= $condominos->find();
        $condomino->select()
            ->where([
                'id_condominio' => $condominio
            ]);
        $total = $condomino->all();

        $spreadsheet = new Spreadsheet();

        $numeroDeFila = 2;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'id_adeudo');
        $sheet->setCellValue('B1', 'id_condominio');
        $sheet->setCellValue('C1', 'id_condomino');
        $sheet->setCellValue('D1', 'mes');
        $sheet->setCellValue('E1', 'monto');
        $sheet->setCellValue('F1', 'concepto');
        $sheet->setCellValue('G1', 'fecha');
        $sheet->setCellValue('H1', 'condomino');

        foreach($totales as $totales) {
            $sheet->setCellValue('A'.$numeroDeFila, "-");
            $sheet->setCellValue('B'.$numeroDeFila, $totales->id_condominio);
            $sheet->setCellValue('C'.$numeroDeFila, $totales->id_condomino);
            $sheet->setCellValue('D'.$numeroDeFila, "2022-01-01");
            $sheet->setCellValue('E'.$numeroDeFila, "100");
            $sheet->setCellValue('F'.$numeroDeFila, "Concepto");
            $sheet->setCellValue('G'.$numeroDeFila, "-");
            $sheet->setCellValue('H'.$numeroDeFila, $totales->numero_departamento);
            $numeroDeFila++;
        }

        $writer = new Xlsx($spreadsheet);

        $stream = new CallbackStream(function () use ($writer) {
            $writer->save('php://output');
        });
        $filename = 'Cuotas'.$this->request->session()->read('Auth.User.id_user');
        $response = $this->response;

        return $response->withType('xlsx')
            ->withHeader('Content-Disposition', "attachment;filename=\"{$filename}.xlsx\"")
            ->withBody($stream);

    }

    public function excel()
    {
        $this->loadModel('Excel');
        $excel = $this->paginate($this->Excel);
        $this->set(compact('excel'));

        $excel = TableRegistry::getTableLocator()->get('Excel');
        $archivo = $excel->find()
            ->select()
            ->where([
                'id_user' => $this->request->session()->read('Auth.User.id_user')
            ]);
        $shreed = $archivo->last();
        $this->set('shreed', $shreed);

        //Indice de la tabla

        $this->loadModel('Excel');

        $excel = $this->Excel->newEntity();
        if ($this->request->is('post')) {
            $excel = $this->Excel->patchEntity($excel, $this->request->getData());
            if ($this->Excel->save($excel)) {

                return $this->redirect($this->referer());

            }
            $this->Flash->error(__('The excel could not be saved. Please, try again.'));
        }
        $this->set(compact('excel'));
    }

    public function insertar()
    {
        $excel = TableRegistry::getTableLocator()->get('Excel');
        $archivo = $excel->find()
            ->select()
            ->where([
                'id_user' => $this->request->session()->read('Auth.User.id_user')
            ]);
        $shreed = $archivo->last();
        $this->set('shreed', $shreed);

        $helper = new Helper\Sample();
        //debug($helper);
        $inputFileName = WWW_ROOT . 'files/Excel/archivo/' .$shreed->archivo;
        $spreadsheet = IOFactory::load($inputFileName);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, false, true, false);
        //var_dump($sheetData);
        $isheader = 0;

        $adeudo = TableRegistry::get('Adeudos');
            foreach ($sheetData as $row){
                if($isheader > 0) {
                    if(null !== $row){
                    $adeudo->query()
                    ->insert(['id_condominio', 'id_condomino', 'mes' ,'monto', 'concepto'])
                    ->values([
                        'id_condominio' => $row[1],
                        'id_condomino' => $row[2],
                        'mes' => $row[3],
                        'monto' => $row[4],
                        'concepto' => $row[5],
                    ])
                    ->execute();
                    }
            }else
                { $isheader = 1;
                }
            }

        return $this->redirect($this->referer());
    }

}
