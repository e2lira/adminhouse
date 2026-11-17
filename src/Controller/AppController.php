<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use App\Security\CondominiumAccess;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\I18n\I18n;
I18n::setLocale('es');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
   public $helpers = ['AkkaCKEditor.CKEditor' => [
		'version' => '4.4.7', // Default Option
		'distribution' => 'full' // Default Option / Other options => 'basic', 'standard', 'standard-all', 'full-all'
	]];

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('Security');`
     *
     * @return void
     */
    public function initialize()
    {


        parent::initialize();

        I18n::setLocale('es');

        $this->loadComponent('RequestHandler');
        $this->loadComponent('Flash');

        /*
         * Enable the following components for recommended CakePHP security settings.
         * see https://book.cakephp.org/3.0/en/controllers/components/security.html
         */
        $this->loadComponent('Security');
        $this->loadComponent('Csrf');
        $this->loadComponent('Auth', [
            'authorize'=> 'Controller',
            'loginRedirect' => [
                'controller' => 'Pages',
                'action' => 'bienvenido'
            ],
            'logoutRedirect' => [
                'controller' => 'Users',
                'action' => 'login'
                ],
            'authError' => '¡Lo siento! No estas autorizado para ingresar',
            //use isAuthorized in Controllers
            //'authorize' => ['Controller'],
            // If unauthorized, return them to page they were just on
            'unauthorizedRedirect' => $this->referer()
        ]);

        // AZ-2: after authorization, a condominium id taken from the URL must belong to the user.
        $this->getEventManager()->on('Controller.startup', ['priority' => 20], function () {
            CondominiumAccess::enforce($this);
        });
    }

      public function beforeFilter(Event $event)
      {
          // 'vigilante' is no longer public: it exposed any visit without signing in (AZ-1).
          $this->Auth->allow(['login', 'logout']);
      }

      public function changeLanguage($language=null){
        if($language!=null&& in_array($language, ['es_ES', 'es'])){
            $this->request->session()->write('Config.language',$language);
            return $this->redirect($this->referer());
        }else{
            $this->request->session()->write('Config.language',I18n::locale());
            return $this->redirect($this->referer());
        }
      }

}
