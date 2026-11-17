<?php
namespace App\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * AZ-2: a condominium id taken from the URL must belong to the signed-in user,
 * except for the Superuser profile, which sees every condominium.
 */
class CondominiumAccessTest extends TestCase
{
    use IntegrationTestTrait;

    public $fixtures = [
        'app.Users',
        'app.UsersCondominio',
        'app.Asigna',
        'app.Condomino',
        'app.Condominio',
        'app.Comunicado',
        'app.TipoPago',
    ];

    public function setUp()
    {
        parent::setUp();
        TableRegistry::getTableLocator()->get('Condominio')->query()->insert(
            ['id_condominio', 'nombre_condominio', 'direccion_condominio', 'telefono_condominio']
        )->values([
            'id_condominio' => 2,
            'nombre_condominio' => 'Other condominium',
            'direccion_condominio' => 'Other street 1',
            'telefono_condominio' => '5500000000',
        ])->execute();
    }

    public function testStaffCanOpenTheirOwnCondominium(): void
    {
        $this->signInAs(1, 2);

        $this->get('/comunicado/index/1');

        $this->assertResponseOk();
    }

    public function testStaffGetsNotFoundForAnotherCondominium(): void
    {
        $this->signInAs(1, 2);

        $this->get('/comunicado/index/2');

        $this->assertResponseCode(404);
    }

    public function testStaffWithoutAnyCondominiumGetsNotFound(): void
    {
        $this->signInAs(77, 5);

        $this->get('/comunicado/index/1');

        $this->assertResponseCode(404);
    }

    public function testParameterNamedIdCondominioIsCheckedOverHttp(): void
    {
        $this->signInAs(1, 2);

        $this->get('/tipo-pago/index/1');
        $this->assertResponseOk();

        $this->get('/tipo-pago/index/2');
        $this->assertResponseCode(404);
    }

    public function testSuperuserSeesAnyCondominium(): void
    {
        $this->signInAs(99, 1);

        $this->get('/comunicado/index/2');

        $this->assertResponseOk();
    }

    private function signInAs(int $userId, int $profile): void
    {
        $this->session(['Auth' => ['User' => ['id_user' => $userId, 'id_perfil' => $profile]]]);
    }
}
