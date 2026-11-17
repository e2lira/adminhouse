<?php
/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Core\Plugin;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

/**
 * The default class to use for all routes
 *
 * The following route classes are supplied with CakePHP and are appropriate
 * to set as the default:
 *
 * - Route
 * - InflectedRoute
 * - DashedRoute
 *
 * If no call is made to `Router::defaultRouteClass()`, the class used is
 * `Route` (`Cake\Routing\Route\Route`)
 *
 * Note that `Route` does not do any inflections on URLs which will result in
 * inconsistently cased URLs when used with `:plugin`, `:controller` and
 * `:action` markers.
 *
 */
Router::defaultRouteClass(DashedRoute::class);
Router::extensions('xlsx');

Router::scope('/', function (RouteBuilder $routes) {
    /**
     * Here, we are connecting '/' (base path) to a controller called 'Pages',
     * its action called 'display', and we pass a param to select the view file
     * to use (in this case, src/Template/Pages/home.ctp)...
     */
    $routes->connect('/', ['controller' => 'Users', 'action' => 'login']);

    /**
     * ...and connect the rest of 'Pages' controller's URLs.
     */
    $routes->connect('/pages/*', ['controller' => 'Pages', 'action' => 'display']);


    $routes->connect('/condominio/:id',
        ['controller'=>'Condominio', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('/condomino/:id',
        ['controller'=>'Condomino', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('/condomino/add/:id',
        ['controller'=>'Condomino', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('/condomino/index/:id',
        ['controller'=>'asigna', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('/pago/add/:id',
        ['controller'=>'Pago', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('/gasto/add/:id',
        ['controller'=>'Gasto', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('/cuotas/add/:id',
        ['controller'=>'Cuotas', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('/folios/add/:id',
        ['controller'=>'Folios', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('proveedores/add/:id',
        ['controller'=>'Proveedores', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('proveedores/:id',
        ['controller'=>'Proveedores', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('banco/:id',
        ['controller'=>'Banco', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('bancaria/:id',
        ['controller'=>'Bancaria', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('bancaria/add/:id',
        ['controller'=>'Bancaria', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('/pago/:id',
        ['controller'=>'Pago', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('supervision/add/:id',
        ['controller'=>'Supervision', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('supervision/:id',
        ['controller'=>'Supervision', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('indiviso/add/:id',
        ['controller'=>'Indiviso', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('reportes/departamento/:id',
        ['controller'=>'Reportes', 'action'=>'departamento'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('reportes/:id',
        ['controller'=>'Reportes', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('reportes/prim/:id',
        ['controller'=>'Reportes', 'action'=>'prim'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('reportes/seg/:id',
        ['controller'=>'Reportes', 'action'=>'seg'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('reportes/dep/:id',
        ['controller'=>'Reportes', 'action'=>'dep'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('reportes/informe/:id',
        ['controller'=>'Reportes', 'action'=>'informe'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('reportes/informeSeg/:id',
        ['controller'=>'Reportes', 'action'=>'informeSeg'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('reportes/infoAnual/:id',
        ['controller'=>'Reportes', 'action'=>'infoAnual'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('reportes/cierre/:id',
        ['controller'=>'Reportes', 'action'=>'cierre'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('comunicado/index/:id',
        ['controller'=>'Comunicado', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('comunicado/add/:id',
        ['controller'=>'Comunicado', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('bloque/index/:id',
        ['controller'=>'Bloque', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('bloque/add/:id',
        ['controller'=>'Bloque', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('reglamento/index/:id',
        ['controller'=>'Reglamento', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('reglamento/add/:id',
        ['controller'=>'Reglamento', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('reportes/recibos/:id',
        ['controller'=>'Reportes', 'action'=>'recibos'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('reportes/nuevo/:id',
        ['controller'=>'Reportes', 'action'=>'nuevo'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('caja/add/:id',
        ['controller'=>'Caja', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('caja/index/:id',
        ['controller'=>'Caja', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('conceptos/add/:id',
        ['controller'=>'Conceptos', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('conceptos/index/:id',
        ['controller'=>'Conceptos', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('gasto/index/:id',
        ['controller'=>'Gasto', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('cuotas/index/:id',
        ['controller'=>'Cuotas', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('indiviso/index/:id',
        ['controller'=>'Indiviso', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('amenidad/add/:id',
        ['controller'=>'Amenidad', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('amenidad/index/:id',
        ['controller'=>'Amenidad', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('tipoPago/index/:id',
        ['controller'=>'tipoPago', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('tipoPago/add/:id',
        ['controller'=>'tipoPago', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('tipoGasto/add/:id',
        ['controller'=>'tipoGasto', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('tipoGasto/index/:id',
        ['controller'=>'tipoGasto', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('tipoCuota/index/:id',
        ['controller'=>'tipoCuota', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('tipoCuota/add/:id',
        ['controller'=>'tipoCuota', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('categoria/add/:id',
        ['controller'=>'categoria', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('categoria/index/:id',
        ['controller'=>'categoria', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);


    $routes->connect('saldoCondominio/index/:id',
        ['controller'=>'saldoCondominio', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('saldoCondominio/add/:id',
        ['controller'=>'saldoCondominio', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('saldoCondomino/index/:id',
        ['controller'=>'saldoCondomino', 'action'=>'index'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('saldoCondomino/add/:id',
        ['controller'=>'saldoCondomino', 'action'=>'add'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('usuario/registrar/:id',
        ['controller'=>'usuario', 'action'=>'registrar'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('usuario/qr/:id',
        ['controller'=>'usuario', 'action'=>'qr'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('supervision/areas/:id',
        ['controller'=>'supervision', 'action'=>'areas'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);

    $routes->connect('supervision/view/:id',
        ['controller'=>'supervision', 'action'=>'view'])
        ->setPatterns(['id'=>'\d+'])
        ->setPass(['id']);


    /**
     * Connect catchall routes for all controllers.
     *
     * Using the argument `DashedRoute`, the `fallbacks` method is a shortcut for
     *    `$routes->connect('/:controller', ['action' => 'index'], ['routeClass' => 'DashedRoute']);`
     *    `$routes->connect('/:controller/:action/*', [], ['routeClass' => 'DashedRoute']);`
     *
     * Any route class can be used with this method, such as:
     * - DashedRoute
     * - InflectedRoute
     * - Route
     * - Or your own route class
     *
     * You can remove these routes once you've connected the
     * routes you want in your application.
     */
    $routes->fallbacks(DashedRoute::class);
});

Router::scope('/', function (\Cake\Routing\RouteBuilder $routes) {
    $routes->setExtensions(['pdf']);
    // ...
});
