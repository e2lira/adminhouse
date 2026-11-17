<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Calendario Controller
 *
 * @property \App\Model\Table\Calendario $Condominio
 *
 * @method \App\Model\Entity\Condominio[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */

class CalendarioController extends AppController
{
    
    
     public function Calendario()
    {
         
    }
    
    public function load(){
        $id = $this->request->getQuery('id');
        $event = TableRegistry::get('Events');
        
        $eventos = $event->find();
        
        // the order of these three lines is very important !!!
        $resultJ = json_encode($eventos);
        $this->response->type('json');
        $this->response->body($resultJ);

        return $this->response;
    }
    
    public function insert(){
        $event = TableRegistry::get('Events');
        $query = $event->query();

        $query->insert(['title', 'start_event', 'end_event'])
         ->values([
        'title' => 'title',
        'body' => 'Some body text'
        ])
        ->execute();
        }
    
}