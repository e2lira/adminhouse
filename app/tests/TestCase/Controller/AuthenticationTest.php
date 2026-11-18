<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\Support\DatabaseTestCase;
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

        $this->assertStringNotContainsString('password_hash', serialize($this->_requestSession->read()));
    }
}
