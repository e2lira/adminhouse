<?php
declare(strict_types=1);

namespace App\Test\Support;

use App\Application;
use Cake\Routing\RouteBuilder;

require_once __DIR__ . '/ProbeController.php';

/**
 * The application plus a test-only route to the probe controller. The real route table has no fallbacks.
 */
class ProbeApplication extends Application
{
    /**
     * @param \Cake\Routing\RouteBuilder $routes Route builder.
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
        parent::routes($routes);
        $routes->connect('/__probe/{action}', ['controller' => 'Probe']);
    }
}
