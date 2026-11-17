<?php
namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    public $fixtures = ['app.Users'];

    /**
     * Exact Location headers recorded on 2026-11-16 from a real run on the
     * isolated PHP 7.4.33 runner (SQLite memory, legacy request dispatcher).
     */
    private const PROFILE_ONE_LOCATION = '/pages/bienvenido';
    private const PROFILE_SIX_LOCATION = '/usuario/profile';
    private const DEFAULT_PROFILE_LOCATION = '/pages/bienvenido';

    public function testRootLoginRouteIsAvailable(): void
    {
        $this->get('/');

        $this->assertResponseOk();
        $this->assertResponseContains('Ingrese su usuario y contraseña');
        $this->assertResponseContains('name="username"');
    }

    public function testLoginFormIsAvailable(): void
    {
        $this->get('/users/login');

        $this->assertResponseOk();
        $this->assertResponseContains('Ingrese su usuario y contraseña');
        $this->assertResponseContains('name="username"');
    }

    public function testInvalidCredentialsDoNotAuthenticate(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/login', [
            'username' => 'profile-one',
            'password' => 'incorrect-password',
        ]);

        $this->assertResponseOk();
        $this->assertNoRedirect();
        $this->assertSession(null, 'Auth.User');
    }

    public function testProfileOneRedirectsToWelcomePage(): void
    {
        $this->login('profile-one');

        $this->assertExactRedirect(self::PROFILE_ONE_LOCATION, 'profile 1');
        $this->assertSession(1, 'Auth.User.id_perfil');
    }

    public function testProfileSixRedirectsToResidentProfile(): void
    {
        $this->login('profile-six');

        $this->assertExactRedirect(self::PROFILE_SIX_LOCATION, 'profile 6');
        $this->assertSession(6, 'Auth.User.id_perfil');
    }

    public function testDefaultProfileUsesConfiguredAuthRedirect(): void
    {
        $this->login('profile-two');

        $this->assertExactRedirect(self::DEFAULT_PROFILE_LOCATION, 'default profile');
        $this->assertSession(2, 'Auth.User.id_perfil');
    }

    private function login(string $username): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/login', [
            'username' => $username,
            'password' => 'correct-horse-battery-staple',
        ]);
    }

    private function assertExactRedirect(?string $expectedLocation, string $scenario): void
    {
        if ($expectedLocation === null) {
            $this->markTestIncomplete(
                'Run this scenario with the isolated compatible runner and record its exact Location for ' . $scenario . '.'
            );
        }

        $this->assertRedirect($expectedLocation);
    }
}
