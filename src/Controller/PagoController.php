<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use App\Model\Table\Pago;
use App\Model\Table\Favor;
use Cake\Log\Log;
use NumerosEnLetras;
use Cake\Http\ServerRequest;
use Setasign\Fpdf;
use App\Util\PDF;
//use Cake\Network\Email\Email;
use Cake\Filesystem\File;
use Cake\Datasource\ConnectionManager;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Helper;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Cake\Http\CallbackStream;
use Cake\Event\Event;
use Robotusers\Excel\Registry;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use Robotusers\Excel\Excel\Manager;
use function Sodium\add;
use function Sodium\increment;
use Cake\Mailer\Email;


/**
 * Pago Controller
 *
 * @property \App\Model\Table\PagoTable $Pago
 *
 * @method \App\Model\Entity\Pago[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class PagoController extends AppController
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
     * @return \Cake\Http\Response|void
     */
    public function index($id_condominio = null)
    {
        $query = $this->Pago
            ->find()
            ->select([
                'id_pago',
                'condominio' => 'c.nombre_condominio',
                'condomino' => 'd.numero_departamento',
                'abono',
                'bonificacion',
                'monto_letra',
                'concepto',
                'referencia',
                'fecha_pago',
                'fecha'
            ])
            ->hydrate(false)
            ->join([
                'c' => [
                    'table' => 'condominio',
                    'type' => 'INNER',
                    'conditions' => 'c.id_condominio = Pago.id_condominio',
                ]
            ])
            ->join([
                'd' => [
                    'table' => 'condomino',
                    'type' => 'INNER',
                    'conditions' => 'd.id_condomino = Pago.id_condomino',
                ]
            ])
            ->where([
            'c.id_condominio' => $id_condominio
            ]);
     //   $pago = $this->paginate($query);
     //   $this->set(compact('pago'));
     //   $pago = $this->paginate($query);
     //   debug($query);
        $this->set('pago', $this->paginate($query));
        $this->set('_serialize', ['pago']);
        $this->set('id_condominio', $id_condominio);

        //Excel
        // Create a new spreadsheet
        $spreadsheet = new Spreadsheet();
        // Add value in a sheet inside of that spreadsheet.
        // // (It's possible to have multiple sheets in a single spreadsheet)
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Hello World !');
        // Save the spreadsheet in the webroot folder
        $writer = new Xlsx($spreadsheet);
        $writer->save('test.xlsx');



    }

    /**
     * View method
     *
     * @param string|null $id Pago id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $pago = $this->Pago->get($id, [
            'contain' => []
        ]);

        $this->set('pago', $pago);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
   public function add($id_condominio = null)
    {
        $pago = $this->Pago->newEntity();

        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->get($id_condominio,[
            'contain'=>[]
        ]);

        $query = TableRegistry::get('Bancaria')->find()
                                               ->select(['no_cuenta', 'nombre_banco'])
                                               ->where(['estatus'=>'activo'])
                                               ->where(['id_condominio'=>$id_condominio]);

        $this->set('query', $query);
        $row = $query->first();

        $this->set('cuenta', $row);
        $this->set('_serialize', ['cuenta']);


        // $condomino = $condominoTable->find()->where(['id_condomino' => $pago->id_condomino])->first();
        $folioTable = TableRegistry::get('Folios');
        $folio = $folioTable->find()->where(['id_condominio' => $id_condominio])->first();

        $this->loadModel('condominio');
        $optionsCondominio = $this->condominio->find('list', ['keyField' => 'id_condominio',
            'valueField' => 'nombre_condominio']);

        $this->loadModel('condomino');
        $optionsCondomino = $this->condomino->find('list', [
            'keyField' => 'id_condomino',
            'valueField' => 'numero_departamento',
            'order' => [\App\Database\SqlDialect::forConnection()->leadingInteger('condomino.numero_departamento') => 'ASC']]);


        $this->loadModel('tipo_pago'); //if it's not already loaded
        $optionsTipo = $this->tipo_pago->find('list', ['keyField' => 'id_tipo',
            'valueField' => 'nombre']);

        $this->loadModel('bancaria');
        $optionsBanco = $this->bancaria->find('list', ['keyField' => 'nombre_banco',
            'valueField' => 'nombre_banco'])
                ->where(['id_condominio'=>$id_condominio]);

        $this->loadModel('bancaria');
        $optionsCuenta = $this->bancaria->find('list', ['keyField' => 'no_cuenta',
            'valueField' => 'no_cuenta'])
            ->where(['id_condominio'=>$id_condominio]);

        $this->set('optionsCondominio', $optionsCondominio);
        $this->set('optionsCondomino', $optionsCondomino);
        $this->set('optionsTipo', $optionsTipo);
        $this->set('optionsBanco', $optionsBanco);
        $this->set('optionsCuenta', $optionsCuenta);

        if ($this->request->is('post')) {
            $pago = $this->Pago->patchEntity($pago, $this->request->getData());
            // Incrementa el folio
            $serie = $folio->serie + $folio->incremento;
            $pago->folio = $serie;
            if ($this->Pago->save($pago)) {
                $this->saveBanco($pago);
                $this->saveCierre($pago);
                $this->cierreCondomino($pago);
                $this->actualizaSaldo($pago);
                $folio->serie = $serie;
                $folioTable->save($folio);

                if(!empty($pago->abono) && $pago->abono != 0){
                    $this->createPdf($pago);
                    $this->sendEmail($pago);
                }

                $this->Flash->success(__('The pago has been saved.'));
            }
            $this->Flash->error(__('The pago could not be saved. Please, try again.'));
        }
        $this->set(compact('pago','condominio'));
    }

       public function pdf($id = null)
    {
        $pago = $this->Pago->get($id, [
            'contain' => []
        ]);
        //$this->createQrCode();
        $this->createPdf($pago);
    }

    private function createQrCode(){
        $text = 'https://kualitet.mx/';
       // $qrData = file_get_contents('https://api.qrserver.com/v1/create-qr-code/?data=' . $text. '&size=150x150');
        $qrData = $this->file_get_contents_curl('https://api.qrserver.com/v1/create-qr-code/?data=' . $text. '&size=150x150');
        $file = new File( WWW_ROOT. 'img/qrcliente.png', true);
        $file->write($qrData);
    }


    private function file_get_contents_curl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    protected function createPdf($pago)
    {

        ob_start();

        $condominoTable = TableRegistry::get('Condomino');
        $condomino = $condominoTable->find()->where(['id_condomino' => $pago->id_condomino])->first();

        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->find()->where(['id_condominio' => $pago->id_condominio])->first();

        $pdf = new PDF('L','mm',array(139.7,215.9));

        //$pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',12);
        $pdf->SetTextColor(255,40,0);
        $pdf->Cell(40,10,'Folio: ' .  $pago->id_pago,0,1,'R',false);
        $pdf->Ln(10);

        $pdf->SetFontSize(40);
        $pdf->SetTextColor(255,40,0);
        $pdf->RotatedText(70,70,'PAGADO',45);
        //$pdf->TextWithDirection(110,50,'PAGADO','L');
        $pdf->SetTextColor(0,0,0);
        // $pdf->Cell(0,10);
        //$pdf->SetLeftMargin(30);
        $pdf->SetFont('Arial','',16);
        $pdf->Cell(40,10,'Condominio: ' .  utf8_decode($condominio->nombre_condominio),0,1,'L',false);
        $pdf->Cell(40,10,'Casa: ' . $condomino->numero_departamento . '   ' .  utf8_decode($condomino->residente_condominio),0,1,'L',false);
        $pdf->Cell(40,10,'' . utf8_decode($condomino->rfc),0,1,'L',false);
        //$pdf->Cell(40,10,'Departamento: ' . $condomino->numero_departamento,0,1,'L',false);
        $pdf->Cell(40,10,'Cantidad: $' . number_format($pago->abono, 2) . ' (' .utf8_decode($pago->monto_letra) . ')',0,1,'L',false);
        $pdf->Cell(40,10,'Por concepto de: ' . utf8_decode($pago->concepto),0,1,'L',false);
        $pdf->Image(WWW_ROOT  . 'img/qrcliente.png',58,80,30);
        $pdf->Ln(7);
        $pdf->Cell(110,10,utf8_decode('La Administración '),0,0,'L',false);
        $pdf->Cell(40,10,'Fecha de pago: ' .  $pago->fecha,0,0,'R',false);

        $pdf->Output("I",WWW_ROOT. "recibo.pdf",true);
        ob_end_flush();

    }

    public function createFilePdf($pago)
    {
        ob_start();
        $condominoTable = TableRegistry::get('Condomino');
        $condomino = $condominoTable->find()->where(['id_condomino' => $pago->id_condomino])->first();
        $condominioTable = TableRegistry::get('Condominio');
        $condominio = $condominioTable->find()->where(['id_condominio' => $pago->id_condominio])->first();

        $pdf = new PDF('L','mm',array(139.7,215.9));

        //$pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',12);
        $pdf->SetTextColor(255,40,0);
        $pdf->Cell(40,10,'Folio: ' .  $pago->id_pago,0,1,'R',false);
        $pdf->Ln(10);

        $pdf->SetFontSize(40);
        $pdf->SetTextColor(255,40,0);
        $pdf->RotatedText(70,70,'PAGADO',45);
        //$pdf->TextWithDirection(110,50,'PAGADO','L');
        $pdf->SetTextColor(0,0,0);
       // $pdf->Cell(0,10);
        //$pdf->SetLeftMargin(30);
        $pdf->SetFont('Arial','',16);
        $pdf->Cell(40,10,'Condominio: ' .  utf8_decode($condominio->nombre_condominio),0,1,'L',false);
        $pdf->Cell(40,10,'Casa: ' . $condomino->numero_departamento . '   ' .  utf8_decode($condomino->residente_condominio),0,1,'L',false);
        //$pdf->Cell(40,10,'Departamento: ' . $condomino->numero_departamento,0,1,'L',false);
        $pdf->Cell(40,10,'Cantidad: $' . number_format($pago->abono, 2) . ' (' .utf8_decode($pago->monto_letra) . ')',0,1,'L',false);
        $pdf->Cell(40,10,'Por concepto de: ' . utf8_decode($pago->concepto),0,1,'L',false);
        $pdf->Image(WWW_ROOT  . 'img/qrcliente.png',60,70,30);
        $pdf->Ln(7);
        $pdf->Cell(110,10,utf8_decode('La Administración '),0,0,'L',false);
        $pdf->Cell(40,10,'Fecha de pago: ' .  $pago->fecha,0,0,'R',false);
        $pdf->Output("F",WWW_ROOT. "recibo.pdf",true);
        ob_end_flush();

    }

    public function sendEmail($pago){
        $condominoTable = TableRegistry::get('Condomino');
        $condomino = $condominoTable->find()->where(['id_condomino' => $pago->id_condomino])->first();
        $email = new Email();
        $email->setTransport('alpa')
            ->setEmailFormat('html')
            ->setTo($condomino->correo_contacto)
            ->setCc('recibos@alpa-condominios.com')
            ->setCc('bitacora@alpaadministraciones.com')
            ->setFrom('recibos@kualitet.mx');
        $this->createQrCode();
        $this->createFilePdf($pago);
        $email->setAttachments([
            'recibo.pdf' => [
                'file' => WWW_ROOT. 'recibo.pdf',
                'mimetype' => 'application/pdf'
            ]
        ]);
        $message = '<h2> Estimado '  . $condomino->residente_condominio . '</h2>'
            .  '<br><p>Nos permitimos enviarle en archivo adjunto su recibo. </p>'
            . '<br><p>En caso de faltar alguno, le solicitamos envié el comprobante del faltante '
            . 'al correo de su condominio.</p>'
            . '<br><p><b>FAVOR DE NO RESPONDER ESTE CORREO</b></p>';
        $email->setSubject('Recibo de pago de administracion')
            ->send($message);
    }

    public function sendEmailToClient($id){
        $pago = $this->Pago->get($id);
        $condominoTable = TableRegistry::get('Condomino');
        $condomino = $condominoTable->find()->where(['id_condomino' => $pago->id_condomino])->first();
        $email = new Email();
        $email->setTransport('alpa')
            ->setEmailFormat('html')
            ->setTo($condomino->correo_contacto)
            ->setCc('recibos@alpa-condominios.com')
            ->setCc('bitacora@alpaadministraciones.com')
            ->setFrom('recibos@kualitet.mx');
        //$this->createQrCode();
        $this->createFilePdf($pago);
        $email->setAttachments([
            'recibo.pdf' => [
                'file' => WWW_ROOT. 'recibo.pdf',
                'mimetype' => 'application/pdf'
            ]
        ]);
        $message = '<h2> Estimado '  . $condomino->residente_condominio . '</h2>'
            .  '<br><p>Nos permitimos enviarle en archivo adjunto su recibo. </p>'
            . '<br><p>En caso de faltar alguno, le solicitamos envié el comprobante del faltante '
            . 'al correo de su condominio.</p>'
            . '<br><p><b>FAVOR DE NO RESPONDER ESTE CORREO</b></p>';
        $email->setSubject('Recibo de pago de administracion')
            ->send($message);
            return $this->redirect('/pago/' . $condomino->id_condominio );

    }

    public function saveBanco($pago, $id_condominio = null)
    {
        $bancoTable = TableRegistry::get('Banco');
        $banco = $bancoTable->newEntity();

        $banco->id_condominio = $this->request->data['id_condominio'];
        $banco->id_pago = $pago->id_pago;
        $banco->abono = $pago->abono;
        $banco->nombre_banco = $pago->nombre_banco;
        $banco->no_cuenta = $pago->no_cuenta;


        if ($bancoTable->save($banco)){
            debug('Se guardo con éxito en banco');
        }
    }

    public function saveCierre($pago)
    {
        $cierreTable = TableRegistry::get('SaldoCierre');

        $fechaPago = new \DateTime($pago->fecha);
        $mes = $fechaPago->format('m');
        $anio = $fechaPago->format('Y');

        $cierre = $cierreTable->find()
            ->where([
                'id_condominio' => $pago->id_condominio,
                'MONTH(fecha)' => $mes,
                'YEAR(fecha)' => $anio
            ])
            ->first();

        if ($cierre) {
            $cierre->saldo = $cierre->saldo + $pago->abono;
        } else {
            $cierre = $cierreTable->newEntity();
            $cierre->id_condominio = $pago->id_condominio;
            $cierre->fecha = $pago->fecha;
            $cierre->saldo = $pago->abono;
        }

        if ($cierreTable->save($cierre)) {
            $this->Flash->success(__('El saldo cierre ha sido actualizado.'));
        } else {
            $this->Flash->error(__('El saldo cierre no se pudo guardar. Por favor, intenta de nuevo.'));
        }
    }

    public function cierreCondomino($pago){
        $cierreCondominoTable = TableRegistry::get('CierreCondomino');

        $fechaPago = new \DateTime($pago->fecha);
        $mes = $fechaPago->format('m');
        $anio = $fechaPago->format('Y');

        $cierreCondomino = $cierreCondominoTable->find()
            ->where([
                'id_condomino' => $pago->id_condomino,
                'MONTH(CierreCondomino.fecha) = ' . $mes,
                'YEAR(CierreCondomino.fecha) = ' . $anio])
            ->first();

        if ($cierreCondomino) {
            $cierreCondomino->saldo = $cierreCondomino->saldo + $pago->abono;
        } else {
            $cierreCondomino = $cierreCondominoTable->newEntity();
            $cierreCondomino->id_condomino = $pago->id_condomino;
            $cierreCondomino->fecha = $pago->fecha;
            $cierreCondomino->saldo = $pago->abono;
        }

        // Guardar el registro
        if ($cierreCondominoTable->save($cierreCondomino)) {
            $this->Flash->success(__('El saldo cierre ha sido actualizado.'));
        } else {
            $this->Flash->error(__('El saldo cierre no se pudo guardar. Por favor, intenta de nuevo.'));
        }
    }
    public function updateBanco($pago)
    {
        $bancoTable = TableRegistry::get('Banco');
        //debug("ID del Pago --> " + $pago->id_pago);
        $banco = $bancoTable->find()->where(['id_pago' => $pago->id_pago])->first();

        $banco->abono = $pago->abono;

        if ($bancoTable->save($banco)){
               //debug('Se modifico con éxito en banco');
        }
    }

    public function deleteBanco($pago){
        $bancoTable = TableRegistry::get('Banco');
        debug("ID del Pago --> " + $pago->id_pago);
        $banco = $bancoTable->find()->where(['id_pago' => $pago->id_pago])->first();
        if ($banco != null){
            if ($bancoTable->delete($banco)){
               debug('Se borro con éxito el banco');
               return true;
            } else {
                debug('NO Se borro el registro');
                return false;
            }
        }
        return true;
    }

    protected function deleteFavor($pago){
     /*   $favorTable = TableRegistry::get('Favor');
        debug("ID del Pago --> " + $pago->id_pago);
        $favor = $favorTable->find()->where(['id_pago' => $pago->id_pago])->first();
        if ($favor != null){
            if ($favorTable->delete($favor)){
                debug('Se borro con éxito el banco');
                return true;
            } else {
                debug('NO Se borro el registro');
                return false;
            }
        }
        return true; */

    }

    public function condominio()
    {
        $id = $this->request->getQuery('id_condominio');
        Log::debug(" Id -------> " + $id);
        //$condominios = TableRegistry::get('Condominio');
        $cuotas = TableRegistry::get('Cuotas');

        $cuotaSelected = $cuotas->find()->where(['id_condominio' => $id])->first();

        // the order of these three lines is very important !!!
        $resultJ = json_encode($cuotaSelected);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;
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


    public function getDepartamentos(){
        $id = $this->request->getQuery('id_condominio');
        //Log::debug(" Id -------> " + $id);
        $condominos = TableRegistry::get('Condomino');

        $departamentos = $condominos->find()->where(['id_condominio' => $id])->all();
        $resultJ = json_encode($departamentos);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;
    }

    /**
     * Edit method
     *
     * @param string|null $id Pago id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null, $id_condominio = null)
    {
        $pago = $this->Pago->get($id, [
            'contain' => []
        ]);

        $this->loadModel('condominio'); //if it's not already loaded
        $optionsCondominio = $this->condominio->find('list', ['keyField' => 'id_condominio',
            'valueField' => 'nombre_condominio']);
        $this->loadModel('condomino'); //if it's not already loaded
        $optionsCondomino = $this->condomino->find('list', ['keyField' => 'id_condomino',
            'valueField' => 'numero_departamento']);
        $this->loadModel('tipo_pago'); //if it's not already loaded
        $optionsTipo = $this->tipo_pago->find('list', ['keyField' => 'id_tipo',
            'valueField' => 'nombre']);


        $this->set('optionsCondominio', $optionsCondominio);
        $this->set('optionsCondomino', $optionsCondomino);
        $this->set('optionsTipo', $optionsTipo);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $pago = $this->Pago->patchEntity($pago, $this->request->getData());
            //debug('Pago: ' . $pago->abono);
            if ($this->Pago->save($pago)) {

                $this->updateBanco($pago);
                $this->Flash->success(__('The pago has been saved.'));

                return $this->redirect(['action' => 'add'], array('target'=>'_blank'));
            }
            $this->Flash->error(__('The pago could not be saved. Please, try again.'));
        }
        $this->set(compact('pago'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Pago id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $pago = $this->Pago->get($id);
        if ($this->deleteBanco($pago)){
            if ($this->Pago->delete($pago)) {
                  $this->deleteBanco($pago);

                $this->Flash->success(__('The pago has been deleted.'));
            } else {
                $this->Flash->error(__('The pago could not be deleted. Please, try again.'));
            }
        }
        return $this->redirect(['action' => 'index']);
    }

    public function getCondominos($id_condominio = null){
        $condominoTable = TableRegistry::get('Condomino'); //Carga los registros de la tabla
        $filtro = $this->request->getQuery('search'); //
        $page = $this->request->getQuery('page');
        $limit = $this->request->getQuery('size');
        if($page == 'NaN'){
            $varPage=1;
        }
        Log::debug('Condominio Id:' . $id_condominio . ' Buscar: '.$filtro.' page: '. $page);
        $query= $condominoTable
            ->find()
            ->select([
                'id'=>'id_condomino',
                'text'=>'numero_departamento'
            ])
            ->where([
                'numero_departamento LIKE' => '%'.$filtro.'%',
                    'id_condominio' => $id_condominio
            ])
            ->hydrate(false)
            ->limit($limit)
            ->page($page);
        $more = ['more'=> 'true'];
        //$result = ['result'=>$query, 'pagination'=>$more];
        $result = ['results'=>$query, 'pagination'=>$more];
        $resultJson = json_encode($result);
        //Log::debug('Result '. $resultJson);

        $this->response->type('json');
        $this->response->body($resultJson);

        return $this->response;
    }

    public function borrar($id = null){

        $pago = $this->Pago->get($id);
        if ($this->Pago->delete($pago)){
            $this->deleteBanco($pago);
            $this->deleteFavor($pago);
            $message = [['message' => 'Se borro con éxito el condomino - ' . $pago->id_pago . ' - ', 'result' => 'ok']];
        } else {
            $message = [['message' => 'Error al borrar los datos', 'result' => 'error']];
            Log::debug($pago->errors());
        }

        $resultJ = json_encode($message);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;

    }

    public function deleteAdeudo($pago){
        $adeudoTable = TableRegistry::get('Adeudos');
        $adeudo = $adeudoTable->find()
            ->where(['concepto' => $pago->concepto])
            ->where(['id_condomino' => $pago->id_condomino])
            ->where(['monto' => $pago->abono])
            ->first();
        if ($adeudo != null){
            if ($adeudoTable->delete($adeudo)){
                debug('Se borro con éxito el adeudo');
                return true;
            } else {
                debug('NO Se borro el registro');
                return false;
            }
        }
        return true;
    }

    public function actualizaAdeudo($pago){
        $adeudoTable = TableRegistry::get('Adeudos');
        $adeudo = $adeudoTable->find()
            ->where(['concepto' => $pago->concepto])
            ->where(['id_condomino' => $pago->id_condomino])
            ->where(['monto' => $pago->abono])
            ->first();
        if ($adeudo != null){
            if ($adeudoTable->delete($adeudo)){
                debug('Se borro con éxito el adeudo');
                return true;
            } else {
                debug('NO Se borro el registro');
                return false;
            }
        }
        return true;



        $sAdeudo = TableRegistry::get('adeudo');
        $query3 = $sAdeudo->query();
        $query3->update()
            ->set(['abono' => $suma])
            ->where(['id_condomino'=> $data[2]])
            ->execute();
    }

    protected function pagoConcentrado($pago){
        $adeudoTable = TableRegistry::get('Adeudos');
        $adeudo = $adeudoTable->find()
            ->where(['concepto' => $pago->concepto])
            ->where(['id_condomino' => $pago->id_condomino])
            ->where(['monto' => $pago->abono])
            ->first();


    }

    public function saldoCondomino($pago){

        $saldoCondo = TableRegistry::getTableLocator()->get('saldo_condomino');
        $saldo = $saldoCondo->newEntity();

        $saldo->id_condomino = $pago->id_condomino;
        $saldo->id_condominio = $pago->id_condominio;
        $saldo->saldo = $pago->abono;
        $saldo->fecha = $pago->fecha;
        $saldoCondo->save($saldo);
    }



    public function saldoCondominio($pago){

        $saldoCondo = TableRegistry::getTableLocator()->get('saldo_condominio');
        $saldo = $saldoCondo->newEntity();

        $saldo->id_condominio = $this->request->data['id_condominio'];
        $saldo->saldo = $pago->abono;
        $saldo->fecha = $pago->fecha;

        $saldoCondo->save($saldo);
    }



    public function saldoFavor($pago, $id_condominio = null){

        $saldoFavor = TableRegistry::getTableLocator()->get('Favor');
        $saldo = $saldoFavor->newEntity();

        $favorTable = TableRegistry::get('Favor');
        $favor = $favorTable->find()->where(['id_condomino'=> $pago->id_condomino])
                                    ->last();

        if($favor === 0 || is_null($favor) || $favor == ''){
            $saldo->abono = $pago->abono;
        }else{
            $suma = $pago->abono + $favor->abono;
            $saldo->abono = $suma;
        }

        $saldo->id_condominio = $this->request->data['id_condominio'];
        $saldo->id_condomino = $pago->id_condomino;
        $saldo->fecha = $pago->fecha;
        $saldo->estatus = 1;

        $saldoFavor->save($saldo);
    }
    public function actualizaSaldo($pago, $id_condominio = null){

        $saldoFavor = TableRegistry::getTableLocator()->get('Favor');
        $saldo = $saldoFavor->newEntity();

        $favorTable = TableRegistry::get('Favor');
        $favor = $favorTable->find()->where(['id_condomino'=> $pago->id_condomino])
            ->last();

        if($favor === 0 || is_null($favor) || $favor == ''){
            $saldo->abono = $pago->abono + $pago->bonificacion;
            $saldo->id_condominio = $this->request->data['id_condominio'];
            $saldo->id_condomino = $pago->id_condomino;
            $saldo->fecha = $pago->fecha;
            $saldo->estatus = 1;
            $saldoFavor->save($saldo);
        }else{
            $suma = $pago->abono + $favor->abono + $pago->bonificacion;

            $favor = TableRegistry::get('Favor');
            $query = $favor->query();
            $query->update()
                ->set(['abono' => $suma])
                ->where(['id_condomino'=> $pago->id_condomino])
                ->execute();
        }


    }

    /*
        $folioTable = TableRegistry::get('Folios');
        $folio = $folioTable->find()->where(['id_condominio' => $id_condominio])->first();
        $folio->serie = $serie;
        $folioTable->save($folio);


     if ($this->request->is('post')) {
            $pago = $this->Pago->patchEntity($pago, $this->request->getData());
            // Incrementa el folio
            $serie = $folio->serie + $folio->incremento;
            $pago->folio = $serie;
                // Actualizando el folio
                $folio->serie = $serie;
                $folioTable->save($folio);
                // Fin de actualizacion de folio
                $this->createPdf($pago);
                $this->sendEmail($pago);
     */

    public function xlsx()
    {
      $condominio = $_GET['condominio'];

      $pagos = TableRegistry::getTableLocator()->get('Condomino');
      $todosPagos = $pagos->find()
                          ->select()
                          ->where([
              'id_condominio' => $condominio
          ]);

        $totales = $todosPagos->all();


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
        $sheet->setCellValue('A1', 'id_pago');
        $sheet->setCellValue('B1', 'id_condominio');
        $sheet->setCellValue('C1', 'id_condomino');
        $sheet->setCellValue('D1', 'abono');
        $sheet->setCellValue('E1', 'monto_letra');
        $sheet->setCellValue('F1', 'concepto');
        $sheet->setCellValue('G1', 'fecha_pago');
        $sheet->setCellValue('H1', 'fecha');
        $sheet->setCellValue('I1', 'referencia');
        $sheet->setCellValue('J1', 'id_tipo');
        $sheet->setCellValue('K1', 'id_cuota');
        $sheet->setCellValue('L1', 'folio');
        $sheet->setCellValue('M1', 'Condominio');

        foreach($totales as $totales) {
        $sheet->setCellValue('A'.$numeroDeFila, "");
        $sheet->setCellValue('B'.$numeroDeFila, $totales->id_condominio);
        $sheet->setCellValue('C'.$numeroDeFila, $totales->id_condomino);
        $sheet->setCellValue('D'.$numeroDeFila, "1000");
        $sheet->setCellValue('E'.$numeroDeFila, "Mil pesos");
        $sheet->setCellValue('F'.$numeroDeFila, "Mantenimiento");
        $sheet->setCellValue('G'.$numeroDeFila, "");
        $sheet->setCellValue('H'.$numeroDeFila, "2022-01-01");
        $sheet->setCellValue('I'.$numeroDeFila, "0000000");
        $sheet->setCellValue('J'.$numeroDeFila, "1");
        $sheet->setCellValue('K'.$numeroDeFila, "1");
        $sheet->setCellValue('L'.$numeroDeFila, "1");
        $sheet->setCellValue('M'.$numeroDeFila, $totales->numero_departamento);
        $numeroDeFila++;
        }

        $writer = new Xlsx($spreadsheet);

        // ↓↓ Added new code from here in the eariler sample code
        // Save the file in a stream
        $stream = new CallbackStream(function () use ($writer) {
            $writer->save('php://output');
        });
        $filename = 'Pago'.$this->request->session()->read('Auth.User.id_user');
        $response = $this->response;

        // Return the stream in a response
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
                $this->Flash->success('Archivo Cargado');
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

        $pago = TableRegistry::get('Pago');
        foreach ($sheetData as $row){
            if($isheader > 0) {
                if(null !== $row){
                    $pago->query()
                        ->insert(['id_condominio', 'id_condomino', 'abono' ,'monto_letra', 'concepto', 'fecha_pago', 'fecha', 'referencia', 'id_tipo', 'id_cuotas', 'folio'])
                        ->values([
                            'id_condominio' => $row[1],
                            'id_condomino' => $row[2],
                            'abono' => $row[3],
                            'monto_letra' => $row[4],
                            'concepto' => $row[5],
                            'fecha_pago' => $row[6],
                            'fecha' => $row[7],
                            'referencia' => $row[8],
                            'id_tipo' => $row[9],
                            'id_cuotas' => $row[10],
                            'folio' => $row[11],
                        ])
                        ->execute();
                }
            }else
            { $isheader = 1;
            }
        }

        return $this->redirect($this->referer());
    }

    public function actualizaFavor(){

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

        $pago = TableRegistry::get('Favor');
        foreach ($sheetData as $row){
            if($isheader > 0) {
                if(null !== $row){
                    $pago->query()
                        ->insert(['id_condominio', 'id_condomino', 'abono', 'fecha','estatus'])
                        ->values([
                            'id_condominio' => $row[1],
                            'id_condomino' => $row[2],
                            'abono' => $row[3],
                            'fecha' => $row[7],
                            'estatus' => 1,
                        ])
                        ->execute();
                }
            }else
            { $isheader = 1;
            }
        }
        return $this->redirect($this->referer());
    }


    public function actualizaFavorActual(){

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

        $favorTable = TableRegistry::get('Favor');
        $query2 = $favorTable->find();
        foreach ($query2 as $favor) {

            foreach ($sheetData as $row){

                if ($isheader > 0) {
                    if (null !== $row) {

                        $suma = $query2->abono + $row[3];

                        //$favor = TableRegistry::get('Favor');
                        $query = $favor->query();
                        $query->update()
                            ->set(['abono' => $suma])
                            ->where(['id_condomino' => $query2->id_condomino])
                            ->execute();
                    }
                } else {
                    $isheader = 1;
                }
            }
        }

        return $this->redirect($this->referer());
    }



    public function actualizaFavorActua(){

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
        $sheetData = $spreadsheet->getActiveSheet()->toArray( null, false, true, false);
        //var_dump($sheetData);
        $isheader = 0;



        $favorTable = TableRegistry::get('Favor');
        $query2 = $favorTable->find()->all();
        $favor = TableRegistry::get('Favor');

        foreach ($query2 as $query2){
            foreach ($sheetData as $data){
                if ($isheader > 0) {
                    if (null !== $data) {
                        $suma = $query2->abono + $data[3];
                        $query = $favor->query();
                        $query->update()
                            ->set(['abono' => $suma])
                            ->where(['id_condomino' => $query2->id_condomino])
                            ->execute();
                    }

                }else{
                    $isheader = 1;
                }

            }
        }

        $this->Flash->success(__('registro cargado'));
        return $this->redirect($this->referer());
        $this->Flash->success(__('registro cargado'));

    }


}
