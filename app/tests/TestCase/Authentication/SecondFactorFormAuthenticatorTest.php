<?php
declare(strict_types=1);

namespace App\Test\TestCase\Authentication;

use App\Application;
use App\Authentication\SecondFactor\PendingLogin;
use App\Test\Support\DatabaseTestCase;
use App\Test\Support\SecondFactorTestTrait;
use App\Test\Support\SignsInTrait;
use Authentication\Authenticator\ResultInterface;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\Routing\Router;
use Cake\TestSuite\IntegrationTestTrait;

/**
 * The authenticator in isolation, with a mocked session so session id handling can be asserted.
 */
class SecondFactorFormAuthenticatorTest extends DatabaseTestCase
{
    use IntegrationTestTrait;
    use SecondFactorTestTrait;
    use SignsInTrait;

    /**
     * @param \Cake\Http\Session $session Session to attach.
     * @param string $username Username to submit.
     * @return \Cake\Http\ServerRequest
     */
    private function loginRequest(Session $session, string $username): ServerRequest
    {
        $this->get('/login');

        return (new ServerRequest([
            'url' => Router::url(['controller' => 'Users', 'action' => 'login']),
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'post' => ['username' => $username, 'password' => self::PASSWORD],
        ]))->withAttribute('session', $session);
    }

    private function app(): Application
    {
        return new Application(dirname(__DIR__, 3) . '/config');
    }

    public function testSecondFactorUserGetsNoIdentityAndTheSessionIdIsRenewedOnce(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $this->enrollTotp($userId);
        $written = [];
        $session = $this->createMock(Session::class);
        $session->method('read')->willReturn(null);
        $session->method('check')->willReturn(false);
        $session->expects($this->once())->method('renew');
        $session->method('write')->willReturnCallback(function ($key, $value = null) use (&$written): void {
            $written[$key] = $value;
        });
        $request = $this->loginRequest($session, 'root');

        $result = $this->app()->getAuthenticationService($request)->authenticate($request);

        $this->assertFalse($result->isValid());
        $this->assertSame(ResultInterface::FAILURE_OTHER, $result->getStatus());
        $this->assertNull($result->getData(), 'a pending login must not carry identity data');
        $this->assertSame([PendingLogin::SESSION_KEY], array_keys($written), 'only the pending marker is written');
        $this->assertSame($userId, $written[PendingLogin::SESSION_KEY]['user_id']);
        $this->assertSame(0, $written[PendingLogin::SESSION_KEY]['failures']);
        $this->assertEqualsWithDelta(time() + PendingLogin::LIFETIME, $written[PendingLogin::SESSION_KEY]['expires'], 2);
    }

    public function testUserWithoutSecondFactorIsAuthenticatedAndNoPendingMarkerIsWritten(): void
    {
        $this->createUser('dave', self::PASSWORD);
        $session = $this->createMock(Session::class);
        $session->method('read')->willReturn(null);
        $session->method('check')->willReturn(false);
        $session->expects($this->never())->method('write');
        $request = $this->loginRequest($session, 'dave');

        $result = $this->app()->getAuthenticationService($request)->authenticate($request);

        $this->assertTrue($result->isValid());
    }
}
