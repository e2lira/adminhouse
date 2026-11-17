<?php
namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * A resident account that has no unit assigned yet must get a clear message,
 * not a database error, on the resident landing page.
 */
class UsuarioProfileTest extends TestCase
{
    use IntegrationTestTrait;

    public $fixtures = ['app.Asigna', 'app.Condomino', 'app.Users'];

    public function testUserWithoutAssignedUnitSeesMessage(): void
    {
        $this->session(['Auth' => ['User' => ['id_user' => 999, 'id_perfil' => 6]]]);

        $this->get('/usuario/profile');

        $this->assertResponseOk();
        $this->assertResponseContains('no tiene una unidad asignada');
        $this->assertResponseNotContains('SQLSTATE');
    }
}
