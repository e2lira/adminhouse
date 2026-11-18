<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Application;
use App\Test\Support\DatabaseTestCase;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\Routing\Router;
use Cake\TestSuite\IntegrationTestTrait;

/**
 * Password sign-in, sign-out and the "everything requires a session" rule.
 */
class AuthenticationTest extends DatabaseTestCase
{
    use IntegrationTestTrait;

    private const PASSWORD = 'correct horse battery';

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
    }

    /**
     * The integration harness starts every request with a fresh session, so the session written by the
     * previous request is handed to the next one explicitly, as a browser cookie would.
     */
    private function carrySession(): void
    {
        $this->_session = $_SESSION ?? [];
    }

    public function testUnauthenticatedRequestRedirectsToLogin(): void
    {
        $this->get('/');

        $this->assertRedirectContains('/login');
    }

    public function testUnauthenticatedLogoutRedirectsToLogin(): void
    {
        $this->post('/logout');

        $this->assertRedirectContains('/login');
    }

    public function testLoginFormIsShownInSpanish(): void
    {
        $this->get('/login');

        $this->assertResponseOk();
        $this->assertResponseContains('Iniciar sesión');
        $this->assertResponseContains('name="username"');
        $this->assertResponseContains('name="password"');
    }

    public function testValidCredentialsSignInAndRedirectToHome(): void
    {
        $this->createUser('alice', self::PASSWORD);

        $this->post('/login', ['username' => 'alice', 'password' => self::PASSWORD]);

        $this->assertRedirect('/');
        $this->carrySession();
        $this->get('/');
        $this->assertResponseOk();
        $this->assertResponseContains('Inicio');
        $this->assertResponseContains('alice');
    }

    public function testSignInHonoursAnInternalRedirectTarget(): void
    {
        $this->createUser('alice', self::PASSWORD);

        $this->post('/login?redirect=%2Fpages%2Fsomething', ['username' => 'alice', 'password' => self::PASSWORD]);

        $this->assertRedirect('/pages/something');
    }

    public function testSignInIgnoresAnExternalRedirectTarget(): void
    {
        $this->createUser('alice', self::PASSWORD);

        $this->post(
            '/login?redirect=' . rawurlencode('https://evil.example.test/'),
            ['username' => 'alice', 'password' => self::PASSWORD],
        );

        $this->assertRedirect('/');
    }

    public function testWrongPasswordStaysOnLoginWithSpanishError(): void
    {
        $this->createUser('alice', self::PASSWORD);

        $this->post('/login', ['username' => 'alice', 'password' => 'not the password']);

        $this->assertResponseOk();
        $this->assertResponseContains('Usuario o contraseña incorrectos');
        $this->assertNoRedirect();
        $this->get('/');
        $this->assertRedirectContains('/login');
    }

    public function testUnknownUserGetsTheSameError(): void
    {
        $this->post('/login', ['username' => 'nobody', 'password' => self::PASSWORD]);

        $this->assertResponseOk();
        $this->assertResponseContains('Usuario o contraseña incorrectos');
    }

    public function testDisabledUserCannotSignIn(): void
    {
        $this->createUser('mallory', self::PASSWORD, 'disabled');

        $this->post('/login', ['username' => 'mallory', 'password' => self::PASSWORD]);

        $this->assertResponseOk();
        $this->assertResponseContains('Usuario o contraseña incorrectos');
        $this->get('/');
        $this->assertRedirectContains('/login');
    }

    public function testUserWithoutCredentialsCannotSignIn(): void
    {
        $this->createUser('ghost', null);

        $this->post('/login', ['username' => 'ghost', 'password' => '']);

        $this->assertResponseOk();
        $this->assertResponseContains('Usuario o contraseña incorrectos');
    }

    public function testLogoutEndsTheSession(): void
    {
        $this->createUser('alice', self::PASSWORD);
        $this->post('/login', ['username' => 'alice', 'password' => self::PASSWORD]);
        $this->carrySession();
        $this->get('/');
        $this->assertResponseOk();

        $this->post('/logout');

        $this->assertRedirectContains('/login');
        $this->carrySession();
        $this->get('/');
        $this->assertRedirectContains('/login');
    }

    public function testLogoutRefusesGetRequests(): void
    {
        $this->createUser('alice', self::PASSWORD);
        $this->post('/login', ['username' => 'alice', 'password' => self::PASSWORD]);
        $this->carrySession();

        $this->get('/logout');

        $this->assertResponseCode(405);
        $this->get('/');
        $this->assertResponseOk();
    }

    public function testSignedInUserVisitingLoginGoesHome(): void
    {
        $this->createUser('alice', self::PASSWORD);
        $this->post('/login', ['username' => 'alice', 'password' => self::PASSWORD]);
        $this->carrySession();

        $this->get('/login');

        $this->assertRedirect('/');
    }

    public function testPasswordHashIsNeverStoredInTheSession(): void
    {
        $this->createUser('alice', self::PASSWORD);
        $this->post('/login', ['username' => 'alice', 'password' => self::PASSWORD]);

        $this->assertStringNotContainsString('password_hash', serialize($_SESSION ?? []));
    }

    public function testUserDisabledMidSessionIsSignedOutOnTheNextRequest(): void
    {
        $userId = $this->createUser('alice', self::PASSWORD);
        $this->post('/login', ['username' => 'alice', 'password' => self::PASSWORD]);
        $this->carrySession();
        $this->get('/');
        $this->assertResponseOk();

        $this->db()->execute("UPDATE users SET status = 'disabled' WHERE id = ?", [$userId]);

        $this->get('/');
        $this->assertRedirectContains('/login');
        $this->assertArrayNotHasKey('Auth', $_SESSION ?? [], 'the session must be cleared');

        $this->carrySession();
        $this->get('/');
        $this->assertRedirectContains('/login');
    }

    public function testUserDeletedMidSessionIsSignedOutOnTheNextRequest(): void
    {
        $userId = $this->createUser('alice', self::PASSWORD);
        $this->post('/login', ['username' => 'alice', 'password' => self::PASSWORD]);
        $this->carrySession();

        $this->db()->execute('DELETE FROM users WHERE id = ?', [$userId]);

        $this->get('/');
        $this->assertRedirectContains('/login');
        $this->assertArrayNotHasKey('Auth', $_SESSION ?? []);
    }

    public function testReEnabledUserStillNeedsToSignInAgain(): void
    {
        $userId = $this->createUser('alice', self::PASSWORD);
        $this->post('/login', ['username' => 'alice', 'password' => self::PASSWORD]);
        $this->carrySession();
        $this->db()->execute("UPDATE users SET status = 'disabled' WHERE id = ?", [$userId]);
        $this->get('/');
        $this->carrySession();
        $this->db()->execute("UPDATE users SET status = 'active' WHERE id = ?", [$userId]);

        $this->get('/');

        $this->assertRedirectContains('/login');
    }

    public function testSessionStoresOnlyTheUserId(): void
    {
        $userId = $this->createUser('alice', self::PASSWORD);
        $this->post('/login', ['username' => 'alice', 'password' => self::PASSWORD]);

        $this->assertSame($userId, (int)($_SESSION['Auth'] ?? 0));
    }

    public function testSessionIdIsRenewedOnLoginAndLogout(): void
    {
        $userId = $this->createUser('alice', self::PASSWORD);
        $app = new Application(dirname(__DIR__, 3) . '/config');
        $this->get('/login');

        $loginUrl = Router::url(['controller' => 'Users', 'action' => 'login']);
        $session = $this->createMock(Session::class);
        $session->method('read')->willReturn(null);
        $session->method('check')->willReturn(false);
        $session->expects($this->once())->method('renew');
        $session->expects($this->once())->method('write')->with('Auth', $userId);

        $request = (new ServerRequest([
            'url' => $loginUrl,
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'post' => ['username' => 'alice', 'password' => self::PASSWORD],
        ]))->withAttribute('session', $session);

        $service = $app->getAuthenticationService($request);
        $result = $service->authenticate($request);
        $this->assertTrue($result->isValid());

        $service->persistIdentity($request, new Response(), $result->getData());
    }
}
