<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Application;
use App\Middleware\RequestTransactionMiddleware;
use App\Middleware\SessionUserMiddleware;
use App\Test\Support\DatabaseTestCase;
use App\Test\Support\ProbeApplication;
use App\Test\Support\SignsInTrait;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\TestSuite\IntegrationTestTrait;
use RuntimeException;

/**
 * Every request runs in ONE transaction that tells PostgreSQL only the user and the context (RLS-SEC-001).
 */
class RequestSessionTest extends DatabaseTestCase
{
    use IntegrationTestTrait;
    use SignsInTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configApplication(ProbeApplication::class, [dirname(__DIR__, 3) . '/config']);
        $this->enableCsrfToken();
    }

    /**
     * @return array<string, mixed>
     */
    private function session(): array
    {
        return json_decode((string)$this->_response->getBody(), true);
    }

    public function testTheTransactionOpensBeforeAuthenticationAndTheUserIsSetAfterIt(): void
    {
        $queue = (new Application(dirname(__DIR__, 3) . '/config'))->middleware(new MiddlewareQueue());
        $order = [];
        foreach ($queue as $middleware) {
            $order[] = $middleware::class;
        }

        $transaction = array_search(RequestTransactionMiddleware::class, $order, true);
        $authentication = array_search(AuthenticationMiddleware::class, $order, true);
        $user = array_search(SessionUserMiddleware::class, $order, true);

        $this->assertNotFalse($transaction);
        $this->assertNotFalse($user);
        $this->assertLessThan($authentication, $transaction);
        $this->assertGreaterThan($authentication, $user);
    }

    public function testAnAnonymousRequestHasTheRequestContextAndNoUser(): void
    {
        $this->get('/__probe/session');

        $this->assertResponseOk();
        $session = $this->session();
        $this->assertSame('request', $session['context']);
        $this->assertContains($session['user_id'], [null, '']);
        $this->assertTrue($session['in_transaction']);
        $this->assertSame('kualitet_app', $session['db_user']);
    }

    public function testASignedInRequestCarriesTheUserId(): void
    {
        $userId = $this->signInAs('alice');

        $this->get('/__probe/session');

        $session = $this->session();
        $this->assertSame('request', $session['context']);
        $this->assertSame((string)$userId, $session['user_id']);
    }

    public function testTheTransactionIsOverWhenTheRequestEnds(): void
    {
        $this->signInAs('alice');
        $this->get('/__probe/session');

        $this->assertFalse($this->appDb()->inTransaction());
    }

    public function testASuccessfulRequestCommits(): void
    {
        $this->get('/__probe/write');

        $this->assertResponseOk();
        $count = $this->db()->execute("SELECT COUNT(*) FROM auth_events WHERE username = 'probe-write'")->fetch()[0];
        $this->assertSame(1, (int)$count);
    }

    public function testAFailedRequestRollsBack(): void
    {
        $this->disableErrorHandlerMiddleware();
        try {
            $this->get('/__probe/writeThenFail');
            $this->fail('The failure must propagate.');
        } catch (RuntimeException $e) {
            $this->assertSame('probe failure', $e->getMessage());
        }

        $this->assertFalse($this->appDb()->inTransaction());
        $count = $this->db()->execute("SELECT COUNT(*) FROM auth_events WHERE username = 'probe-fail'")->fetch()[0];
        $this->assertSame(0, (int)$count);
    }

    public function testTheSessionIsCleanForTheNextRequest(): void
    {
        $this->signInAs('alice');
        $this->get('/__probe/session');
        $this->assertSame('request', $this->session()['context']);

        $this->_session = [];
        $this->get('/__probe/session');
        $this->assertContains($this->session()['user_id'], [null, '']);
    }
}
