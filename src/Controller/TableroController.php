<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * Condominio Controller
 *
 * @property \App\Model\Table\CondominioTable $Condominio
 *
 * @method \App\Model\Entity\Condominio[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TableroController extends AppController
{

    public function recibo(){
        $this->viewBuilder()->layout('');
        
          $this->viewBuilder()->options([
            'pdfConfig' => [
                'orientation' => 'portrait',
                'filename' => 'User_' . $id . '.pdf'
            ]
        ]);

    }
    
     public function view()
    {
$this->viewBuilder()->layout('');
        
          $this->viewBuilder()->options([
            'pdfConfig' => [
                'orientation' => 'portrait',
                'filename' => 'User_' . $id . '.pdf'
            ]
        ]);

    }
    
}