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

    /**
     * Walk every routed Developments action with a fresh development per request (no state carried between
     * actions) and check the exact status. An action that never authorizes answers 500 and fails here.
     *
     * @param array<string, int> $expected Status per `action METHOD`.
     * @param callable(int): void $prepare Called with each fresh development id before its request.
     * @return void
     */
    private function walkDevelopmentRoutes(array $expected, callable $prepare): void
    {
        $debug = Configure::read('debug');
        Configure::write('debug', false);
        $walked = [];
        try {
            foreach (Router::routes() as $route) {
                if (($route->defaults['controller'] ?? null) !== 'Developments') {
                    continue;
                }
                foreach ((array)($route->defaults['_method'] ?? ['GET']) as $method) {
                    $key = $route->defaults['action'] . ' ' . $method;
                    $dev = $this->createDevelopment('Walk ' . count($walked));
                    $prepare($dev);
                    $url = str_replace('{id}', (string)$dev, $route->template);
                    $method === 'POST' ? $this->post($url, []) : $this->get($url);
                    $walked[$key] = $this->_response->getStatusCode();
                }
            }
        } finally {
            Configure::write('debug', $debug);
        }

        ksort($walked);
        ksort($expected);
        $this->assertSame($expected, $walked);
    }

    public function testEveryRoutedDevelopmentActionAnswersTheSuperAdministratorWithItsExactStatus(): void
    {
        $root = $this->signInAs('root');
        $this->assignRole($root, 'super_administrator', null);

        $this->walkDevelopmentRoutes([
            'index GET' => 200, 'add GET' => 200, 'add POST' => 200, 'edit GET' => 200, 'edit POST' => 302,
            'archive POST' => 302, 'unarchive POST' => 302,
        ], fn(int $dev) => null);
    }

    public function testEveryRoutedDevelopmentActionRefusesAnAdministratorOfThatDevelopment(): void
    {
        $user = $this->signInAs('ann');

        $this->walkDevelopmentRoutes([
            'index GET' => 200, 'add GET' => 403, 'add POST' => 403, 'edit GET' => 404, 'edit POST' => 404,
            'archive POST' => 404, 'unarchive POST' => 404,
        ], fn(int $dev) => $this->assignRole($user, 'administrator', $dev));
    }
}
