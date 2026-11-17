<?php
namespace App\Controller;
use Cake\Controller\Component;
use Cake\Mailer\Email;
use Cake\ORM\TableRegistry;


class EmailController extends AppController {
    public function index()
    {

    }

//use email component
    public $components = array('Email');

    function send() {
//array of values for using in template file

        $emailValues = array('name' => 'MyName', 'phone' => 'MyPhone');
        $this->set('emailValues', $emailValues); //pass to template
        $this->Email->reset(); //To clear previous messages
        $this->Email->to = 'gnardant@gmail.com'; //receiver's email
        $this->Email->subject = 'Recibo de Pago'; // set title for email subject
        $this->Email->replyTo = 'dante@masbienweb.com.mx'; //email address fr replyTo
        $this->Email->from = 'pagosalpa@gmail.com'; //sender's email
        $this->Email->template = 'template_name'; //email template
        $this->Email->sendAs = 'html'; // email type
        /*         * ***smtp settings********** */
        $this->Email->smtpOptions = array(
            'port' => '465',
            'timeout' => '30',
            'host' => 'ssl://smtp.gmail.com',
            'username' => 'pagosalpa@gmail.com',
            'password' => \Cake\Mailer\TransportFactory::getConfig('gmail')['password'],
        );
        $this->Email->delivery = 'smtp';
        if ($this->Email->send()) {
            return true;
        } else {
            echo $this->Email->smtpError;
        }
    }


