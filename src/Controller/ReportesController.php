<?php
namespace App\Controller;
ini_set('memory_limit', '-1');
use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use phpDocumentor\Reflection\DocBlock\Tags\Uses;

/**
 * Adeudos Controller
 *
 * @property \App\Model\Table\AdeudosTable $Adeudos
 *
 * @method \App\Model\Entity\Adeudo[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ReportesController extends AppController
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

    public function index($id_condominio = null)
    {
        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);
        $this->set(compact('condominio'));
    }


    public function informe($id_condominio = null, $fecha = null)
    {
        //$now = $time;

        if($fecha == null){
            $time = date("Y");
            //$time->i18nFormat('yyyy');
        }else{
            $time = $fecha;
        }
        $this->set('time', $time);


        $this->loadModel('condomino');
        $condominosT = $this->condomino->find()
            ->select([
                'id_condomino'
            ])
            ->where([
                'id_condominio' => $id_condominio
            ]);
        $this->set('condominosT', $condominosT);


        foreach ($condominosT as $condoTotal){

        }

        /*-------------------------- Condominio -------------------------------*/


        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);
        $this->set(compact('condominio'));


        $this->loadModel('condomino');
        $condominos = $this->condomino->find()
            ->select([
                'id_condomino' =>'c.id_condomino',
                'departamento' =>'c.numero_departamento',
            ])
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Condomino.id_condomino',
                ]
            ])
            ->where([
                'Condomino.id_condominio' => $id_condominio
            ]);
        $this->set('condominos', $condominos);

        $this->loadModel('Gasto');
        $gasto = $this->Gasto
            ->find()
            ->select([
                'id_gasto',
                'id_condominio',
                'id_tipo',
                'id_proveedores',
                'gasto',
                'fecha',
                'fecha'
            ])

            ->where(['id_condominio' => $id_condominio ])
            ->where(['id_condominio' => $id_condominio ]);

        $rows = $gasto->all();
        $this->set('gasto', $rows);


        /*-------------------------- Saldo Inicial desde Tabla de SI -------------------------------*/
        $gastosTotTable = TableRegistry::get('Gasto');
        $TotalGastos = $gastosTotTable->find();
        $TotalGastos->select([
            'F.id_condominio',
            'categoria' =>'c.categoria',
            'enero' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = F.id_gasto)'),
            'febrero' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = F.id_gasto)'),
            'marzo' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = F.id_gasto)'),
            'abril' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = F.id_gasto)'),
            'mayo' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = F.id_gasto)'),
            'junio' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = F.id_gasto)'),
            'total' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = F.id_gasto)')
        ])
            ->from('gasto as F')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_categoria = F.id_categoria',
                ]
            ])
            ->where(['F.id_condominio' => $id_condominio])
            ->where(['confirmacion =' => 'SI'])
            ->where(['YEAR(F.fecha)' => $time]);
        $totGas  = $TotalGastos->all();
        $this->set('totGas', $totGas);

        /*-------------------------- Saldo Inicial -------------------------------*/
        $saldoTable = TableRegistry::get('saldo_condominio');

        $saldoPrim = $saldoTable->find();
        $saldoPrim->select([
            'id_condominio' => 'S.id_condominio',
            'enero' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 1 THEN S.saldo ELSE 0 END'),
            'febrero' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 2 THEN S.saldo ELSE 0 END'),
            'marzo' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 3 THEN S.saldo ELSE 0 END'),
            'abril' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 4 THEN S.saldo ELSE 0 END'),
            'mayo' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 5 THEN S.saldo ELSE 0 END'),
            'junio' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 6 THEN S.saldo ELSE 0 END'),
            'total' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) BETWEEN 1 AND 6 THEN S.saldo ELSE 0 END')
        ])
            ->from(['S' => 'saldo_condominio'])
            ->where(['S.id_condominio' => $id_condominio])
            ->where(['YEAR(S.fecha)' => $time])
            ->group('S.id_condominio');

        $saldo = $saldoPrim->all();

        $this->set('saldo', $saldo);


        /*-------------------------- Saldo final -------------------------------*/
        $saldoTotaTable = TableRegistry::get('saldo_cierre');

        $saldoTotalPrim = $saldoTotaTable->find();
        $saldoTotalPrim->select([
            'id_condominio' => 'S.id_condominio',
            'enero' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 1 THEN S.saldo ELSE 0 END'),
            'febrero' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 2 THEN S.saldo ELSE 0 END'),
            'marzo' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 3 THEN S.saldo ELSE 0 END'),
            'abril' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 4 THEN S.saldo ELSE 0 END'),
            'mayo' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 5 THEN S.saldo ELSE 0 END'),
            'junio' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 6 THEN S.saldo ELSE 0 END'),
            'total' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) BETWEEN 1 AND 6 THEN S.saldo ELSE 0 END')
        ])
            ->from(['S' => 'saldo_cierre'])  // Cambié 'I' por 'S' para la tabla
            ->where(['S.id_condominio' => $id_condominio])  // Filtro por condominio
            ->where(['YEAR(S.fecha)' => $time])  // Filtro por año
            ->group('S.id_condominio');  // Agrupar por id_condominio

        $finalAmount = $saldoTotalPrim->all();

        $this->set('finalAmount', $finalAmount);


        /*-------------------------- Ingresos por Cuotas -------------------------------*/
        $cuotasTable = TableRegistry::get('Pago');
        $cuotasTotal = $cuotasTable->find();
        $cuotasTotal->select([
            'J.id_condominio',
            'tipo'=>'tp.nombre',
            'enero' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 1 AND id_pago = J.id_pago)'),
            'febrero' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 2 AND id_pago = J.id_pago)'),
            'marzo' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 3 AND id_pago = J.id_pago)'),
            'abril' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 4 AND id_pago = J.id_pago)'),
            'mayo' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 5 AND id_pago = J.id_pago)'),
            'junio' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 6 AND id_pago = J.id_pago)'),
            'total' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 6 AND id_pago = J.id_pago)')
        ])
            ->from('pago as J')
            ->enableHydration(false)
            ->join([
                'tp' => [
                    'table' => 'tipo_pago', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'J.id_tipo = tp.id_tipo',
                ]
            ])
            ->where(['J.id_condominio' => $id_condominio])
            ->where(['YEAR(J.fecha)' => $time])
            ->group('tp.id_tipo');
        $cuotas = $cuotasTotal->all();
        $this->set('cuotas', $cuotas);
        /*-------------------------- Ingresos totales -------------------------------*/
        $itTable = TableRegistry::get('Pago');
        $itTotal = $itTable->find();
        $itTotal->select([
            'J.id_condominio',
            'enero' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 1 AND id_pago = J.id_pago)'),
            'febrero' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 2 AND id_pago = J.id_pago)'),
            'marzo' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 3 AND id_pago = J.id_pago)'),
            'abril' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 4 AND id_pago = J.id_pago)'),
            'mayo' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 5 AND id_pago = J.id_pago)'),
            'junio' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 6 AND id_pago = J.id_pago)'),
            'total' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 6 AND id_pago = J.id_pago)')
        ])
            ->from('pago as J')
            ->where(['J.id_condominio' => $id_condominio])
            ->where(['YEAR(J.fecha)' => $time])
            ->group('J.id_condominio');
        $its = $itTotal->all();
        $this->set('its', $its);
        /*-------------------------- Gastos Fijos -------------------------------*/
        $gafijosTable = TableRegistry::get('Gasto');
        $fijos = $gafijosTable->find();
        $fijos->select([
            'D.id_condominio',
            'categoria' =>'c.categoria',
            'enero' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = D.id_gasto)'),
            'febrero' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = D.id_gasto)'),
            'marzo' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = D.id_gasto)'),
            'abril' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = D.id_gasto)'),
            'mayo' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = D.id_gasto)'),
            'junio' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = D.id_gasto)'),
            'total' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = D.id_gasto)')
        ])
            ->from('gasto as D')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_categoria = D.id_categoria',
                ]
            ])
            ->where(['D.id_condominio' => $id_condominio])
            ->where(['YEAR(D.fecha)' => $time])
            ->where(['id_tipo =' => 1])
            ->where(['confirmacion =' => 'SI'])
            ->group('D.id_categoria');
        $this->set('fijos', $fijos);
        //___________________________________________________________  Total gastos fijos _____________________________________________//
        $totalFijosTable = TableRegistry::get('Gasto');
        $fij = $totalFijosTable->find();
        $fij->select([
            'G.id_condominio',
            'enero' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = G.id_gasto)'),
            'febrero' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = G.id_gasto)'),
            'marzo' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = G.id_gasto)'),
            'abril' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = G.id_gasto)'),
            'mayo' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = G.id_gasto)'),
            'junio' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = G.id_gasto)'),
            'total' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = G.id_gasto)')
        ])
            ->from('gasto as G')
            ->where(['G.id_condominio' => $id_condominio])
            ->where(['YEAR(G.fecha)' => $time])
            ->where(['id_tipo =' => 1])
            ->where(['confirmacion =' => 'SI'])
            ->group('G.id_condominio');
        $this->set('fij', $fij);
        /*-------------------------- Gastos Variables -------------------------------*/
        $gastosTable = TableRegistry::get('Gasto');
        $gastos = $gastosTable->find();
        $gastos->select([
            'G.id_condominio',
            'categoria' =>'c.categoria',
            'enero' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = G.id_gasto)'),
            'febrero' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = G.id_gasto)'),
            'marzo' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = G.id_gasto)'),
            'abril' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = G.id_gasto)'),
            'mayo' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = G.id_gasto)'),
            'junio' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = G.id_gasto)'),
            'total' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = G.id_gasto)')
        ])
            ->from('gasto as G')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_categoria = G.id_categoria',
                ]
            ])
            ->where(['G.id_condominio' => $id_condominio])
            ->where(['YEAR(G.fecha)' => $time])
            ->where(['id_tipo =' => 2])
            ->where(['confirmacion =' => 'SI'])
            ->group('G.id_categoria');
        $this->set('gastos', $gastos);

        //_____________________________________________ Total gastos variables ________________________________________________________________//
        $totalVariablesTable = TableRegistry::get('Gasto');
        $varia = $totalVariablesTable->find();
        $varia->select([
            'G.id_condominio',
            'enero' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = G.id_gasto)'),
            'febrero' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = G.id_gasto)'),
            'marzo' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = G.id_gasto)'),
            'abril' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = G.id_gasto)'),
            'mayo' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = G.id_gasto)'),
            'junio' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = G.id_gasto)'),
            'total' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = G.id_gasto)')
        ])
            ->from('gasto as G')
            ->where(['G.id_condominio' => $id_condominio])
            ->where(['YEAR(G.fecha)' => $time])
            ->where(['id_tipo =' => 2])
            ->where(['confirmacion =' => 'SI'])
            ->group('G.id_condominio');
        $this->set('varia', $varia);
        /*------------------------------------------------------- Gastos caja chica -----------------------------------------------------------------*/
        $cajaTable = TableRegistry::get('Conceptos');
        $caja = $cajaTable->find();
        $caja->select([
            'N.id_condominio',
            'concepto' => 'N.concept',
            'enero' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 1 AND id_conceptos = N.id_conceptos)'),
            'febrero' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 2 AND id_conceptos = N.id_conceptos)'),
            'marzo' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 3 AND id_conceptos = N.id_conceptos)'),
            'abril' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 4 AND id_conceptos = N.id_conceptos)'),
            'mayo' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 5 AND id_conceptos = N.id_conceptos)'),
            'junio' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 6 AND id_conceptos = N.id_conceptos)'),
            'total' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha) BETWEEN 1 AND 6 AND id_conceptos = N.id_conceptos)')
        ])
            ->from('conceptos as N')
            ->enableHydration(false)
            ->where(['N.id_condominio' => $id_condominio])
            ->where(['YEAR(N.fecha)' => $time])
            ->group(['concepto']);
        $this->set('caja', $caja);
        /*-------------------------------------------------------Total Gastos caja chica -----------------------------------------------------------------*/
        $ToCajaTable = TableRegistry::get('Conceptos');
        $cajaTotal = $ToCajaTable->find();
        $cajaTotal->select([
            'N.id_condominio',
            'concepto' => 'N.concept',
            'enero' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 1 AND id_conceptos = N.id_conceptos)'),
            'febrero' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 2 AND id_conceptos = N.id_conceptos)'),
            'marzo' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 3 AND id_conceptos = N.id_conceptos)'),
            'abril' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 4 AND id_conceptos = N.id_conceptos)'),
            'mayo' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 5 AND id_conceptos = N.id_conceptos)'),
            'junio' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 6 AND id_conceptos = N.id_conceptos)'),
            'total' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha) BETWEEN 1 AND 6 AND id_conceptos = N.id_conceptos)')
        ])
            ->from('conceptos as N')
            ->enableHydration(false)
            ->where(['N.id_condominio' => $id_condominio])
            ->where(['YEAR(N.fecha)' => $time])
            ->group(['id_gasto']);
        $this->set('cajaTotal', $cajaTotal);
        /*------------------------------------------------------- Total Gastos -----------------------------------------------------------------*/
        $totalFijosTable = TableRegistry::get('Gasto');
        $totalFijos = $totalFijosTable->find();
        $totalFijos->select([
            'G.id_condominio',
            'enero' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = G.id_gasto)'),
            'febrero' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = G.id_gasto)'),
            'marzo' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = G.id_gasto)'),
            'abril' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = G.id_gasto)'),
            'mayo' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = G.id_gasto)'),
            'junio' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = G.id_gasto)'),
            'total' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = G.id_gasto)')
        ])
            ->from('gasto as G')
            ->where(['G.id_condominio' => $id_condominio])
            ->where(['YEAR(G.fecha)' => $time])
            ->where(['id_tipo =' => 1])
            ->where(['confirmacion =' => 'SI'])
            ->group('G.id_condominio');
        $totalFijosResults = $totalFijos->toArray();
        $sumTotalFijos = array_reduce($totalFijosResults, function($carry, $item) {
            $carry['enero'] += $item['enero'];
            $carry['febrero'] += $item['febrero'];
            $carry['marzo'] += $item['marzo'];
            $carry['abril'] += $item['abril'];
            $carry['mayo'] += $item['mayo'];
            $carry['junio'] += $item['junio'];
            $carry['total'] += $item['total'];
            return $carry;
        }, ['enero' => 0, 'febrero' => 0, 'marzo' => 0, 'abril' => 0, 'mayo' => 0, 'junio' => 0, 'total' => 0]);

        $totalVariablesTable = TableRegistry::get('Gasto');
        $totalVariables = $totalVariablesTable->find();
        $totalVariables->select([
            'G.id_condominio',
            'enero' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = G.id_gasto)'),
            'febrero' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = G.id_gasto)'),
            'marzo' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = G.id_gasto)'),
            'abril' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = G.id_gasto)'),
            'mayo' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = G.id_gasto)'),
            'junio' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = G.id_gasto)'),
            'total' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = G.id_gasto)')
        ])
            ->from('gasto as G')
            ->where(['G.id_condominio' => $id_condominio])
            ->where(['YEAR(G.fecha)' => $time])
            ->where(['id_tipo =' => 2])
            ->where(['confirmacion =' => 'SI'])
            ->group('G.id_condominio');
        $totalVariablesResults = $totalVariables->toArray();
        $sumTotalVariables = array_reduce($totalVariablesResults, function($carry, $item) {
            $carry['enero'] += $item['enero'];
            $carry['febrero'] += $item['febrero'];
            $carry['marzo'] += $item['marzo'];
            $carry['abril'] += $item['abril'];
            $carry['mayo'] += $item['mayo'];
            $carry['junio'] += $item['junio'];
            $carry['total'] += $item['total'];
            return $carry;
        }, ['enero' => 0, 'febrero' => 0, 'marzo' => 0, 'abril' => 0, 'mayo' => 0, 'junio' => 0, 'total' => 0]);

        $totalCajaTable = TableRegistry::get('Conceptos');
        $totalCaja = $totalCajaTable->find();
        $totalCaja->select([
            'N.id_condominio',
            'concepto' => 'N.concept',
            'enero' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 1 AND id_conceptos = N.id_conceptos)'),
            'febrero' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 2 AND id_conceptos = N.id_conceptos)'),
            'marzo' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 3 AND id_conceptos = N.id_conceptos)'),
            'abril' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 4 AND id_conceptos = N.id_conceptos)'),
            'mayo' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 5 AND id_conceptos = N.id_conceptos)'),
            'junio' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 6 AND id_conceptos = N.id_conceptos)'),
            'total' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha) BETWEEN 1 AND 6 AND id_conceptos = N.id_conceptos)')
        ])
            ->from('conceptos as N')
            ->enableHydration(false)
            ->where(['N.id_condominio' => $id_condominio])
            ->where(['YEAR(N.fecha)' => $time])
            ->group(['id_conceptos']);
        $totalCajaResults = $totalCaja->toArray();
        $sumTotalCaja = array_reduce($totalCajaResults, function($carry, $item) {
            $carry['enero'] += $item['enero'];
            $carry['febrero'] += $item['febrero'];
            $carry['marzo'] += $item['marzo'];
            $carry['abril'] += $item['abril'];
            $carry['mayo'] += $item['mayo'];
            $carry['junio'] += $item['junio'];
            $carry['total'] += $item['total'];
            return $carry;
        }, ['enero' => 0, 'febrero' => 0, 'marzo' => 0, 'abril' => 0, 'mayo' => 0, 'junio' => 0, 'total' => 0]);

        $grandTotal = [
            'enero' => $sumTotalFijos['enero'] + $sumTotalVariables['enero'] + $sumTotalCaja['enero'],
            'febrero' => $sumTotalFijos['febrero'] + $sumTotalVariables['febrero'] + $sumTotalCaja['febrero'],
            'marzo' => $sumTotalFijos['marzo'] + $sumTotalVariables['marzo'] + $sumTotalCaja['marzo'],
            'abril' => $sumTotalFijos['abril'] + $sumTotalVariables['abril'] + $sumTotalCaja['abril'],
            'mayo' => $sumTotalFijos['mayo'] + $sumTotalVariables['mayo'] + $sumTotalCaja['mayo'],
            'junio' => $sumTotalFijos['junio'] + $sumTotalVariables['junio'] + $sumTotalCaja['junio'],
            'total' => $sumTotalFijos['total'] + $sumTotalVariables['total'] + $sumTotalCaja['total']
        ];

        $this->set(compact('totalFijos', 'totalVariables', 'totalCaja', 'grandTotal'));
        /*-------------------------- Pago -------------------------------*/
        $pagosTable = TableRegistry::get('Pago');
        $pagos = $pagosTable->find();
        $pagos->select([
            'P.id_condomino',
            'departamento' => (\App\Database\SqlDialect::forConnection()->isPostgres() ? $pagos->newExpr(\App\Database\SqlDialect::forConnection()->leadingInteger('c.numero_departamento')) : $pagos->func()->cast('c.numero_departamento', 'unsigned')),
            'enero' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 1 AND id_pago = P.id_pago)'),
            'febrero' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 2 AND id_pago = P.id_pago)'),
            'marzo' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 3 AND id_pago = P.id_pago)'),
            'abril' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 4 AND id_pago = P.id_pago)'),
            'mayo' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 5 AND id_pago = P.id_pago)'),
            'junio' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 6 AND id_pago = P.id_pago)'),
            'total' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 6 AND id_pago = P.id_pago)')
        ])
            ->from('pago as P')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = P.id_condomino',
                ]
            ])
            ->where(['P.id_condominio' => $id_condominio])
            ->where(['YEAR(P.fecha)' => $time])
            ->group('P.id_condomino')
            ->order(['departamento' => 'ASC']);
        $this->set('pagos', $pagos);

        $pagos2Table = TableRegistry::get('Pago');
        $pagosTotales = $pagos2Table->find();
        $pagosTotales->select([
            'M.id_condomino',
            'departamento' =>'c.numero_departamento',
            'enero' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 1 AND id_pago = M.id_pago)'),
            'febrero' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 2 AND id_pago = M.id_pago)'),
            'marzo' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 3 AND id_pago = M.id_pago)'),
            'abril' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 4 AND id_pago = M.id_pago)'),
            'mayo' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 5 AND id_pago = M.id_pago)'),
            'junio' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 6 AND id_pago = M.id_pago)'),
            'total' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 6 AND id_pago = M.id_pago)')
        ])
            ->from('pago as M')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = M.id_condomino',
                ]
            ])
            ->where(['M.id_condominio' => $id_condominio])
            ->where(['YEAR(M.fecha)' => $time])
            ->group('M.id_condominio');
        $pag = $pagosTotales->all();
        $this->set('pag', $pag);
        /*-------------------------- Adeudos -------------------------------*/
        $adeudosTable = TableRegistry::get('Adeudos');
        $adeudos = $adeudosTable->find();
        $adeudos->select([
            'T.id_condomino',
            'departamento' => (\App\Database\SqlDialect::forConnection()->isPostgres() ? $adeudos->newExpr(\App\Database\SqlDialect::forConnection()->leadingInteger('c.numero_departamento')) : $adeudos->func()->cast('c.numero_departamento', 'unsigned')),
            'saldo' => 'sc.contra',
            'enero' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 1 AND id_adeudo = T.id_adeudo)'),
            'febrero' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 2 AND id_adeudo = T.id_adeudo)'),
            'marzo' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 3 AND id_adeudo = T.id_adeudo)'),
            'abril' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 4 AND id_adeudo = T.id_adeudo)'),
            'mayo' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 5 AND id_adeudo = T.id_adeudo)'),
            'junio' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 6 AND id_adeudo = T.id_adeudo)'),
            'total' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) BETWEEN 1 AND 6 AND id_adeudo = T.id_adeudo)')
        ])
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino',
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = T.id_condomino',
                ]
            ])
            ->join([
                'sc' => [
                    'table' => 'saldo_condomino',
                    'type' => 'INNER',
                    'conditions' => [
                        'sc.id_condomino = T.id_condomino',
                        'MONTH(sc.fecha) BETWEEN 1 AND 6'
                    ]                ]
            ])
            ->where(['T.id_condominio' => $id_condominio])
            ->where(['YEAR(T.mes)' => $time])
            ->where(['T.estatus' => 1])
            ->group('T.id_condomino')
            ->order(['departamento' => 'ASC']);
        $this->set('adeudos', $adeudos);
        /*-------------------------- Total Adeudos -------------------------------*/
        $totalAdeudosTable = TableRegistry::get('Adeudos');
        $pseudosTotals = $totalAdeudosTable->find();
        $pseudosTotals->select([
            'T.id_condomino',
            'saldo' => $adeudos->func()->sum('CASE WHEN MONTH(sc.fecha) BETWEEN 1 AND 6 THEN sc.contra ELSE 0 END'),
            'enero' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 1 AND id_adeudo = T.id_adeudo)'),
            'febrero' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 2 AND id_adeudo = T.id_adeudo)'),
            'marzo' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 3 AND id_adeudo = T.id_adeudo)'),
            'abril' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 4 AND id_adeudo = T.id_adeudo)'),
            'mayo' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 5 AND id_adeudo = T.id_adeudo)'),
            'junio' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 6 AND id_adeudo = T.id_adeudo)'),
            'total' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) BETWEEN 1 AND 6 AND id_adeudo = T.id_adeudo)')
        ])
            ->join([
                'sc' => [
                    'table' => 'saldo_condomino',
                    'type' => 'INNER',
                    'conditions' => 'sc.id_condomino = T.id_condomino',
                ]
            ])
            ->from('adeudos as T')
            ->enableHydration(false)
            ->where(['T.id_condominio' => $id_condominio])
            ->where(['T.estatus' => 1])
            ->where(['YEAR(T.mes)' => $time])
            ->group('T.id_condominio');

        $adTota = $pseudosTotals->all();
        $this->set('adTota', $adTota);
        /*----------------------------------------------------- Saldo al cierre ------------------------------------------------------*/
    }

    public function pdfInforme($id_condominio = null, $fecha = null)
    {
        //$now = $time;

        if($fecha == null){
            $time = date("Y");
            //$time->i18nFormat('yyyy');
        }else{
            $time = $fecha;
        }
        $this->set('time', $time);


        $this->loadModel('condomino');
        $condominosT = $this->condomino->find()
            ->select([
                'id_condomino'
            ])
            ->where([
                'id_condominio' => $id_condominio
            ]);
        $this->set('condominosT', $condominosT);


        foreach ($condominosT as $condoTotal){

        }

        /*-------------------------- Condominio -------------------------------*/


        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);
        $this->set(compact('condominio'));


        $this->loadModel('condomino');
        $condominos = $this->condomino->find()
            ->select([
                'id_condomino' =>'c.id_condomino',
                'departamento' =>'c.numero_departamento',
            ])
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Condomino.id_condomino',
                ]
            ])
            ->where([
                'Condomino.id_condominio' => $id_condominio
            ]);
        $this->set('condominos', $condominos);

        $this->loadModel('Gasto');
        $gasto = $this->Gasto
            ->find()
            ->select([
                'id_gasto',
                'id_condominio',
                'id_tipo',
                'id_proveedores',
                'gasto',
                'fecha',
                'fecha'
            ])

            ->where(['id_condominio' => $id_condominio ])
            ->where(['id_condominio' => $id_condominio ]);

        $rows = $gasto->all();
        $this->set('gasto', $rows);


        /*-------------------------- Saldo Inicial desde Tabla de SI -------------------------------*/
        $gastosTotTable = TableRegistry::get('Gasto');
        $TotalGastos = $gastosTotTable->find();
        $TotalGastos->select([
            'F.id_condominio',
            'categoria' =>'c.categoria',
            'enero' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = F.id_gasto)'),
            'febrero' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = F.id_gasto)'),
            'marzo' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = F.id_gasto)'),
            'abril' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = F.id_gasto)'),
            'mayo' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = F.id_gasto)'),
            'junio' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = F.id_gasto)'),
            'total' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = F.id_gasto)')
        ])
            ->from('gasto as F')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_categoria = F.id_categoria',
                ]
            ])
            ->where(['F.id_condominio' => $id_condominio])
            ->where(['confirmacion =' => 'SI'])
            ->where(['YEAR(F.fecha)' => $time]);
        $totGas  = $TotalGastos->all();
        $this->set('totGas', $totGas);

        /*-------------------------- Saldo Inicial -------------------------------*/
        $saldoTable = TableRegistry::get('saldo_condominio');

        $saldoPrim = $saldoTable->find();
        $saldoPrim->select([
            'id_condominio' => 'S.id_condominio',
            'enero' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 1 THEN S.saldo ELSE 0 END'),
            'febrero' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 2 THEN S.saldo ELSE 0 END'),
            'marzo' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 3 THEN S.saldo ELSE 0 END'),
            'abril' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 4 THEN S.saldo ELSE 0 END'),
            'mayo' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 5 THEN S.saldo ELSE 0 END'),
            'junio' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 6 THEN S.saldo ELSE 0 END'),
            'total' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) BETWEEN 1 AND 6 THEN S.saldo ELSE 0 END')
        ])
            ->from(['S' => 'saldo_condominio'])
            ->where(['S.id_condominio' => $id_condominio])
            ->where(['YEAR(S.fecha)' => $time])
            ->group('S.id_condominio');

        $saldo = $saldoPrim->all();

        $this->set('saldo', $saldo);


        /*-------------------------- Saldo final -------------------------------*/
        $saldoTotaTable = TableRegistry::get('saldo_cierre');

        $saldoTotalPrim = $saldoTotaTable->find();
        $saldoTotalPrim->select([
            'id_condominio' => 'S.id_condominio',
            'enero' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 1 THEN S.saldo ELSE 0 END'),
            'febrero' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 2 THEN S.saldo ELSE 0 END'),
            'marzo' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 3 THEN S.saldo ELSE 0 END'),
            'abril' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 4 THEN S.saldo ELSE 0 END'),
            'mayo' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 5 THEN S.saldo ELSE 0 END'),
            'junio' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 6 THEN S.saldo ELSE 0 END'),
            'total' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) BETWEEN 1 AND 6 THEN S.saldo ELSE 0 END')
        ])
            ->from(['S' => 'saldo_cierre'])  // Cambié 'I' por 'S' para la tabla
            ->where(['S.id_condominio' => $id_condominio])  // Filtro por condominio
            ->where(['YEAR(S.fecha)' => $time])  // Filtro por año
            ->group('S.id_condominio');  // Agrupar por id_condominio

        $finalAmount = $saldoTotalPrim->all();

        $this->set('finalAmount', $finalAmount);


        /*-------------------------- Ingresos por Cuotas -------------------------------*/
        $cuotasTable = TableRegistry::get('Pago');
        $cuotasTotal = $cuotasTable->find();
        $cuotasTotal->select([
            'J.id_condominio',
            'tipo'=>'tp.nombre',
            'enero' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 1 AND id_pago = J.id_pago)'),
            'febrero' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 2 AND id_pago = J.id_pago)'),
            'marzo' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 3 AND id_pago = J.id_pago)'),
            'abril' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 4 AND id_pago = J.id_pago)'),
            'mayo' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 5 AND id_pago = J.id_pago)'),
            'junio' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 6 AND id_pago = J.id_pago)'),
            'total' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 6 AND id_pago = J.id_pago)')
        ])
            ->from('pago as J')
            ->enableHydration(false)
            ->join([
                'tp' => [
                    'table' => 'tipo_pago', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'J.id_tipo = tp.id_tipo',
                ]
            ])
            ->where(['J.id_condominio' => $id_condominio])
            ->where(['YEAR(J.fecha)' => $time])
            ->group('tp.id_tipo');
        $cuotas = $cuotasTotal->all();
        $this->set('cuotas', $cuotas);
        /*-------------------------- Ingresos totales -------------------------------*/
        $itTable = TableRegistry::get('Pago');
        $itTotal = $itTable->find();
        $itTotal->select([
            'J.id_condominio',
            'enero' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 1 AND id_pago = J.id_pago)'),
            'febrero' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 2 AND id_pago = J.id_pago)'),
            'marzo' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 3 AND id_pago = J.id_pago)'),
            'abril' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 4 AND id_pago = J.id_pago)'),
            'mayo' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 5 AND id_pago = J.id_pago)'),
            'junio' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 6 AND id_pago = J.id_pago)'),
            'total' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 6 AND id_pago = J.id_pago)')
        ])
            ->from('pago as J')
            ->where(['J.id_condominio' => $id_condominio])
            ->where(['YEAR(J.fecha)' => $time])
            ->group('J.id_condominio');
        $its = $itTotal->all();
        $this->set('its', $its);
        /*-------------------------- Gastos Fijos -------------------------------*/
        $gafijosTable = TableRegistry::get('Gasto');
        $fijos = $gafijosTable->find();
        $fijos->select([
            'D.id_condominio',
            'categoria' =>'c.categoria',
            'enero' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = D.id_gasto)'),
            'febrero' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = D.id_gasto)'),
            'marzo' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = D.id_gasto)'),
            'abril' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = D.id_gasto)'),
            'mayo' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = D.id_gasto)'),
            'junio' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = D.id_gasto)'),
            'total' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = D.id_gasto)')
        ])
            ->from('gasto as D')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_categoria = D.id_categoria',
                ]
            ])
            ->where(['D.id_condominio' => $id_condominio])
            ->where(['YEAR(D.fecha)' => $time])
            ->where(['id_tipo =' => 1])
            ->where(['confirmacion =' => 'SI'])
            ->group('D.id_categoria');
        $this->set('fijos', $fijos);
        //___________________________________________________________  Total gastos fijos _____________________________________________//
        $totalFijosTable = TableRegistry::get('Gasto');
        $fij = $totalFijosTable->find();
        $fij->select([
            'G.id_condominio',
            'enero' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = G.id_gasto)'),
            'febrero' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = G.id_gasto)'),
            'marzo' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = G.id_gasto)'),
            'abril' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = G.id_gasto)'),
            'mayo' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = G.id_gasto)'),
            'junio' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = G.id_gasto)'),
            'total' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = G.id_gasto)')
        ])
            ->from('gasto as G')
            ->where(['G.id_condominio' => $id_condominio])
            ->where(['YEAR(G.fecha)' => $time])
            ->where(['id_tipo =' => 1])
            ->where(['confirmacion =' => 'SI'])
            ->group('G.id_condominio');
        $this->set('fij', $fij);
        /*-------------------------- Gastos Variables -------------------------------*/
        $gastosTable = TableRegistry::get('Gasto');
        $gastos = $gastosTable->find();
        $gastos->select([
            'G.id_condominio',
            'categoria' =>'c.categoria',
            'enero' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = G.id_gasto)'),
            'febrero' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = G.id_gasto)'),
            'marzo' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = G.id_gasto)'),
            'abril' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = G.id_gasto)'),
            'mayo' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = G.id_gasto)'),
            'junio' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = G.id_gasto)'),
            'total' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = G.id_gasto)')
        ])
            ->from('gasto as G')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_categoria = G.id_categoria',
                ]
            ])
            ->where(['G.id_condominio' => $id_condominio])
            ->where(['YEAR(G.fecha)' => $time])
            ->where(['id_tipo =' => 2])
            ->where(['confirmacion =' => 'SI'])
            ->group('G.id_categoria');
        $this->set('gastos', $gastos);

        //_____________________________________________ Total gastos variables ________________________________________________________________//
        $totalVariablesTable = TableRegistry::get('Gasto');
        $varia = $totalVariablesTable->find();
        $varia->select([
            'G.id_condominio',
            'enero' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = G.id_gasto)'),
            'febrero' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = G.id_gasto)'),
            'marzo' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = G.id_gasto)'),
            'abril' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = G.id_gasto)'),
            'mayo' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = G.id_gasto)'),
            'junio' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = G.id_gasto)'),
            'total' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = G.id_gasto)')
        ])
            ->from('gasto as G')
            ->where(['G.id_condominio' => $id_condominio])
            ->where(['YEAR(G.fecha)' => $time])
            ->where(['id_tipo =' => 2])
            ->where(['confirmacion =' => 'SI'])
            ->group('G.id_condominio');
        $this->set('varia', $varia);
        /*------------------------------------------------------- Gastos caja chica -----------------------------------------------------------------*/
        $cajaTable = TableRegistry::get('Conceptos');
        $caja = $cajaTable->find();
        $caja->select([
            'N.id_condominio',
            'concepto' => 'N.concept',
            'enero' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 1 AND id_conceptos = N.id_conceptos)'),
            'febrero' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 2 AND id_conceptos = N.id_conceptos)'),
            'marzo' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 3 AND id_conceptos = N.id_conceptos)'),
            'abril' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 4 AND id_conceptos = N.id_conceptos)'),
            'mayo' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 5 AND id_conceptos = N.id_conceptos)'),
            'junio' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 6 AND id_conceptos = N.id_conceptos)'),
            'total' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha) BETWEEN 1 AND 6 AND id_conceptos = N.id_conceptos)')
        ])
            ->from('conceptos as N')
            ->enableHydration(false)
            ->where(['N.id_condominio' => $id_condominio])
            ->where(['YEAR(N.fecha)' => $time])
            ->group(['concepto']);
        $this->set('caja', $caja);
        /*-------------------------------------------------------Total Gastos caja chica -----------------------------------------------------------------*/
        $ToCajaTable = TableRegistry::get('Conceptos');
        $cajaTotal = $ToCajaTable->find();
        $cajaTotal->select([
            'N.id_condominio',
            'concepto' => 'N.concept',
            'enero' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 1 AND id_conceptos = N.id_conceptos)'),
            'febrero' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 2 AND id_conceptos = N.id_conceptos)'),
            'marzo' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 3 AND id_conceptos = N.id_conceptos)'),
            'abril' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 4 AND id_conceptos = N.id_conceptos)'),
            'mayo' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 5 AND id_conceptos = N.id_conceptos)'),
            'junio' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 6 AND id_conceptos = N.id_conceptos)'),
            'total' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha) BETWEEN 1 AND 6 AND id_conceptos = N.id_conceptos)')
        ])
            ->from('conceptos as N')
            ->enableHydration(false)
            ->where(['N.id_condominio' => $id_condominio])
            ->where(['YEAR(N.fecha)' => $time])
            ->group(['id_gasto']);
        $this->set('cajaTotal', $cajaTotal);
        /*------------------------------------------------------- Total Gastos -----------------------------------------------------------------*/
        $totalFijosTable = TableRegistry::get('Gasto');
        $totalFijos = $totalFijosTable->find();
        $totalFijos->select([
            'G.id_condominio',
            'enero' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = G.id_gasto)'),
            'febrero' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = G.id_gasto)'),
            'marzo' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = G.id_gasto)'),
            'abril' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = G.id_gasto)'),
            'mayo' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = G.id_gasto)'),
            'junio' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = G.id_gasto)'),
            'total' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = G.id_gasto)')
        ])
            ->from('gasto as G')
            ->where(['G.id_condominio' => $id_condominio])
            ->where(['YEAR(G.fecha)' => $time])
            ->where(['id_tipo =' => 1])
            ->where(['confirmacion =' => 'SI'])
            ->group('G.id_condominio');
        $totalFijosResults = $totalFijos->toArray();
        $sumTotalFijos = array_reduce($totalFijosResults, function($carry, $item) {
            $carry['enero'] += $item['enero'];
            $carry['febrero'] += $item['febrero'];
            $carry['marzo'] += $item['marzo'];
            $carry['abril'] += $item['abril'];
            $carry['mayo'] += $item['mayo'];
            $carry['junio'] += $item['junio'];
            $carry['total'] += $item['total'];
            return $carry;
        }, ['enero' => 0, 'febrero' => 0, 'marzo' => 0, 'abril' => 0, 'mayo' => 0, 'junio' => 0, 'total' => 0]);

        $totalVariablesTable = TableRegistry::get('Gasto');
        $totalVariables = $totalVariablesTable->find();
        $totalVariables->select([
            'G.id_condominio',
            'enero' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = G.id_gasto)'),
            'febrero' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = G.id_gasto)'),
            'marzo' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = G.id_gasto)'),
            'abril' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = G.id_gasto)'),
            'mayo' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = G.id_gasto)'),
            'junio' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = G.id_gasto)'),
            'total' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = G.id_gasto)')
        ])
            ->from('gasto as G')
            ->where(['G.id_condominio' => $id_condominio])
            ->where(['YEAR(G.fecha)' => $time])
            ->where(['id_tipo =' => 2])
            ->where(['confirmacion =' => 'SI'])
            ->group('G.id_condominio');
        $totalVariablesResults = $totalVariables->toArray();
        $sumTotalVariables = array_reduce($totalVariablesResults, function($carry, $item) {
            $carry['enero'] += $item['enero'];
            $carry['febrero'] += $item['febrero'];
            $carry['marzo'] += $item['marzo'];
            $carry['abril'] += $item['abril'];
            $carry['mayo'] += $item['mayo'];
            $carry['junio'] += $item['junio'];
            $carry['total'] += $item['total'];
            return $carry;
        }, ['enero' => 0, 'febrero' => 0, 'marzo' => 0, 'abril' => 0, 'mayo' => 0, 'junio' => 0, 'total' => 0]);

        $totalCajaTable = TableRegistry::get('Conceptos');
        $totalCaja = $totalCajaTable->find();
        $totalCaja->select([
            'N.id_condominio',
            'concepto' => 'N.concept',
            'enero' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 1 AND id_conceptos = N.id_conceptos)'),
            'febrero' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 2 AND id_conceptos = N.id_conceptos)'),
            'marzo' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 3 AND id_conceptos = N.id_conceptos)'),
            'abril' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 4 AND id_conceptos = N.id_conceptos)'),
            'mayo' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 5 AND id_conceptos = N.id_conceptos)'),
            'junio' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 6 AND id_conceptos = N.id_conceptos)'),
            'total' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha) BETWEEN 1 AND 6 AND id_conceptos = N.id_conceptos)')
        ])
            ->from('conceptos as N')
            ->enableHydration(false)
            ->where(['N.id_condominio' => $id_condominio])
            ->where(['YEAR(N.fecha)' => $time])
            ->group(['id_conceptos']);
        $totalCajaResults = $totalCaja->toArray();
        $sumTotalCaja = array_reduce($totalCajaResults, function($carry, $item) {
            $carry['enero'] += $item['enero'];
            $carry['febrero'] += $item['febrero'];
            $carry['marzo'] += $item['marzo'];
            $carry['abril'] += $item['abril'];
            $carry['mayo'] += $item['mayo'];
            $carry['junio'] += $item['junio'];
            $carry['total'] += $item['total'];
            return $carry;
        }, ['enero' => 0, 'febrero' => 0, 'marzo' => 0, 'abril' => 0, 'mayo' => 0, 'junio' => 0, 'total' => 0]);

        $grandTotal = [
            'enero' => $sumTotalFijos['enero'] + $sumTotalVariables['enero'] + $sumTotalCaja['enero'],
            'febrero' => $sumTotalFijos['febrero'] + $sumTotalVariables['febrero'] + $sumTotalCaja['febrero'],
            'marzo' => $sumTotalFijos['marzo'] + $sumTotalVariables['marzo'] + $sumTotalCaja['marzo'],
            'abril' => $sumTotalFijos['abril'] + $sumTotalVariables['abril'] + $sumTotalCaja['abril'],
            'mayo' => $sumTotalFijos['mayo'] + $sumTotalVariables['mayo'] + $sumTotalCaja['mayo'],
            'junio' => $sumTotalFijos['junio'] + $sumTotalVariables['junio'] + $sumTotalCaja['junio'],
            'total' => $sumTotalFijos['total'] + $sumTotalVariables['total'] + $sumTotalCaja['total']
        ];

        $this->set(compact('totalFijos', 'totalVariables', 'totalCaja', 'grandTotal'));
        /*-------------------------- Pago -------------------------------*/
        $pagosTable = TableRegistry::get('Pago');
        $pagos = $pagosTable->find();
        $pagos->select([
            'P.id_condomino',
            'departamento' => (\App\Database\SqlDialect::forConnection()->isPostgres() ? $pagos->newExpr(\App\Database\SqlDialect::forConnection()->leadingInteger('c.numero_departamento')) : $pagos->func()->cast('c.numero_departamento', 'unsigned')),
            'enero' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 1 AND id_pago = P.id_pago)'),
            'febrero' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 2 AND id_pago = P.id_pago)'),
            'marzo' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 3 AND id_pago = P.id_pago)'),
            'abril' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 4 AND id_pago = P.id_pago)'),
            'mayo' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 5 AND id_pago = P.id_pago)'),
            'junio' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 6 AND id_pago = P.id_pago)'),
            'total' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 6 AND id_pago = P.id_pago)')
        ])
            ->from('pago as P')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = P.id_condomino',
                ]
            ])
            ->where(['P.id_condominio' => $id_condominio])
            ->where(['YEAR(P.fecha)' => $time])
            ->group('P.id_condomino')
            ->order(['departamento' => 'ASC']);
        $this->set('pagos', $pagos);

        $pagos2Table = TableRegistry::get('Pago');
        $pagosTotales = $pagos2Table->find();
        $pagosTotales->select([
            'M.id_condomino',
            'departamento' =>'c.numero_departamento',
            'enero' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 1 AND id_pago = M.id_pago)'),
            'febrero' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 2 AND id_pago = M.id_pago)'),
            'marzo' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 3 AND id_pago = M.id_pago)'),
            'abril' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 4 AND id_pago = M.id_pago)'),
            'mayo' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 5 AND id_pago = M.id_pago)'),
            'junio' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 6 AND id_pago = M.id_pago)'),
            'total' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 6 AND id_pago = M.id_pago)')
        ])
            ->from('pago as M')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = M.id_condomino',
                ]
            ])
            ->where(['M.id_condominio' => $id_condominio])
            ->where(['YEAR(M.fecha)' => $time])
            ->group('M.id_condominio');
        $pag = $pagosTotales->all();
        $this->set('pag', $pag);
        /*-------------------------- Adeudos -------------------------------*/
        $adeudosTable = TableRegistry::get('Adeudos');
        $adeudos = $adeudosTable->find();
        $adeudos->select([
            'T.id_condomino',
            'departamento' => (\App\Database\SqlDialect::forConnection()->isPostgres() ? $adeudos->newExpr(\App\Database\SqlDialect::forConnection()->leadingInteger('c.numero_departamento')) : $adeudos->func()->cast('c.numero_departamento', 'unsigned')),
            'saldo' => 'sc.contra',
            'enero' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 1 AND id_adeudo = T.id_adeudo)'),
            'febrero' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 2 AND id_adeudo = T.id_adeudo)'),
            'marzo' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 3 AND id_adeudo = T.id_adeudo)'),
            'abril' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 4 AND id_adeudo = T.id_adeudo)'),
            'mayo' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 5 AND id_adeudo = T.id_adeudo)'),
            'junio' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 6 AND id_adeudo = T.id_adeudo)'),
            'total' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) BETWEEN 1 AND 6 AND id_adeudo = T.id_adeudo)')
        ])
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino',
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = T.id_condomino',
                ]
            ])
            ->join([
                'sc' => [
                    'table' => 'saldo_condomino',
                    'type' => 'INNER',
                    'conditions' => [
                        'sc.id_condomino = T.id_condomino',
                        'MONTH(sc.fecha) BETWEEN 1 AND 6'
                    ]                ]
            ])
            ->where(['T.id_condominio' => $id_condominio])
            ->where(['YEAR(T.mes)' => $time])
            ->where(['T.estatus' => 1])
            ->group('T.id_condomino')
            ->order(['departamento' => 'ASC']);
        $this->set('adeudos', $adeudos);
        /*-------------------------- Total Adeudos -------------------------------*/
        $totalAdeudosTable = TableRegistry::get('Adeudos');
        $pseudosTotals = $totalAdeudosTable->find();
        $pseudosTotals->select([
            'T.id_condomino',
            'saldo' => $adeudos->func()->sum('CASE WHEN MONTH(sc.fecha) BETWEEN 1 AND 6 THEN sc.contra ELSE 0 END'),
            'enero' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 1 AND id_adeudo = T.id_adeudo)'),
            'febrero' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 2 AND id_adeudo = T.id_adeudo)'),
            'marzo' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 3 AND id_adeudo = T.id_adeudo)'),
            'abril' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 4 AND id_adeudo = T.id_adeudo)'),
            'mayo' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 5 AND id_adeudo = T.id_adeudo)'),
            'junio' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 6 AND id_adeudo = T.id_adeudo)'),
            'total' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) BETWEEN 1 AND 6 AND id_adeudo = T.id_adeudo)')
        ])
            ->join([
                'sc' => [
                    'table' => 'saldo_condomino',
                    'type' => 'INNER',
                    'conditions' => 'sc.id_condomino = T.id_condomino',
                ]
            ])
            ->from('adeudos as T')
            ->enableHydration(false)
            ->where(['T.id_condominio' => $id_condominio])
            ->where(['YEAR(T.mes)' => $time])
            ->where(['T.estatus' => 1])
            ->group('T.id_condominio');

        $adTota = $pseudosTotals->all();
        $this->set('adTota', $adTota);

        $this->viewBuilder()->setClassName('CakePdf.Pdf');
        $this->viewBuilder()->setOption(
            'pdfConfig',
            [
                'orientation' => 'portrait',
                'download' => true,
                'filename' => 'Informe' . $id_condominio . $time . '.pdf'
            ]
        );
    }


    /**
     * @param null $id_condominio
     *
     * Informe segundo semestre
     *
     **/


    public function informeSeg($id_condominio = null, $fecha = null)
    {
        //$now = $time;

        if($fecha == null){
            $time = date("Y");
            //$time->i18nFormat('yyyy');
        }else{
            $time = $fecha;
        }
        $this->set('time', $time);


        $this->loadModel('condomino');
        $condominosT = $this->condomino->find()
            ->select([
                'id_condomino'
            ])
            ->where([
                'id_condominio' => $id_condominio
            ]);
        $this->set('condominosT', $condominosT);


        foreach ($condominosT as $condoTotal){

        }

        /*-------------------------- Condominio -------------------------------*/


        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);
        $this->set(compact('condominio'));


        $this->loadModel('condomino');
        $condominos = $this->condomino->find()
            ->select([
                'id_condomino' =>'c.id_condomino',
                'departamento' =>'c.numero_departamento',
            ])
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Condomino.id_condomino',
                ]
            ])
            ->where([
                'Condomino.id_condominio' => $id_condominio
            ]);
        $this->set('condominos', $condominos);

        $this->loadModel('Gasto');
        $gasto = $this->Gasto
            ->find()
            ->select([
                'id_gasto',
                'id_condominio',
                'id_tipo',
                'id_proveedores',
                'gasto',
                'fecha',
                'fecha'
            ])

            ->where(['id_condominio' => $id_condominio ])
            ->where(['id_condominio' => $id_condominio ]);

        $rows = $gasto->all();
        $this->set('gasto', $rows);


        /*-------------------------- Saldo Inicial desde Tabla de SI -------------------------------*/
        $gastosTotTable = TableRegistry::get('Gasto');
        $TotalGastos = $gastosTotTable->find();
        $TotalGastos->select([
            'F.id_condominio',
            'categoria' =>'c.categoria',
            'julio' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 7 AND id_gasto = F.id_gasto)'),
            'agosto' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 8 AND id_gasto = F.id_gasto)'),
            'septiembre' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 9 AND id_gasto = F.id_gasto)'),
            'octubre' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 10 AND id_gasto = F.id_gasto)'),
            'noviembre' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 11 AND id_gasto = F.id_gasto)'),
            'diciembre' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 12 AND id_gasto = F.id_gasto)'),
            'total' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 7 AND 12 AND id_gasto = F.id_gasto)')
        ])
            ->from('gasto as F')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_categoria = F.id_categoria',
                ]
            ])
            ->where(['F.id_condominio' => $id_condominio])
            ->where(['confirmacion =' => 'SI'])
            ->where(['YEAR(F.fecha)' => $time]);
        $totGas  = $TotalGastos->all();
        $this->set('totGas', $totGas);

        /*-------------------------- Saldo Inicial -------------------------------*/
        $saldoTable = TableRegistry::get('saldo_condominio');

        $saldoPrim = $saldoTable->find();
        $saldoPrim->select([
            'id_condominio' => 'S.id_condominio',
            'julio' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 7 THEN S.saldo ELSE 0 END'),
            'agosto' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 8 THEN S.saldo ELSE 0 END'),
            'septiembre' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 9 THEN S.saldo ELSE 0 END'),
            'octubre' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 10 THEN S.saldo ELSE 0 END'),
            'noviembre' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 11 THEN S.saldo ELSE 0 END'),
            'diciembre' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 12 THEN S.saldo ELSE 0 END'),
            'total' => $saldoPrim->func()->sum('CASE WHEN MONTH(S.fecha) BETWEEN 7 AND 12 THEN S.saldo ELSE 0 END')
        ])
            ->from(['S' => 'saldo_condominio'])
            ->where(['S.id_condominio' => $id_condominio])
            ->where(['YEAR(S.fecha)' => $time])
            ->group('S.id_condominio');

        $saldo = $saldoPrim->all();

        $this->set('saldo', $saldo);


        /*-------------------------- Saldo final -------------------------------*/
        $saldoTotaTable = TableRegistry::get('saldo_cierre');

        $saldoTotalPrim = $saldoTotaTable->find();
        $saldoTotalPrim->select([
            'id_condominio' => 'S.id_condominio',
            'julio' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 7 THEN S.saldo ELSE 0 END'),
            'agosto' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 8 THEN S.saldo ELSE 0 END'),
            'septiembre' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 9 THEN S.saldo ELSE 0 END'),
            'octubre' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 10 THEN S.saldo ELSE 0 END'),
            'noviembre' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 11 THEN S.saldo ELSE 0 END'),
            'diciembre' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) = 12 THEN S.saldo ELSE 0 END'),
            'total' => $saldoTotalPrim->func()->sum('CASE WHEN MONTH(S.fecha) BETWEEN 7 AND 12 THEN S.saldo ELSE 0 END')
        ])
            ->from(['S' => 'saldo_cierre'])  // Cambié 'I' por 'S' para la tabla
            ->where(['S.id_condominio' => $id_condominio])  // Filtro por condominio
            ->where(['YEAR(S.fecha)' => $time])  // Filtro por año
            ->group('S.id_condominio');  // Agrupar por id_condominio

        $finalAmount = $saldoTotalPrim->all();

        $this->set('finalAmount', $finalAmount);


        /*-------------------------- Ingresos por Cuotas -------------------------------*/
        $cuotasTable = TableRegistry::get('Pago');
        $cuotasTotal = $cuotasTable->find();
        $cuotasTotal->select([
            'J.id_condominio',
            'tipo'=>'tp.nombre',
            'julio' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 7 AND id_pago = J.id_pago)'),
            'agosto' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 8 AND id_pago = J.id_pago)'),
            'septiembre' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 9 AND id_pago = J.id_pago)'),
            'octubre' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 10 AND id_pago = J.id_pago)'),
            'noviembre' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 11 AND id_pago = J.id_pago)'),
            'diciembre' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 12 AND id_pago = J.id_pago)'),
            'total' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 7 AND 12 AND id_pago = J.id_pago)')
        ])
            ->from('pago as J')
            ->enableHydration(false)
            ->join([
                'tp' => [
                    'table' => 'tipo_pago', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'J.id_tipo = tp.id_tipo',
                ]
            ])
            ->where(['J.id_condominio' => $id_condominio])
            ->where(['YEAR(J.fecha)' => $time])
            ->group('tp.id_tipo');
        $cuotas = $cuotasTotal->all();
        $this->set('cuotas', $cuotas);
        /*-------------------------- Ingresos totales -------------------------------*/
        $itTable = TableRegistry::get('Pago');
        $itTotal = $itTable->find();
        $itTotal->select([
            'J.id_condominio',
            'julio' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 7 AND id_pago = J.id_pago)'),
            'agosto' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 8 AND id_pago = J.id_pago)'),
            'septiembre' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 9 AND id_pago = J.id_pago)'),
            'octubre' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 10 AND id_pago = J.id_pago)'),
            'noviembre' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 11 AND id_pago = J.id_pago)'),
            'diciembre' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 12 AND id_pago = J.id_pago)'),
            'total' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 7 AND 12 AND id_pago = J.id_pago)')
        ])
            ->from('pago as J')
            ->where(['J.id_condominio' => $id_condominio])
            ->where(['YEAR(J.fecha)' => $time])
            ->group('J.id_condominio');
        $its = $itTotal->all();
        $this->set('its', $its);
        /*-------------------------- Gastos Fijos -------------------------------*/
        $gafijosTable = TableRegistry::get('Gasto');
        $fijos = $gafijosTable->find();
        $fijos->select([
            'D.id_condominio',
            'categoria' =>'c.categoria',
            'julio' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 7 AND id_gasto = D.id_gasto)'),
            'agosto' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 8 AND id_gasto = D.id_gasto)'),
            'septiembre' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 9 AND id_gasto = D.id_gasto)'),
            'octubre' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 10 AND id_gasto = D.id_gasto)'),
            'noviembre' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 11 AND id_gasto = D.id_gasto)'),
            'diciembre' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 12 AND id_gasto = D.id_gasto)'),
            'total' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 7 AND 12 AND id_gasto = D.id_gasto)')
        ])
            ->from('gasto as D')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_categoria = D.id_categoria',
                ]
            ])
            ->where(['D.id_condominio' => $id_condominio])
            ->where(['YEAR(D.fecha)' => $time])
            ->where(['id_tipo =' => 1])
            ->where(['confirmacion =' => 'SI'])
            ->group('D.id_categoria');
        $this->set('fijos', $fijos);
        //___________________________________________________________  Total gastos fijos _____________________________________________//
        $totalFijosTable = TableRegistry::get('Gasto');
        $fij = $totalFijosTable->find();
        $fij->select([
            'G.id_condominio',
            'julio' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 7 AND id_gasto = G.id_gasto)'),
            'agosto' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 8 AND id_gasto = G.id_gasto)'),
            'septiembre' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 9 AND id_gasto = G.id_gasto)'),
            'octubre' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 10 AND id_gasto = G.id_gasto)'),
            'noviembre' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 11 AND id_gasto = G.id_gasto)'),
            'diciembre' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 12 AND id_gasto = G.id_gasto)'),
            'total' => $fij->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 7 AND 12 AND id_gasto = G.id_gasto)')
        ])
            ->from('gasto as G')
            ->where(['G.id_condominio' => $id_condominio])
            ->where(['YEAR(G.fecha)' => $time])
            ->where(['id_tipo =' => 1])
            ->where(['confirmacion =' => 'SI'])
            ->group('G.id_condominio');
        $this->set('fij', $fij);
        /*-------------------------- Gastos Variables -------------------------------*/
        $gastosTable = TableRegistry::get('Gasto');
        $gastos = $gastosTable->find();
        $gastos->select([
            'G.id_condominio',
            'categoria' =>'c.categoria',
            'julio' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 7 AND id_gasto = G.id_gasto)'),
            'agosto' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 8 AND id_gasto = G.id_gasto)'),
            'septiembre' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 9 AND id_gasto = G.id_gasto)'),
            'octubre' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 10 AND id_gasto = G.id_gasto)'),
            'noviembre' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 11 AND id_gasto = G.id_gasto)'),
            'diciembre' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 12 AND id_gasto = G.id_gasto)'),
            'total' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 7 AND 12 AND id_gasto = G.id_gasto)')
        ])
            ->from('gasto as G')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_categoria = G.id_categoria',
                ]
            ])
            ->where(['G.id_condominio' => $id_condominio])
            ->where(['YEAR(G.fecha)' => $time])
            ->where(['id_tipo =' => 2])
            ->where(['confirmacion =' => 'SI'])
            ->group('G.id_categoria');
        $this->set('gastos', $gastos);

        //_____________________________________________ Total gastos variables ________________________________________________________________//
        $totalVariablesTable = TableRegistry::get('Gasto');
        $varia = $totalVariablesTable->find();
        $varia->select([
            'G.id_condominio',
            'julio' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 7 AND id_gasto = G.id_gasto)'),
            'agosto' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 8 AND id_gasto = G.id_gasto)'),
            'septiembre' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 9 AND id_gasto = G.id_gasto)'),
            'octubre' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 10 AND id_gasto = G.id_gasto)'),
            'noviembre' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 11 AND id_gasto = G.id_gasto)'),
            'diciembre' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 12 AND id_gasto = G.id_gasto)'),
            'total' => $varia->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 7 AND 12 AND id_gasto = G.id_gasto)')
        ])
            ->from('gasto as G')
            ->where(['G.id_condominio' => $id_condominio])
            ->where(['YEAR(G.fecha)' => $time])
            ->where(['id_tipo =' => 2])
            ->where(['confirmacion =' => 'SI'])
            ->group('G.id_condominio');
        $this->set('varia', $varia);
        /*------------------------------------------------------- Gastos caja chica -----------------------------------------------------------------*/
        $cajaTable = TableRegistry::get('Conceptos');
        $caja = $cajaTable->find();
        $caja->select([
            'N.id_condominio',
            'concepto' => 'N.concept',
            'julio' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 1 AND id_conceptos = N.id_conceptos)'),
            'agosto' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 2 AND id_conceptos = N.id_conceptos)'),
            'septiembre' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 3 AND id_conceptos = N.id_conceptos)'),
            'octubre' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 4 AND id_conceptos = N.id_conceptos)'),
            'noviembre' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 5 AND id_conceptos = N.id_conceptos)'),
            'diciembre' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 6 AND id_conceptos = N.id_conceptos)'),
            'total' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha) BETWEEN 1 AND 6 AND id_conceptos = N.id_conceptos)')
        ])
            ->from('conceptos as N')
            ->enableHydration(false)
            ->where(['N.id_condominio' => $id_condominio])
            ->where(['YEAR(N.fecha)' => $time])
            ->group(['concepto']);
        $this->set('caja', $caja);
        /*-------------------------------------------------------Total Gastos caja chica -----------------------------------------------------------------*/
        $ToCajaTable = TableRegistry::get('Conceptos');
        $cajaTotal = $ToCajaTable->find();
        $cajaTotal->select([
            'N.id_condominio',
            'concepto' => 'N.concept',
            'julio' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 1 AND id_conceptos = N.id_conceptos)'),
            'agosto' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 2 AND id_conceptos = N.id_conceptos)'),
            'septiembre' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 3 AND id_conceptos = N.id_conceptos)'),
            'octubre' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 4 AND id_conceptos = N.id_conceptos)'),
            'noviembre' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 5 AND id_conceptos = N.id_conceptos)'),
            'diciembre' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 6 AND id_conceptos = N.id_conceptos)'),
            'total' => $caja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha) BETWEEN 1 AND 6 AND id_conceptos = N.id_conceptos)')
        ])
            ->from('conceptos as N')
            ->enableHydration(false)
            ->where(['N.id_condominio' => $id_condominio])
            ->where(['YEAR(N.fecha)' => $time])
            ->group(['id_gasto']);
        $this->set('cajaTotal', $cajaTotal);
        /*------------------------------------------------------- Total Gastos -----------------------------------------------------------------*/
        $totalFijosTable = TableRegistry::get('Gasto');
        $totalFijos = $totalFijosTable->find();
        $totalFijos->select([
            'G.id_condominio',
            'julio' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = G.id_gasto)'),
            'agosto' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = G.id_gasto)'),
            'septiembre' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = G.id_gasto)'),
            'octubre' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = G.id_gasto)'),
            'noviembre' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = G.id_gasto)'),
            'diciembre' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = G.id_gasto)'),
            'total' => $totalFijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = G.id_gasto)')
        ])
            ->from('gasto as G')
            ->where(['G.id_condominio' => $id_condominio])
            ->where(['YEAR(G.fecha)' => $time])
            ->where(['id_tipo =' => 1])
            ->where(['confirmacion =' => 'SI'])
            ->group('G.id_condominio');
        $totalFijosResults = $totalFijos->toArray();
        $sumTotalFijos = array_reduce($totalFijosResults, function($carry, $item) {
            $carry['julio'] += $item['julio'];
            $carry['agosto'] += $item['agosto'];
            $carry['septiembre'] += $item['septiembre'];
            $carry['octubre'] += $item['octubre'];
            $carry['noviembre'] += $item['noviembre'];
            $carry['diciembre'] += $item['diciembre'];
            $carry['total'] += $item['total'];
            return $carry;
        }, ['julio' => 0, 'agosto' => 0, 'septiembre' => 0, 'octubre' => 0, 'noviembre' => 0, 'diciembre' => 0, 'total' => 0]);

        $totalVariablesTable = TableRegistry::get('Gasto');
        $totalVariables = $totalVariablesTable->find();
        $totalVariables->select([
            'G.id_condominio',
            'julio' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = G.id_gasto)'),
            'agosto' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = G.id_gasto)'),
            'septiembre' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = G.id_gasto)'),
            'octubre' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = G.id_gasto)'),
            'noviembre' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = G.id_gasto)'),
            'diciembre' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = G.id_gasto)'),
            'total' => $totalVariables->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = G.id_gasto)')
        ])
            ->from('gasto as G')
            ->where(['G.id_condominio' => $id_condominio])
            ->where(['YEAR(G.fecha)' => $time])
            ->where(['id_tipo =' => 2])
            ->where(['confirmacion =' => 'SI'])
            ->group('G.id_condominio');
        $totalVariablesResults = $totalVariables->toArray();
        $sumTotalVariables = array_reduce($totalVariablesResults, function($carry, $item) {
            $carry['julio'] += $item['julio'];
            $carry['agosto'] += $item['agosto'];
            $carry['septiembre'] += $item['septiembre'];
            $carry['octubre'] += $item['octubre'];
            $carry['noviembre'] += $item['noviembre'];
            $carry['diciembre'] += $item['diciembre'];
            $carry['total'] += $item['total'];
            return $carry;
        }, ['julio' => 0, 'agosto' => 0, 'septiembre' => 0, 'octubre' => 0, 'noviembre' => 0, 'diciembre' => 0, 'total' => 0]);

        $totalCajaTable = TableRegistry::get('Conceptos');
        $totalCaja = $totalCajaTable->find();
        $totalCaja->select([
            'N.id_condominio',
            'concepto' => 'N.concept',
            'julio' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 1 AND id_conceptos = N.id_conceptos)'),
            'agosto' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 2 AND id_conceptos = N.id_conceptos)'),
            'septiembre' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 3 AND id_conceptos = N.id_conceptos)'),
            'octubre' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 4 AND id_conceptos = N.id_conceptos)'),
            'noviembre' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 5 AND id_conceptos = N.id_conceptos)'),
            'diciembre' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha)= 6 AND id_conceptos = N.id_conceptos)'),
            'total' => $totalCaja->func()->sum('(SELECT gasto FROM conceptos WHERE month(fecha) BETWEEN 1 AND 6 AND id_conceptos = N.id_conceptos)')
        ])
            ->from('conceptos as N')
            ->enableHydration(false)
            ->where(['N.id_condominio' => $id_condominio])
            ->where(['YEAR(N.fecha)' => $time])
            ->group(['id_conceptos']);
        $totalCajaResults = $totalCaja->toArray();
        $sumTotalCaja = array_reduce($totalCajaResults, function($carry, $item) {
            $carry['julio'] += $item['julio'];
            $carry['agosto'] += $item['agosto'];
            $carry['septiembre'] += $item['septiembre'];
            $carry['octubre'] += $item['octubre'];
            $carry['noviembre'] += $item['noviembre'];
            $carry['diciembre'] += $item['diciembre'];
            $carry['total'] += $item['total'];
            return $carry;
        }, ['julio' => 0, 'agosto' => 0, 'septiembre' => 0, 'octubre' => 0, 'noviembre' => 0, 'diciembre' => 0, 'total' => 0]);

        $grandTotal = [
            'julio' => $sumTotalFijos['julio'] + $sumTotalVariables['julio'] + $sumTotalCaja['julio'],
            'agosto' => $sumTotalFijos['agosto'] + $sumTotalVariables['agosto'] + $sumTotalCaja['agosto'],
            'septiembre' => $sumTotalFijos['septiembre'] + $sumTotalVariables['septiembre'] + $sumTotalCaja['septiembre'],
            'octubre' => $sumTotalFijos['octubre'] + $sumTotalVariables['octubre'] + $sumTotalCaja['octubre'],
            'noviembre' => $sumTotalFijos['noviembre'] + $sumTotalVariables['noviembre'] + $sumTotalCaja['noviembre'],
            'diciembre' => $sumTotalFijos['diciembre'] + $sumTotalVariables['diciembre'] + $sumTotalCaja['diciembre'],
            'total' => $sumTotalFijos['total'] + $sumTotalVariables['total'] + $sumTotalCaja['total']
        ];

        $this->set(compact('totalFijos', 'totalVariables', 'totalCaja', 'grandTotal'));
        /*-------------------------- Pago -------------------------------*/
        $pagosTable = TableRegistry::get('Pago');
        $pagos = $pagosTable->find();
        $pagos->select([
            'P.id_condomino',
            'departamento' => (\App\Database\SqlDialect::forConnection()->isPostgres() ? $pagos->newExpr(\App\Database\SqlDialect::forConnection()->leadingInteger('c.numero_departamento')) : $pagos->func()->cast('c.numero_departamento', 'unsigned')),
            'julio' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 1 AND id_pago = P.id_pago)'),
            'agosto' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 2 AND id_pago = P.id_pago)'),
            'septiembre' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 3 AND id_pago = P.id_pago)'),
            'octubre' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 4 AND id_pago = P.id_pago)'),
            'noviembre' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 5 AND id_pago = P.id_pago)'),
            'diciembre' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 6 AND id_pago = P.id_pago)'),
            'total' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 6 AND id_pago = P.id_pago)')
        ])
            ->from('pago as P')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = P.id_condomino',
                ]
            ])
            ->where(['P.id_condominio' => $id_condominio])
            ->where(['YEAR(P.fecha)' => $time])
            ->group('P.id_condomino')
            ->order(['departamento' => 'ASC']);
        $this->set('pagos', $pagos);

        $pagos2Table = TableRegistry::get('Pago');
        $pagosTotales = $pagos2Table->find();
        $pagosTotales->select([
            'M.id_condomino',
            'departamento' =>'c.numero_departamento',
            'julio' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 1 AND id_pago = M.id_pago)'),
            'agosto' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 2 AND id_pago = M.id_pago)'),
            'septiembre' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 3 AND id_pago = M.id_pago)'),
            'octubre' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 4 AND id_pago = M.id_pago)'),
            'noviembre' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 5 AND id_pago = M.id_pago)'),
            'diciembre' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 6 AND id_pago = M.id_pago)'),
            'total' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 6 AND id_pago = M.id_pago)')
        ])
            ->from('pago as M')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = M.id_condomino',
                ]
            ])
            ->where(['M.id_condominio' => $id_condominio])
            ->where(['YEAR(M.fecha)' => $time])
            ->group('M.id_condominio');
        $pag = $pagosTotales->all();
        $this->set('pag', $pag);
        /*-------------------------- Adeudos -------------------------------*/
        $adeudosTable = TableRegistry::get('Adeudos');
        $adeudos = $adeudosTable->find();
        $adeudos->select([
            'T.id_condomino',
            'departamento' => (\App\Database\SqlDialect::forConnection()->isPostgres() ? $adeudos->newExpr(\App\Database\SqlDialect::forConnection()->leadingInteger('c.numero_departamento')) : $adeudos->func()->cast('c.numero_departamento', 'unsigned')),
            'saldo' => 'sc.contra',
            'julio' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 1 AND id_adeudo = T.id_adeudo)'),
            'agosto' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 2 AND id_adeudo = T.id_adeudo)'),
            'septiembre' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 3 AND id_adeudo = T.id_adeudo)'),
            'octubre' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 4 AND id_adeudo = T.id_adeudo)'),
            'noviembre' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 5 AND id_adeudo = T.id_adeudo)'),
            'diciembre' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 6 AND id_adeudo = T.id_adeudo)'),
            'total' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) BETWEEN 1 AND 6 AND id_adeudo = T.id_adeudo)')
        ])
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino',
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = T.id_condomino',
                ]
            ])
            ->join([
                'sc' => [
                    'table' => 'saldo_condomino',
                    'type' => 'INNER',
                    'conditions' => [
                        'sc.id_condomino = T.id_condomino',
                        'MONTH(sc.fecha) BETWEEN 1 AND 6'
                    ]                ]
            ])
            ->where(['T.id_condominio' => $id_condominio])
            ->where(['YEAR(T.mes)' => $time])
            ->where(['T.estatus' => 1])
            ->group('T.id_condomino')
            ->order(['departamento' => 'ASC']);
        $this->set('adeudos', $adeudos);
        /*-------------------------- Total Adeudos -------------------------------*/
        $totalAdeudosTable = TableRegistry::get('Adeudos');
        $pseudosTotals = $totalAdeudosTable->find();
        $pseudosTotals->select([
            'T.id_condomino',
            'saldo' => $adeudos->func()->sum('CASE WHEN MONTH(sc.fecha) BETWEEN 1 AND 6 THEN sc.contra ELSE 0 END'),
            'julio' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 1 AND id_adeudo = T.id_adeudo)'),
            'agosto' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 2 AND id_adeudo = T.id_adeudo)'),
            'septiembre' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 3 AND id_adeudo = T.id_adeudo)'),
            'octubre' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 4 AND id_adeudo = T.id_adeudo)'),
            'noviembre' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 5 AND id_adeudo = T.id_adeudo)'),
            'diciembre' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) = 6 AND id_adeudo = T.id_adeudo)'),
            'total' => $pseudosTotals->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) BETWEEN 1 AND 6 AND id_adeudo = T.id_adeudo)')
        ])
            ->join([
                'sc' => [
                    'table' => 'saldo_condomino',
                    'type' => 'INNER',
                    'conditions' => 'sc.id_condomino = T.id_condomino',
                ]
            ])
            ->from('adeudos as T')
            ->enableHydration(false)
            ->where(['T.id_condominio' => $id_condominio])
            ->where(['T.estatus' => 1])
            ->where(['YEAR(T.mes)' => $time])
            ->group('T.id_condominio');

        $adTota = $pseudosTotals->all();
        $this->set('adTota', $adTota);

    }


    public function infoAnual($id_condominio = null, $fecha = null)
    {
        //$now = $time;

        if($fecha == null){
            $time = date("Y");
            //$time->i18nFormat('yyyy');
        }else{
            $time = $fecha;
        }
        $this->set('time', $time);

        $this->loadModel('condomino');
        $condominosT = $this->condomino->find()
            ->select([
                'id_condomino'
            ])
            ->where([
                'id_condominio' => $id_condominio
            ]);
        $this->set('condominosT', $condominosT);


        foreach ($condominosT as $condoTotal){

        }

        /*-------------------------- Condominio -------------------------------*/


        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);
        $this->set(compact('condominio'));


        $this->loadModel('condomino');
        $condominos = $this->condomino->find()
            ->select([
                'id_condomino' =>'c.id_condomino',
                'departamento' =>'c.numero_departamento',
            ])
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Condomino.id_condomino',
                ]
            ])
            ->where([
                'Condomino.id_condominio' => $id_condominio
            ]);
        $this->set('condominos', $condominos);

        $this->loadModel('Gasto');
        $gasto = $this->Gasto
            ->find()
            ->select([
                'id_gasto',
                'id_condominio',
                'id_tipo',
                'id_proveedores',
                'gasto',
                'fecha_pago',
                'fecha'
            ])

            ->where(['id_condominio' => $id_condominio ])
            ->where(['id_condominio' => $id_condominio ]);

        $rows = $gasto->all();
        $this->set('gasto', $rows);
        /*-------------------------- Saldo Inicial -------------------------------*/

        $saldoTable = TableRegistry::get('saldo_condominio');
        $saldoTotal = $saldoTable->find();
        $saldoTotal->select([
            'I.id_saldo',
            'I.id_condominio',
            'enero' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 1 AND id_saldo = I.id_saldo)'),
            'febrero' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 2 AND id_saldo = I.id_saldo)'),
            'marzo' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 3 AND id_saldo = I.id_saldo)'),
            'abril' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 4 AND id_saldo = I.id_saldo)'),
            'mayo' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 5 AND id_saldo = I.id_saldo)'),
            'junio' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 6 AND id_saldo = I.id_saldo)'),
            'julio' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 7 AND id_saldo = I.id_saldo)'),
            'agosto' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 8 AND id_saldo = I.id_saldo)'),
            'septiembre' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 9 AND id_saldo = I.id_saldo)'),
            'octubre' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 10 AND id_saldo = I.id_saldo)'),
            'nombiembre' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 11 AND id_saldo = I.id_saldo)'),
            'diciembre' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 12 AND id_saldo = I.id_saldo)')
        ])
            ->from('saldo_condominio as I')
            ->where(['I.id_condominio' => $id_condominio])
            ->where(['YEAR(I.fecha)' => $time])
            ->group('I.id_condominio')
        ;
        $saldo = $saldoTotal->all();
        $this->set('saldo', $saldo);

        /*-------------------------- Ingresos por Cuotas -------------------------------*/

        $cuotasTable = TableRegistry::get('Pago');
        $cuotasTotal = $cuotasTable->find();
        $cuotasTotal->select([
            'J.id_condominio',
            'enero' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 1 AND id_pago = J.id_pago)'),
            'febrero' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 2 AND id_pago = J.id_pago)'),
            'marzo' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 3 AND id_pago = J.id_pago)'),
            'abril' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 4 AND id_pago = J.id_pago)'),
            'mayo' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 5 AND id_pago = J.id_pago)'),
            'junio' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 6 AND id_pago = J.id_pago)'),
            'julio' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 7 AND id_pago = J.id_pago)'),
            'agosto' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 8 AND id_pago = J.id_pago)'),
            'septiembre' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 9 AND id_pago = J.id_pago)'),
            'octubre' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 10 AND id_pago = J.id_pago)'),
            'noviembre' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 11 AND id_pago = J.id_pago)'),
            'diciembre' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 12 AND id_pago = J.id_pago)'),
            'total' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 12 AND id_pago = J.id_pago)')
        ])
            ->from('pago as J')
            ->where(['J.id_condominio' => $id_condominio])
            ->where(['YEAR(J.fecha)' => $time])
            ->where(['J.id_tipo' => 1])
            ->group('J.id_condominio');
        $cuotas = $cuotasTotal->all();
        $this->set('cuotas', $cuotas);

        /*-------------------------- Pago SACMEX -------------------------------*/

        $sacmexTable = TableRegistry::get('Pago');
        $sacmexTotal = $sacmexTable->find();
        $sacmexTotal->select([
            'K.id_condominio',
            'enero' => $sacmexTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 1 AND id_pago = K.id_pago)'),
            'febrero' => $sacmexTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 2 AND id_pago = K.id_pago)'),
            'marzo' => $sacmexTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 3 AND id_pago = K.id_pago)'),
            'abril' => $sacmexTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 4 AND id_pago = K.id_pago)'),
            'mayo' => $sacmexTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 5 AND id_pago = K.id_pago)'),
            'junio' => $sacmexTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 6 AND id_pago = K.id_pago)'),
            'julio' => $sacmexTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 7 AND id_pago = K.id_pago)'),
            'agosto' => $sacmexTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 8 AND id_pago = K.id_pago)'),
            'septiembre' => $sacmexTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 9 AND id_pago = K.id_pago)'),
            'octubre' => $sacmexTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 10 AND id_pago = K.id_pago)'),
            'noviembre' => $sacmexTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 11 AND id_pago = K.id_pago)'),
            'diciembre' => $sacmexTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 12 AND id_pago = K.id_pago)'),
            'total' => $sacmexTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 12 AND id_pago = K.id_pago)')
        ])
            ->from('pago as K')
            ->where(['K.id_condominio' => $id_condominio])
            ->where(['YEAR(K.fecha)' => $time])
            ->where(['id_tipo' => 7])
            ->group('K.id_condominio');
        $sacmex = $sacmexTotal->all();
        $this->set('sacmex', $sacmex);

        /*-------------------------- Ingresos totales -------------------------------*/

        $itTable = TableRegistry::get('Pago');
        $itTotal = $itTable->find();
        $itTotal->select([
            'J.id_condominio',
            'enero' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 1 AND id_pago = J.id_pago)'),
            'febrero' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 2 AND id_pago = J.id_pago)'),
            'marzo' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 3 AND id_pago = J.id_pago)'),
            'abril' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 4 AND id_pago = J.id_pago)'),
            'mayo' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 5 AND id_pago = J.id_pago)'),
            'junio' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 6 AND id_pago = J.id_pago)'),
            'julio' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 7 AND id_pago = J.id_pago)'),
            'agosto' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 8 AND id_pago = J.id_pago)'),
            'septiembre' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 9 AND id_pago = J.id_pago)'),
            'octubre' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 10 AND id_pago = J.id_pago)'),
            'noviembre' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 11 AND id_pago = J.id_pago)'),
            'diciembre' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 12 AND id_pago = J.id_pago)'),
            'total' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 12 AND id_pago = J.id_pago)')
        ])
            ->from('pago as J')
            ->where(['J.id_condominio' => $id_condominio])
            ->where(['YEAR(J.fecha)' => $time])
            ->group('J.id_condominio');
        $its = $itTotal->all();
        $this->set('its', $its);


        /*-------------------------- Gastos Fijos -------------------------------*/
        $gafijosTable = TableRegistry::get('Gasto');
        $fijos = $gafijosTable->find();
        $fijos->select([
            'D.id_condominio',
            'categoria' =>'c.categoria',
            'enero' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 1 AND id_gasto = D.id_gasto)'),
            'febrero' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 2 AND id_gasto = D.id_gasto)'),
            'marzo' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 3 AND id_gasto = D.id_gasto)'),
            'abril' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 4 AND id_gasto = D.id_gasto)'),
            'mayo' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 5 AND id_gasto = D.id_gasto)'),
            'junio' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 6 AND id_gasto = D.id_gasto)'),
            'julio' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 7 AND id_gasto = D.id_gasto)'),
            'agosto' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 8 AND id_gasto = D.id_gasto)'),
            'septiembre' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 9 AND id_gasto = D.id_gasto)'),
            'octubre' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 10 AND id_gasto = D.id_gasto)'),
            'noviembre' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 11 AND id_gasto = D.id_gasto)'),
            'diciembre' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 12 AND id_gasto = D.id_gasto)'),
            'total' => $fijos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago) BETWEEN 1 AND 12 AND id_gasto = D.id_gasto)')
        ])
            ->from('gasto as D')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_categoria = D.id_categoria',
                ]
            ])
            ->where(['D.id_condominio' => $id_condominio])
            ->where(['YEAR(D.fecha_pago)' => $time])
            ->where(['id_tipo =' => 1])
            ->group('D.id_categoria');
        $this->set('fijos', $fijos);


        /*-------------------------- Gastos Variables -------------------------------*/
        $gastosTable = TableRegistry::get('Gasto');
        $gastos = $gastosTable->find();
        $gastos->select([
            'G.id_condominio',
            'categoria' =>'c.categoria',
            'enero' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 1 AND id_gasto = G.id_gasto)'),
            'febrero' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 2 AND id_gasto = G.id_gasto)'),
            'marzo' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 3 AND id_gasto = G.id_gasto)'),
            'abril' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 4 AND id_gasto = G.id_gasto)'),
            'mayo' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 5 AND id_gasto = G.id_gasto)'),
            'junio' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 6 AND id_gasto = G.id_gasto)'),
            'julio' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 7 AND id_gasto = G.id_gasto)'),
            'agosto' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 8 AND id_gasto = G.id_gasto)'),
            'septiembre' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 9 AND id_gasto = G.id_gasto)'),
            'octubre' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 10 AND id_gasto = G.id_gasto)'),
            'noviembre' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 11 AND id_gasto = G.id_gasto)'),
            'diciembre' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 12 AND id_gasto = G.id_gasto)'),
            'total' => $gastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago) BETWEEN 1 AND 12 AND id_gasto = G.id_gasto)')
        ])
            ->from('gasto as G')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_categoria = G.id_categoria',
                ]
            ])
            ->where(['G.id_condominio' => $id_condominio])
            ->where(['YEAR(G.fecha_pago)' => $time])
            ->where(['id_tipo =' => 2])
            ->group('G.id_categoria');
        $this->set('gastos', $gastos);

        $gastos2Table = TableRegistry::get('Gasto');
        $gastosTotale = $gastos2Table->find();
        $gastosTotale->select([
            'A.id_condominio',
            'categoria' =>'c.categoria',
            'enero' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 1 AND id_gasto = A.id_gasto)'),
            'febrero' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 2 AND id_gasto = A.id_gasto)'),
            'marzo' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 3 AND id_gasto = A.id_gasto)'),
            'abril' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 4 AND id_gasto = A.id_gasto)'),
            'mayo' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 5 AND id_gasto = A.id_gasto)'),
            'junio' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 6 AND id_gasto = A.id_gasto)'),
            'julio' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 7 AND id_gasto = A.id_gasto)'),
            'agosto' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 8 AND id_gasto = A.id_gasto)'),
            'septiembre' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 9 AND id_gasto = A.id_gasto)'),
            'octubre' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 10 AND id_gasto = A.id_gasto)'),
            'noviembre' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 11 AND id_gasto = A.id_gasto)'),
            'diciembre' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 12 AND id_gasto = A.id_gasto)'),
            'total' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago) BETWEEN 1 AND 12 AND id_gasto = A.id_gasto)')
        ])
            ->from('gasto as A')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_categoria = A.id_categoria',
                ]
            ])
            ->where(['A.id_condominio' => $id_condominio])
            ->where(['YEAR(A.fecha_pago)' => $time])
            ->where(['id_tipo =' => 2])
            ->group('month(A.fecha_pago)');
        $gas = $gastosTotale->all();
        $this->set('gas', $gas);

        /*-------------------------- Total Gastos -------------------------------*/

        $gastosTotTable = TableRegistry::get('Gasto');
        $TotalGastos = $gastosTotTable->find();
        $TotalGastos->select([
            'F.id_condominio',
            'categoria' =>'c.categoria',
            'enero' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 1 AND id_gasto = F.id_gasto)'),
            'febrero' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 2 AND id_gasto = F.id_gasto)'),
            'marzo' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 3 AND id_gasto = F.id_gasto)'),
            'abril' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 4 AND id_gasto = F.id_gasto)'),
            'mayo' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 5 AND id_gasto = F.id_gasto)'),
            'junio' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 6 AND id_gasto = F.id_gasto)'),
            'julio' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 7 AND id_gasto = F.id_gasto)'),
            'agosto' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 8 AND id_gasto = F.id_gasto)'),
            'septiembre' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 9 AND id_gasto = F.id_gasto)'),
            'octubre' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 10 AND id_gasto = F.id_gasto)'),
            'noviembre' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 11 AND id_gasto = F.id_gasto)'),
            'diciembre' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago)= 12 AND id_gasto = F.id_gasto)'),
            'total' => $TotalGastos->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha_pago) BETWEEN 1 AND 12 AND id_gasto = F.id_gasto)')
        ])
            ->from('gasto as F')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_categoria = F.id_categoria',
                ]
            ])
            ->where(['F.id_condominio' => $id_condominio])
            ->where(['YEAR(F.fecha_pago)' => $time])
            ->group('month(F.fecha_pago)');
        $totGas = $TotalGastos->all();
        $this->set('totGas', $totGas);

        /*-------------------------- Saldo al cierre -------------------------------*/





        /*-------------------------- Pago -------------------------------*/
        $pagosTable = TableRegistry::get('Pago');
        $pagos = $pagosTable->find();
        $pagos->select([
            'P.id_condomino',
            'departamento' =>'c.numero_departamento',
            'enero' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 1 AND id_pago = P.id_pago)'),
            'febrero' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 2 AND id_pago = P.id_pago)'),
            'marzo' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 3 AND id_pago = P.id_pago)'),
            'abril' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 4 AND id_pago = P.id_pago)'),
            'mayo' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 5 AND id_pago = P.id_pago)'),
            'junio' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 6 AND id_pago = P.id_pago)'),
            'julio' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 7 AND id_pago = P.id_pago)'),
            'agosto' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 8 AND id_pago = P.id_pago)'),
            'septiembre' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 9 AND id_pago = P.id_pago)'),
            'octubre' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 10 AND id_pago = P.id_pago)'),
            'noviembre' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 11 AND id_pago = P.id_pago)'),
            'diciembre' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 12 AND id_pago = P.id_pago)'),
            'total' => $pagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 12 AND id_pago = P.id_pago)')
        ])
            ->from('pago as P')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = P.id_condomino',
                ]
            ])
            ->where(['P.id_condominio' => $id_condominio])
            ->where(['YEAR(P.fecha)' => $time])
            ->group('P.id_condomino');
        $this->set('pagos', $pagos);

        $pagos2Table = TableRegistry::get('Pago');
        $pagosTotales = $pagos2Table->find();
        $pagosTotales->select([
            'M.id_condomino',
            'departamento' =>'c.numero_departamento',
            'enero' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 1 AND id_pago = M.id_pago)'),
            'febrero' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 2 AND id_pago = M.id_pago)'),
            'marzo' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 3 AND id_pago = M.id_pago)'),
            'abril' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 4 AND id_pago = M.id_pago)'),
            'mayo' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 5 AND id_pago = M.id_pago)'),
            'junio' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 6 AND id_pago = M.id_pago)'),
            'julio' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 7 AND id_pago = M.id_pago)'),
            'agosto' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 8 AND id_pago = M.id_pago)'),
            'septiembre' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 9 AND id_pago = M.id_pago)'),
            'octubre' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 10 AND id_pago = M.id_pago)'),
            'noviembre' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 11 AND id_pago = M.id_pago)'),
            'diciembre' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 12 AND id_pago = M.id_pago)'),
            'total' => $pagosTotales->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 12 AND id_pago = M.id_pago)')
        ])
            ->from('pago as M')
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = M.id_condomino',
                ]
            ])
            ->where(['M.id_condominio' => $id_condominio])
            ->where(['YEAR(M.fecha)' => $time])
            ->group('month(fecha)');
        $pag = $pagosTotales->all();
        $this->set('pag', $pag);


        /*-------------------------- Adeudos -------------------------------*/

        /* $adeudosTable = TableRegistry::get('Adeudos');
         $adeudos = $adeudosTable->find();
         $now = Time::now();
         //$pagos = $this->Pago->find();
         $adeudos->select([
             'id_adeudo',
             'id_condomino',
             'id_condominio',
             'monto',
             'mes',
             'sum' => $adeudos->func()->sum('monto')
         ])
             ->where(['id_condominio' => $id_condominio])
             ->where(['YEAR(mes)' => $time])
             ->where(['MONTH(mes)' => 1])
             ->group('id_condomino');

         $adeudo = $adeudos->all();
         $this->set('adeudo', $adeudo); */

        $adeudosTable = TableRegistry::get('Adeudos');
        $adeudos = $adeudosTable->find();
        $adeudos->select([
            'T.id_condomino',
            'departamento' =>'c.numero_departamento',
            'saldo' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) BETWEEN 1 AND 12 and year(mes)= -1 AND id_adeudo = T.id_adeudo)'),
            'enero' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 1 AND id_adeudo = T.id_adeudo)'),
            'febrero' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 2 AND id_adeudo = T.id_adeudo)'),
            'marzo' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 3 AND id_adeudo = T.id_adeudo)'),
            'abril' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 4 AND id_adeudo = T.id_adeudo)'),
            'mayo' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 5 AND id_adeudo = T.id_adeudo)'),
            'junio' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 6 AND id_adeudo = T.id_adeudo)'),
            'julio' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 7 AND id_adeudo = T.id_adeudo)'),
            'agosto' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 8 AND id_adeudo = T.id_adeudo)'),
            'septiembre' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 9 AND id_adeudo = T.id_adeudo)'),
            'octubre' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 10 AND id_adeudo = T.id_adeudo)'),
            'noviembre' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 11 AND id_adeudo = T.id_adeudo)'),
            'diciembre' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 12 AND id_adeudo = T.id_adeudo)'),
            'total' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) BETWEEN 1 AND 12 AND id_adeudo = T.id_adeudo)')
        ])
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = T.id_condomino',
                ]
            ])
            ->where(['T.id_condominio' => $id_condominio])
            ->where(['YEAR(T.mes)' => $time])
            ->group('T.id_condomino');
        $this->set('adeudos', $adeudos);
    }


    //_______________________________________________________________ termina informe anual  ________________________________________-

    public function departamento($id_condominio = null, $fechaInicial = null, $fechaFinal = null, $depto = null){

        $this->set('fechaInicial', $fechaInicial);
        $this->set('fechaFinal', $fechaFinal);
        $this->set('depto', $depto);

        //-------------------------------------------------------------Numero del departamento________________________________________
        $this->loadModel('condomino');
        $condominosT = $this->condomino->find()
            ->select([
                'id_condomino'
            ])
            ->where([
                'id_condominio' => $id_condominio
            ])
            ->where([
                'numero_departamento' => $depto
            ]);
        $id_condomino = $condominosT->first();
        $this->set('id_condomino', $id_condomino);
        //Numero del departamento


        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);
        $this->set(compact('condominio'));

        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);
        $this->set(compact('condominio'));

        //___________>>> Cuotas fecha


        // Adeudos
        $adeudosTable = TableRegistry::get('Adeudos');
        $adeudos = $adeudosTable->find();
        $adeudos->select([
            'departamento' =>'c.numero_departamento',
            'concepto' => 'T.concepto',
            'enero' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 1 AND id_adeudo = T.id_adeudo)'),
            'febrero' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 2 AND id_adeudo = T.id_adeudo)'),
            'marzo' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 3 AND id_adeudo = T.id_adeudo)'),
            'abril' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 4 AND id_adeudo = T.id_adeudo)'),
            'mayo' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 5 AND id_adeudo = T.id_adeudo)'),
            'junio' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 6 AND id_adeudo = T.id_adeudo)'),
            'julio' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 7 AND id_adeudo = T.id_adeudo)'),
            'agosto' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 8 AND id_adeudo = T.id_adeudo)'),
            'septiembre' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 9 AND id_adeudo = T.id_adeudo)'),
            'octubre' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 10 AND id_adeudo = T.id_adeudo)'),
            'noviembre' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 11 AND id_adeudo = T.id_adeudo)'),
            'diciembre' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes)= 12 AND id_adeudo = T.id_adeudo)'),
            'total' => $adeudos->func()->sum('(SELECT monto FROM adeudos WHERE month(mes) BETWEEN 1 AND 12 AND id_adeudo = T.id_adeudo)')
        ])
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = T.id_condomino',
                ]
            ])
            ->where(['T.id_condominio' => $id_condominio])
            ->where(['c.numero_departamento' => $depto])
            ->where(['T.id_condomino' => $id_condomino->id_condomino])
            ->where(['fecha >=' => $fechaInicial])
            ->where(['fecha <=' => $fechaFinal])
            ->where(['T.estatus <=' => 1])
            ->group('departamento');
        $this->set('adeudos', $adeudos);

        // Pagos
        $pagosTable = TableRegistry::get('Pago');
        $adeudosPagos = $pagosTable->find();
        $adeudosPagos->select([
            'departamento' =>'j.id_condomino',
            'concepto' => 'j.concepto',
            'enero' => $adeudosPagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 1 AND id_pago = j.id_pago)'),
            'febrero' => $adeudosPagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 2 AND id_pago = j.id_pago)'),
            'marzo' => $adeudosPagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 3 AND id_pago = j.id_pago)'),
            'abril' => $adeudosPagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 4 AND id_pago = j.id_pago)'),
            'mayo' => $adeudosPagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 5 AND id_pago = j.id_pago)'),
            'junio' => $adeudosPagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 6 AND id_pago = j.id_pago)'),
            'julio' => $adeudosPagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 7 AND id_pago = j.id_pago)'),
            'agosto' => $adeudosPagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 8 AND id_pago = j.id_pago)'),
            'septiembre' => $adeudosPagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 9 AND id_pago = j.id_pago)'),
            'octubre' => $adeudosPagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 10 AND id_pago = j.id_pago)'),
            'noviembre' => $adeudosPagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 11 AND id_pago = j.id_pago)'),
            'diciembre' => $adeudosPagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 12 AND id_pago = j.id_pago)'),
            'total' => $adeudosPagos->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 12 AND id_pago = j.id_pago)')
        ])
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino',
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = j.id_condomino',
                ]
            ])
            ->from('pago as j')
            ->where(['j.id_condominio' => $id_condominio])
            ->where(['c.numero_departamento' => $depto])
            ->where(['j.id_condomino' => $id_condomino->id_condomino])
            ->where(['fecha >=' => $fechaInicial])
            ->where(['fecha <=' => $fechaFinal])
            ->group('departamento');
        $this->set('adeudosPagos', $adeudosPagos);

        // Saldo inicial para un solo departamento
        $saldoTable = TableRegistry::get('saldo_condomino');
        $saldoCondomino = $saldoTable->find();
        $saldoCondomino->select([
            'departamento' => 'm.id_condomino',
            'enero' => $saldoCondomino->func()->sum('(SELECT saldo FROM saldo_condomino WHERE month(fecha) = 1 AND id_saldo = m.id_saldo)'),
            'febrero' => $saldoCondomino->func()->sum('(SELECT saldo FROM saldo_condomino WHERE month(fecha) = 2 AND id_saldo = m.id_saldo)'),
            'marzo' => $saldoCondomino->func()->sum('(SELECT saldo FROM saldo_condomino WHERE month(fecha) = 3 AND id_saldo = m.id_saldo)'),
            'abril' => $saldoCondomino->func()->sum('(SELECT saldo FROM saldo_condomino WHERE month(fecha) = 4 AND id_saldo = m.id_saldo)'),
            'mayo' => $saldoCondomino->func()->sum('(SELECT saldo FROM saldo_condomino WHERE month(fecha) = 5 AND id_saldo = m.id_saldo)'),
            'junio' => $saldoCondomino->func()->sum('(SELECT saldo FROM saldo_condomino WHERE month(fecha) = 6 AND id_saldo = m.id_saldo)'),
            'julio' => $saldoCondomino->func()->sum('(SELECT saldo FROM saldo_condomino WHERE month(fecha) = 7 AND id_saldo = m.id_saldo)'),
            'agosto' => $saldoCondomino->func()->sum('(SELECT saldo FROM saldo_condomino WHERE month(fecha) = 8 AND id_saldo = m.id_saldo)'),
            'septiembre' => $saldoCondomino->func()->sum('(SELECT saldo FROM saldo_condomino WHERE month(fecha) = 9 AND id_saldo = m.id_saldo)'),
            'octubre' => $saldoCondomino->func()->sum('(SELECT saldo FROM saldo_condomino WHERE month(fecha) = 10 AND id_saldo = m.id_saldo)'),
            'noviembre' => $saldoCondomino->func()->sum('(SELECT saldo FROM saldo_condomino WHERE month(fecha) = 11 AND id_saldo = m.id_saldo)'),
            'diciembre' => $saldoCondomino->func()->sum('(SELECT saldo FROM saldo_condomino WHERE month(fecha) = 12 AND id_saldo = m.id_saldo)'),
            'total' => $saldoCondomino->func()->sum('(SELECT saldo FROM saldo_condomino WHERE month(fecha) BETWEEN 1 AND 12 AND id_saldo = m.id_saldo)')
        ])
            ->enableHydration(false)
            ->join([
                'd' => [
                    'table' => 'condomino',
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = m.id_condomino',
                ]
            ])
            ->from('saldo_condomino as m')
            ->where(['d.numero_departamento' => $depto])
            ->where(['fecha >=' => $fechaInicial])
            ->where(['fecha <=' => $fechaFinal])
            ->group('departamento');

        $this->set('saldoCondomino', $saldoCondomino);

    }



    public function cierre($id_condominio = null, $mes = null, $anio = null)
    {
        if($anio == null){
            $time = date("Y");
            $month = date("n");
            //$time->i18nFormat('yyyy');
        }else{
            $time = $anio;
            $month = $mes;
        }
        $this->set('time', $time);
        $this->set('month', $month);

        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);
        $this->set(compact('condominio'));


        $this->loadModel('condomino');
        $condominos = $this->condomino->find()
            ->select([
                'id_condomino' =>'c.id_condomino',
                'departamento' =>'c.numero_departamento',
            ])
            ->enableHydration(false)
            ->join([
                'c' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condomino = Condomino.id_condomino',
                ]
            ])
            ->where([
                'Condomino.id_condominio' => $id_condominio
            ]);
        $this->set('condominos', $condominos);

        /*+++++++++++**********************++++  Ingresos ********************************************/
        $cuotasTable = TableRegistry::get('Pago');
        $cuotasTotal = $cuotasTable->find();
        // Income for the selected period and condominium. Previously the period was compared as
        // an unquoted 'Y-m' value (2025-11 evaluates to 2014), the final column used a hardcoded
        // condominium 4, and an unused join with cuotas repeated every payment once per fee row.
        $periodo = 'month(fecha) = ' . (int)$month . ' AND year(fecha) = ' . (int)$time;
        $condominioId = (int)$id_condominio;
        $cuotaDelMes = '(SELECT cuota FROM cuotas WHERE ' . $periodo . ' AND id_tipo = 1 AND id_condominio = ' . $condominioId . ' ORDER BY id_cuotas DESC LIMIT 1)';
        $cuotasTotal->select([
            'P.id_condomino',
            'condomino'=>'C.numero_departamento',
            'cuota' => $cuotaDelMes,
            'descuento' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE ' . $periodo . ' AND P.id_tipo = 8 AND id_pago = P.id_pago)'),
            'pago' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE ' . $periodo . ' AND P.id_tipo = 1 AND id_pago = P.id_pago)'),
            'aclaracion' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE ' . $periodo . ' AND P.id_tipo = 9 AND id_pago = P.id_pago)'),
            'final' => '(' . $cuotaDelMes . '-(SUM((SELECT abono FROM pago WHERE ' . $periodo . ' AND P.id_tipo = 1 AND id_pago = P.id_pago))))'
        ])
            ->from('pago as P')
            ->enableHydration(false)
            ->join([
                'C' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'C.id_condomino = P.id_condomino',
                ]
            ])
            ->where(['C.id_condominio' => $id_condominio])
            ->where(['month(P.fecha)' => $month])
            ->where(['YEAR(P.fecha)' => $time])
            ->group('P.id_condomino');
        $cuotas = $cuotasTotal->all();
        $this->set('cuotas', $cuotas);


        /*+++++++++++**********************++++  Egresos ********************************************/

        /*+++++++++++*****  Gastos Fijos ***************/
        $gafijosTable = TableRegistry::get('Gasto');
        $fijos = $gafijosTable->find();
        $fijos->select([
            'G.id_condominio',
            'categoria' =>'C.categoria',
            'gasto'=>'G.gasto'
        ])
            ->distinct(['categoria'])
            ->from('gasto as G')
            ->enableHydration(false)
            ->join([
                'C' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'C.id_categoria = G.id_categoria',
                ]
            ])
            ->where(['G.id_condominio' => $id_condominio])
            ->where(['G.id_tipo' => 1])
            ->where(['YEAR(G.fecha)' => $time])
            ->where(['month(G.fecha)' => $month]);
        $this->set('fijos', $fijos);

        /*+++++++++++*****  Gastos Variables ***************/
        $gavarTable = TableRegistry::get('Gasto');
        $variables = $gavarTable->find();
        $variables->select([
            'V.id_condominio',
            'categoria' =>'C.categoria',
            'gasto'=>'V.gasto'
        ])
            ->from('gasto as V')
            ->enableHydration(false)
            ->join([
                'C' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'C.id_categoria = V.id_categoria',
                ]
            ])
            ->where(['V.id_condominio' => $id_condominio])
            ->where(['V.id_tipo' => 2])
            ->where(['YEAR(V.fecha)' => $time])
            ->where(['month(V.fecha)' => $month]);
        $this->set('variables', $variables);

        /*+++++++++++*****  Gastos Varios ***************/
        $variosTable = TableRegistry::get('Gasto');
        $varios = $variosTable->find();
        $varios->select([
            'V.id_condominio',
            'categoria' =>'C.categoria',
            'monto' => $varios->func()->sum('V.gasto')
        ])
            ->from('gasto as V')
            ->enableHydration(false)
            ->join([
                'C' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'C.id_categoria = V.id_categoria',
                ]
            ])
            ->where(['V.id_condominio' => $id_condominio])
            ->where(['V.id_tipo' => 4])
            ->where(['YEAR(V.fecha)' => $time])
            ->where(['month(V.fecha)' => $month]);
        $this->set('varios', $varios);

        /*+++++++++++*****  Gastos equipamiento ***************/
        $equipTable = TableRegistry::get('Gasto');
        $equip = $equipTable->find();
        $equip->select([
            'V.id_condominio',
            'categoria' =>'C.categoria',
            'monto' => $equip->func()->sum('V.gasto')
        ])
            ->from('gasto as V')
            ->enableHydration(false)
            ->join([
                'C' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'C.id_categoria = V.id_categoria',
                ]
            ])
            ->where(['V.id_condominio' => $id_condominio])
            ->where(['V.id_tipo' => 3])
            ->where(['YEAR(V.fecha)' => $time])
            ->where(['month(V.fecha)' => $month]);
        $this->set('equip', $equip);

        /*+++++++++++*****  Gastos Totales ***************/
        $gtotalesTable = TableRegistry::get('Gasto');
        $gt = $gtotalesTable->find();
        $gt->select([
            'monto' => $gt->func()->sum('V.gasto')
        ])
            ->from('gasto as V')
            ->enableHydration(false)
            ->join([
                'C' => [
                    'table' => 'categoria', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'C.id_categoria = V.id_categoria',
                ]
            ])
            ->where(['V.id_condominio' => $id_condominio])
            ->where(['YEAR(V.fecha)' => $time])
            ->where(['month(V.fecha)' => $month]);
        $this->set('gt', $gt);

        /*+++++++++++*****  Gastos Totales ***************/
        $ptotalesTable = TableRegistry::get('Pago');
        $pt = $ptotalesTable->find();
        $pt->select([
            'ing' => $gt->func()->sum('p.abono')
        ])
            ->from('pago as p')
            ->enableHydration(false)
            ->where(['p.id_condominio' => $id_condominio])
            ->where(['YEAR(p.fecha)' => $time])
            ->where(['month(p.fecha)' => $month])
            ->group('id_condominio');
        $this->set('pt', $pt);

        /*+++++++++++*****  Saldo inicial ***************/
        $saldoInicialTable = TableRegistry::get('saldo_condominio');
        $saldoInicial = $saldoInicialTable->find()
            ->where(['id_condominio' => $id_condominio])
            ->where(['YEAR(fecha)' => $time])
            ->where(['month(fecha)' => $month]);
        $siTotal = $saldoInicial->first();
        $this->set('siTotal', $siTotal);


        $saldoTable = TableRegistry::get('saldo_condominio');
        $saldo = $saldoTable->find();
        $saldo->select([
            'saldo' => $saldo->func()->sum('saldo')
        ])
            ->where(['id_condominio' => $id_condominio])
            ->where(['YEAR(fecha)' => $time])
            ->where(['month(fecha)' => $month]);
        $stotal = $saldo->first();
        $this->set('stotal', $stotal);

        /*+++++++++++***** ingresos bottom  ***************/
        $inTable = TableRegistry::get('pago');
        $in = $inTable->find();
        $in->select([
            'in' => $in->func()->sum('abono')
        ])
            ->where(['id_condominio' => $id_condominio])
            ->where(['YEAR(fecha)' => $time])
            ->where(['month(fecha)' => $month]);
        $intotal = $in->first();
        $this->set('intotal', $intotal);

        /*+++++++++++***** egresos bottom  ***************/
        $gaTable = TableRegistry::get('gasto');
        $ga = $gaTable->find();
        $ga->select([
            'ga' => $in->func()->sum('gasto')
        ])
            ->where(['id_condominio' => $id_condominio])
            ->where(['YEAR(fecha)' => $time])
            ->where(['month(fecha)' => $month]);
        $gatotal = $ga->first();
        $this->set('gatotal', $gatotal);

        /*+++++++++++***** egresos deposito transito  ***************/
        $dtTable = TableRegistry::get('gasto');
        $dt = $dtTable->find();
        $dt->select([
            'dt' => $dt->func()->sum('gasto')
        ])
            ->where(['id_condominio' => $id_condominio])
            ->where(['id_tipo' => 5])
            ->where(['YEAR(fecha)' => $time])
            ->where(['month(fecha)' => $month]);
        $dttotal = $dt->first();
        $this->set('dttotal', $dttotal);
        /*+++++++++++***** egresos cheque transito  ***************/
        $chtable = TableRegistry::get('gasto');
        $cht = $chtable->find();
        $cht->select([
            'cht' => $cht->func()->sum('gasto')
        ])
            ->where(['id_condominio' => $id_condominio])
            ->where(['id_tipo' => 6])
            ->where(['YEAR(fecha)' => $time])
            ->where(['month(fecha)' => $month]);
        $chttotal = $cht->first();
        $this->set('chttotal', $chttotal);

    }

    public function recibos($id = null){

        // Receipts of a receipt batch (recibos_pago): every payment of that condominium made in the
        // batch month, each with the resident who paid it. The previous version compared PHP
        // strings in where() (always false, so no period filter), joined every resident of the
        // condominium to every payment, and failed when there were no payments.
        $this->loadModel('Condominio');
        $condominio = $this->Condominio
            ->find()
            ->select([
                'id_condominio',
                'nombre_condominio',
                'logo',
                'direccion_condominio',
                'rfc',
                'id'=>'c.id_reporte',
                'condo'=>'d.id_condomino',
                'departamento'=>'d.numero_departamento',
                'residente'=>'d.residente_condominio',
                'rf'=>'d.rfc',
                'pago'=>'e.abono',
                'monto'=>'e.monto_letra',
                'concepto'=>'e.concepto',
                'fecha'=>'e.fecha',
                'folio'=>'e.folio',
            ])
            ->hydrate(false)     // pasarlo como un arreglo
            ->join([
                'c' => [
                    'table' => 'recibos_pago', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'c.id_condominio = Condominio.id_condominio',
                ]
            ])
            ->join([
                'e' => [
                    'table' => 'pago', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => [
                        'e.id_condominio = c.id_condominio',
                        'month(e.fecha) = month(c.fecha)',
                        'year(e.fecha) = year(c.fecha)',
                    ],
                ]
            ])
            ->join([
                'd' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = e.id_condomino',
                ]
            ])
            ->where(['c.id_reporte' => $id])
            ->order(['e.folio' => 'ASC']);

        $row = $condominio->all();
        $this->set('condominio', $row);


    }

    public function nuevo($id_condominio = null)
    {
        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);
        $this->set('id_condominio', $id_condominio);

        $this->loadModel('RecibosPago');
        $recibosPago = $this->RecibosPago->newEntity();
        if ($this->request->is('post')) {
            $recibosPago = $this->RecibosPago->patchEntity($recibosPago, $this->request->getData());
            if ($this->RecibosPago->save($recibosPago)) {
                $this->Flash->success(__('The recibos pago has been saved.'));

                return $this->redirect(['action' => 'recibos']);
            }
            $this->Flash->error(__('The recibos pago could not be saved. Please, try again.'));
        }
        $this->set(compact('recibosPago', 'condominio'));

    }

    public function lista($id_condominio = null){

        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);
        $this->set('id_condominio', $id_condominio);
        $this->loadModel('RecibosPago');
        $recibosPago = $this->paginate($this->RecibosPago);

        $this->set(compact('recibosPago','condominio'));
    }

    public function delete($id = null)
    {
        $this->loadModel('RecibosPago');
        $this->request->allowMethod(['post', 'delete']);
        $recibosPago = $this->RecibosPago->get($id);
        if ($this->RecibosPago->delete($recibosPago)) {
            $this->Flash->success(__('The recibos pago has been deleted.'));
        } else {
            $this->Flash->error(__('The recibos pago could not be deleted. Please, try again.'));
        }

        return $this->redirect($this->referer());
    }

    public function prim($id_condominio = null)
    {
        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio, [
            'contain' => []
        ]);
        $this->set(compact('condominio'));

    }

    /*   -----------------  Calcula saldo final            -------------          */
    public function primSave($id_condominio = null)
    {
        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio, [
            'contain' => []
        ]);
        $this->set(compact('condominio'));

        $saldoCierreTable = TableRegistry::get('saldo_cierre');

        $registroReciente = $saldoCierreTable->find()
            ->where(['id_condominio' => $id_condominio])
            ->order(['fecha' => 'DESC'])
            ->first();

        $this->set('registroReciente', $registroReciente);

        if ($registroReciente) {
            $saldo = $registroReciente->saldo;

            $fechaSiguienteMes = (new \DateTime($registroReciente->fecha))->modify('first day of next month');

            $saldoCondTable = TableRegistry::get('saldo_condominio');

            $registroExistente = $saldoCondTable->find()
                ->where(['id_condominio' => $id_condominio, 'fecha' => $fechaSiguienteMes->format('Y-m-d')])
                ->first();

            if ($registroExistente) {
                $registroExistente->saldo = $saldo;
                if ($saldoCondTable->save($registroExistente)) {
                    $this->Flash->success(__('El saldo ha sido actualizado correctamente.'));
                } else {
                    $this->Flash->error(__('Error al actualizar el saldo. Por favor, inténtelo de nuevo.'));
                }
            } else {
                $nuevoRegistro = $saldoCondTable->newEntity();
                $nuevoRegistro->id_condominio = $id_condominio;
                $nuevoRegistro->saldo = $saldo;
                $nuevoRegistro->fecha = $fechaSiguienteMes->format('Y-m-d');

                if ($saldoCondTable->save($nuevoRegistro)) {
                    $this->Flash->success(__('El saldo ha sido guardado correctamente.'));
                } else {
                    $this->Flash->error(__('Error al guardar el saldo. Por favor, inténtelo de nuevo.'));
                }
            }
        } else {
            $this->Flash->error(__('No se encontró ningún saldo reciente para el condominio.'));
        }

        return $this->redirect(['action' => 'informe', $id_condominio]);
    }


    public function seg($id_condominio = null)
    {
        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio, [
            'contain' => []
        ]);
        $this->set(compact('condominio'));

    }

    public function segSave($id_condominio = null)
    {
        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio, [
            'contain' => []
        ]);
        $this->set(compact('condominio'));

        $saldoCierreTable = TableRegistry::get('saldo_cierre');

        $registroReciente = $saldoCierreTable->find()
            ->where(['id_condominio' => $id_condominio])
            ->order(['fecha' => 'DESC'])
            ->first();

        $this->set('registroReciente', $registroReciente);

        if ($registroReciente) {
            $saldo = $registroReciente->saldo;

            $fechaSiguienteMes = (new \DateTime($registroReciente->fecha))->modify('first day of next month');

            $saldoCondTable = TableRegistry::get('saldo_condominio');

            $registroExistente = $saldoCondTable->find()
                ->where(['id_condominio' => $id_condominio, 'fecha' => $fechaSiguienteMes->format('Y-m-d')])
                ->first();

            if ($registroExistente) {
                $registroExistente->saldo = $saldo;
                if ($saldoCondTable->save($registroExistente)) {
                    $this->Flash->success(__('El saldo ha sido actualizado correctamente.'));
                } else {
                    $this->Flash->error(__('Error al actualizar el saldo. Por favor, inténtelo de nuevo.'));
                }
            } else {
                $nuevoRegistro = $saldoCondTable->newEntity();
                $nuevoRegistro->id_condominio = $id_condominio;
                $nuevoRegistro->saldo = $saldo;
                $nuevoRegistro->fecha = $fechaSiguienteMes->format('Y-m-d');

                if ($saldoCondTable->save($nuevoRegistro)) {
                    $this->Flash->success(__('El saldo ha sido guardado correctamente.'));
                } else {
                    $this->Flash->error(__('Error al guardar el saldo. Por favor, inténtelo de nuevo.'));
                }
            }
        } else {
            $this->Flash->error(__('No se encontró ningún saldo reciente para el condominio.'));
        }

        return $this->redirect(['action' => 'informe_seg', $id_condominio]);
    }


}
