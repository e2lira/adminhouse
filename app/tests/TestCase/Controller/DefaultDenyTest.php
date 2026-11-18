<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Application;
use App\Controller\AppController;
use App\Test\Support\DatabaseTestCase;
use App\Test\Support\ProbeApplication;
use App\Test\Support\SignsInTrait;
use Authentication\Middleware\AuthenticationMiddleware;
use Authorization\Middleware\AuthorizationMiddleware;
use Cake\Core\Configure;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Router;
use Cake\TestSuite\IntegrationTestTrait;

/**
 * RB-R6: an action that does not authorize (or explicitly skip) is refused.
 */
class DefaultDenyTest extends DatabaseTestCase
{
    use IntegrationTestTrait;
    use SignsInTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configApplication(ProbeApplication::class, [dirname(__DIR__, 3) . '/config']);
        $this->enableCsrfToken();
    }

    public function testAuthorizationMiddlewareRequiresACheckOnEveryRequest(): void
    {
        $app = new Application(dirname(__DIR__, 3) . '/config');
        $queue = $app->middleware(new MiddlewareQueue());

        $found = null;
        foreach ($queue as $middleware) {
            if ($middleware instanceof AuthorizationMiddleware) {
                $found = $middleware;
            }
        }

        $this->assertNotNull($found, 'AuthorizationMiddleware must be in the queue');
        $this->assertTrue($found->getConfig('requireAuthorizationCheck'));
    }

    public function testAuthorizationRunsAfterAuthentication(): void
    {
        $app = new Application(dirname(__DIR__, 3) . '/config');
        $queue = $app->middleware(new MiddlewareQueue());

        $order = [];
        foreach ($queue as $middleware) {
            $order[] = $middleware::class;
        }

        $this->assertGreaterThan(
            array_search(AuthenticationMiddleware::class, $order, true),
            array_search(AuthorizationMiddleware::class, $order, true),
        );
    }

    public function testActionThatNeverAuthorizesIsRefused(): void
    {
        $this->signInAs('ann');
        Configure::write('debug', false);

        $this->get('/__probe/unchecked');

        $this->assertResponseCode(500);
        $this->assertResponseNotContains('unchecked ran');
    }

    public function testActionThatSkipsExplicitlyIsAllowed(): void
    {
        $this->signInAs('ann');

        $this->get('/__probe/skipped');

        $this->assertResponseOk();
        $this->assertResponseContains('skipped ok');
    }

    public function testAnonymousRequestToAnyProtectedActionGoesToLogin(): void
    {
        $this->get('/__probe/unchecked');

        $this->assertRedirectContains('/login');
    }

    public function testEveryRoutedControllerExtendsAppController(): void
    {
        $this->get('/login');

        $controllers = [];
        foreach (Router::routes() as $route) {
            $controller = $route->defaults['controller'] ?? null;
            // Plugin routes (DebugKit in debug mode) are not ours to police.
            if (is_string($controller) && empty($route->defaults['plugin'])) {
                $controllers[$controller] = true;
            }
        }

        $this->assertNotEmpty($controllers);
        foreach (array_keys($controllers) as $name) {
            $class = Configure::read('App.namespace') . '\\Controller\\' . $name . 'Controller';
            $this->assertTrue(
                is_subclass_of($class, AppController::class),
                $class . ' must extend AppController to inherit authentication and authorization',
            );
        }
    }
}