    public function informeSeg($id_condominio = null){
        $now = date("Y");

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
        /*-------------------------- Saldo Inicial -------------------------------*/

        $saldoTable = TableRegistry::get('saldo_condominio');
        $saldoTotal = $saldoTable->find();
        $saldoTotal->select([
            'I.id_saldo',
            'I.id_condominio',
            'enero' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 7 AND id_saldo = I.id_saldo)'),
            'febrero' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 8 AND id_saldo = I.id_saldo)'),
            'marzo' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 9 AND id_saldo = I.id_saldo)'),
            'abril' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 10 AND id_saldo = I.id_saldo)'),
            'mayo' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 11 AND id_saldo = I.id_saldo)'),
            'junio' => $saldoTotal->func()->sum('(SELECT saldo FROM saldo_condominio WHERE month(fecha)= 12 AND id_saldo = I.id_saldo)')
        ])
            ->from('saldo_condominio as I')
            ->where(['I.id_condominio' => $id_condominio])
            ->where(['YEAR(I.fecha)' => date("Y")])
            ->group('I.id_condominio');
        $saldo = $saldoTotal->all();
        $this->set('saldo', $saldo);

        /*-------------------------- Ingresos por Cuotas -------------------------------*/

        $cuotasTable = TableRegistry::get('Pago');
        $cuotasTotal = $cuotasTable->find();
        $cuotasTotal->select([
            'J.id_condominio',
            'tipo'=>'tp.nombre',
            'enero' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 7 AND id_pago = J.id_pago)'),
            'febrero' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 8 AND id_pago = J.id_pago)'),
            'marzo' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 9 AND id_pago = J.id_pago)'),
            'abril' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 10 AND id_pago = J.id_pago)'),
            'mayo' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 11 AND id_pago = J.id_pago)'),
            'junio' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha)= 12 AND id_pago = J.id_pago)'),
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
            ->where(['YEAR(J.fecha)' => date("Y")])
            ->group('tp.id_tipo');
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
            'total' => $sacmexTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 6 AND id_pago = K.id_pago)')
        ])
            ->from('pago as K')
            ->where(['K.id_condominio' => $id_condominio])
            ->where(['YEAR(K.fecha)' => date("Y")])
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
            'total' => $itTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 6 AND id_pago = J.id_pago)')
        ])
            ->from('pago as J')
            ->where(['J.id_condominio' => $id_condominio])
            ->where(['YEAR(J.fecha)' => date("Y")])
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
            ->where(['YEAR(D.fecha)' => date("Y")])
            ->where(['id_tipo =' => 1])
            ->group('D.id_categoria');
        $this->set('fijos', $fijos);

        //___________________________________________________________  Total gastos fijos _____________________________________________//

        $gasfijos2Table = TableRegistry::get('Gasto');
        $fijosTotale = $gasfijos2Table->find();
        $fijosTotale->select([
            'A.id_condominio',
            'categoria' =>'c.categoria',
            'enero' => $fijosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = A.id_gasto)'),
            'febrero' => $fijosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = A.id_gasto)'),
            'marzo' => $fijosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = A.id_gasto)'),
            'abril' => $fijosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = A.id_gasto)'),
            'mayo' => $fijosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = A.id_gasto)'),
            'junio' => $fijosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = A.id_gasto)'),
            'total' => $fijosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = A.id_gasto)')
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
            ->where(['YEAR(A.fecha)' => date("Y")])
            ->where(['id_tipo =' => 1])
            ->group('month(A.fecha)');
        $fij = $fijosTotale->all();
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
            ->where(['YEAR(G.fecha)' => date("Y")])
            ->where(['id_tipo =' => 2])
            ->group('G.id_categoria');
        $this->set('gastos', $gastos);

        $gastos2Table = TableRegistry::get('Gasto');
        $gastosTotale = $gastos2Table->find();
        $gastosTotale->select([
            'A.id_condominio',
            'categoria' =>'c.categoria',
            'enero' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = A.id_gasto)'),
            'febrero' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = A.id_gasto)'),
            'marzo' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = A.id_gasto)'),
            'abril' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = A.id_gasto)'),
            'mayo' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = A.id_gasto)'),
            'junio' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = A.id_gasto)'),
            'total' => $gastosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = A.id_gasto)')
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
            ->where(['YEAR(A.fecha)' => date("Y")])
            ->where(['id_tipo =' => 2])
            ->group('month(A.fecha)');
        $gas = $gastosTotale->all();
        $this->set('gas', $gas);



        //_____________________________________________ Total gastos variables ________________________________________________________________//

        $gasVariables2Table = TableRegistry::get('Gasto');
        $variablesTotale = $gasVariables2Table->find();
        $variablesTotale->select([
            'A.id_condominio',
            'categoria' =>'c.categoria',
            'enero' => $fijosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 1 AND id_gasto = A.id_gasto)'),
            'febrero' => $fijosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 2 AND id_gasto = A.id_gasto)'),
            'marzo' => $fijosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 3 AND id_gasto = A.id_gasto)'),
            'abril' => $fijosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 4 AND id_gasto = A.id_gasto)'),
            'mayo' => $fijosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 5 AND id_gasto = A.id_gasto)'),
            'junio' => $fijosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha)= 6 AND id_gasto = A.id_gasto)'),
            'total' => $fijosTotale->func()->sum('(SELECT gasto FROM gasto WHERE month(fecha) BETWEEN 1 AND 6 AND id_gasto = A.id_gasto)')
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
            ->where(['YEAR(A.fecha)' => date("Y")])
            ->where(['id_tipo =' => 2])
            ->group('month(A.fecha)');
        $varia = $variablesTotale->all();
        $this->set('varia', $varia);
        /*------------------------------------------------------- Gastos caja chica -----------------------------------------------------------------*/


        $cajaTable = TableRegistry::get('Conceptos');
        $caja = $cajaTable->find();
        $caja->select([
            'N.id_condominio',
            'N.concept',
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
            ->where(['YEAR(N.fecha)' => date("Y")])
            ->group(['N.id_condominio']);
        $this->set('caja', $caja);

        /*------------------------------------------------------- Total Gastos -----------------------------------------------------------------*/

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
            ->where(['YEAR(F.fecha)' => date("Y")])
            ->group('month(F.fecha)');
        $totGas  = $TotalGastos->all();
        $this->set('totGas', $totGas);

        /*-------------------------- Saldo al cierre -------------------------------*/

        /*Arreglar query con mysql
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
            'total' => $sacmexTotal->func()->sum('(SELECT abono FROM pago WHERE month(fecha) BETWEEN 1 AND 6 AND id_pago = K.id_pago)')
        ])
            ->from('pago as K')
            ->where(['K.id_condominio' => $id_condominio])
            ->where(['YEAR(K.fecha)' => date("Y")])
            ->where(['id_tipo' => 7])
            ->group('K.id_condominio');
        $sacmex = $sacmexTotal->all();
        $this->set('sacmex', $sacmex);

        */

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
            ->where(['YEAR(P.fecha)' => date("Y")])
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
            ->where(['YEAR(M.fecha)' => date("Y")])
            ->group('M.id_condominio');
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
             ->where(['YEAR(mes)' => date("Y")])
             ->where(['MONTH(mes)' => 1])
             ->group('id_condomino');

         $adeudo = $adeudos->all();
         $this->set('adeudo', $adeudo); */

        $adeudosTable = TableRegistry::get('Adeudos');
        $adeudos = $adeudosTable->find();
        $adeudos->select([
            'T.id_condomino',
            'departamento' =>'c.numero_departamento',
            // Same definition as ReportesController::informeSeg(): the resident's opening balance
            // (saldo_condomino.contra) registered between January and June.
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
                    'table' => 'condomino', // nombre de tabla en MySQL
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
                    ]
                ]
            ])
            ->where(['T.id_condominio' => $id_condominio])
            ->where(['YEAR(T.mes)' => date("Y")])
            ->group('T.id_condomino');
        $this->set('adeudos', $adeudos);

    }

    public function departamento($id_condominio = null){
        $now = date("Y");

        $this->loadModel('condomino');
        $condominosT = $this->condomino->find()
            ->select([
                'id_condomino'
            ])
            ->where([
                'id_condominio' => $id_condominio
            ]);
        $this->set('condominosT', $condominosT);

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
    }
    public function cierre($id_condominio = null)
    {
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
        $cuotasTotal->select([
            'P.id_condomino',
            'condomino'=>'C.numero_departamento',
            'cuota' => ('(SELECT cuota FROM cuotas WHERE fecha='.date("Y-m").' AND id_tipo = 1 AND id_condominio = '.$id_condominio.' AND id_cuotas = id_cuotas group by cuota)'),
            'descuento' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE fecha='.date("Y-m").'  AND P.id_tipo = 8 AND id_pago = P.id_pago)'),
            'pago' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE fecha= '.date("Y-m").' AND P.id_tipo = 1 AND id_pago = P.id_pago)'),
            'aclaracion' => $cuotasTotal->func()->sum('(SELECT abono FROM pago WHERE fecha= '.date("Y-m").' AND P.id_tipo = 9 AND id_pago = P.id_pago)'),
            'final' => ('((SELECT cuota FROM cuotas WHERE fecha= '.date("Y-m").' AND id_tipo = 1 AND id_condominio = 4 AND id_cuotas = id_cuotas group by cuota)-(SUM((SELECT abono FROM pago WHERE fecha = '.date("Y-m").' AND P.id_tipo = 1 AND id_pago = P.id_pago))))')
        ])
            ->from('pago as P')
            ->enableHydration(false)
            ->join([
                'C' => [
                    'table' => 'condomino', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'C.id_condomino = P.id_condomino',
                ]
            ])->join([
                'T' => [
                    'table' => 'cuotas', // nombre de tabla en MySQL
                    'type' => 'INNER',
                    'conditions' => 'T.id_condominio = C.id_condominio',
                ]
            ])
            ->where(['C.id_condominio' => $id_condominio])
            ->where(['(P.fecha)' => date("Y-m")])
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
            'monto' => $fijos->func()->sum('G.gasto')
        ])
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
            ->where(['YEAR(G.fecha)' => date("Y")])
            ->where(['month(G.fecha)' => date("m")])
            ->group('G.id_categoria');
        $this->set('fijos', $fijos);

        /*+++++++++++*****  Gastos Variables ***************/
        $gavarTable = TableRegistry::get('Gasto');
        $variables = $gavarTable->find();
        $variables->select([
            'V.id_condominio',
            'categoria' =>'C.categoria',
            'monto' => $fijos->func()->sum('V.gasto')
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
            ->where(['YEAR(V.fecha)' => date("Y")])
            ->where(['month(V.fecha)' => date("m")])
            ->group('V.id_categoria');
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
            ->where(['YEAR(V.fecha)' => date("Y")])
            ->where(['month(V.fecha)' => date("m")])
            ->group('V.id_categoria');
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
            ->where(['YEAR(V.fecha)' => date("Y")])
            ->where(['month(V.fecha)' => date("m")])
            ->group('V.id_categoria');
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
            ->where(['YEAR(V.fecha)' => date("Y")])
            ->where(['month(V.fecha)' => date("m")])
            ->group('id_condominio');
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
            ->where(['YEAR(p.fecha)' => date("Y")])
            ->where(['month(p.fecha)' => date("m")])
            ->group('id_condominio');
        $this->set('pt', $pt);

        /*+++++++++++*****  Saldo inicial ***************/
        $saldoTable = TableRegistry::get('saldo_condomino');
        $saldo = $saldoTable->find();
        $saldo->select([
            'saldo' => $saldo->func()->sum('saldo')
        ])
            ->where(['id_condominio' => $id_condominio])
            ->where(['YEAR(fecha)' => date("Y")])
            ->where(['month(fecha)' => date("m")]);
        $stotal = $saldo->first();
        $this->set('stotal', $stotal);

        /*+++++++++++***** ingresos bottom  ***************/
        $inTable = TableRegistry::get('pago');
        $in = $inTable->find();
        $in->select([
            'in' => $in->func()->sum('abono')
        ])
            ->where(['id_condominio' => $id_condominio])
            ->where(['YEAR(fecha)' => date("Y")])
            ->where(['month(fecha)' => date("m")]);
        $intotal = $in->first();
        $this->set('intotal', $intotal);

        /*+++++++++++***** egresos bottom  ***************/
        $gaTable = TableRegistry::get('gasto');
        $ga = $gaTable->find();
        $ga->select([
            'ga' => $in->func()->sum('gasto')
        ])
            ->where(['id_condominio' => $id_condominio])
            ->where(['YEAR(fecha)' => date("Y")])
            ->where(['month(fecha)' => date("m")]);
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
            ->where(['YEAR(fecha)' => date("Y")])
            ->where(['month(fecha)' => date("m")]);
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
            ->where(['YEAR(fecha)' => date("Y")])
            ->where(['month(fecha)' => date("m")]);
        $chttotal = $cht->first();
        $this->set('chttotal', $chttotal);

    }

}
