<?php
namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * AZ-1: the guard visit page must not be readable without signing in,
 * and only staff profiles may open it.
 */
class GuardVisitAccessTest extends TestCase
{
    use IntegrationTestTrait;

    public $fixtures = [
        'app.Users',
        'app.Asigna',
        'app.Condomino',
        'app.Condominio',
        'app.Comunicado',
        'app.Mensaje',
        'app.Visitas',
    ];

    public function testVisitIsNotReadableWithoutSignIn(): void
    {
        $this->get('/usuario/vigilante/1/1');

        $this->assertRedirectContains('redirect=');
        $this->assertResponseNotContains('placas');
    }

    public function testResidentProfileCannotOpenTheGuardPage(): void
    {
        $this->session(['Auth' => ['User' => ['id_user' => 50, 'id_perfil' => 6]]]);

        $this->get('/usuario/vigilante/1/1');

        $this->assertRedirect();
    }

    /**
     * @return array
     */
    public function staffProfiles()
    {
        return [[1], [2], [3], [5]];
    }

    /**
     * @dataProvider staffProfiles
     * @param int $profile Staff profile.
     * @return void
     */
    public function testStaffProfilesCanOpenTheGuardPage($profile): void
    {
        $this->session(['Auth' => ['User' => ['id_user' => 60, 'id_perfil' => $profile]]]);

        $this->get('/usuario/vigilante/1/1');

        $this->assertResponseOk();
    }
}
