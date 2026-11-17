<?php
namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Internal helpers and empty stubs must not be reachable as URL actions.
 * They previously answered with server errors (missing template, wrong argument types).
 */
class InternalMethodsNotRoutableTest extends TestCase
{
    use IntegrationTestTrait;

    public function setUp()
    {
        parent::setUp();
        $this->session(['Auth' => ['User' => ['id_user' => 1, 'id_perfil' => 1]]]);
    }

    /**
     * @return array
     */
    public function internalUrls()
    {
        return [
            ['/pago/create-pdf/1'],
            ['/pago/delete-favor/1'],
            ['/pago/pago-concentrado/1'],
            ['/adeudos/resta-adeudo/1/1'],
            ['/administrador/display'],
            ['/users/display'],
            ['/bloque/save-adeudo/1'],
            ['/events/calendario'],
            ['/gasto/getid/1'],
            ['/invoices/view/1'],
            ['/users/profile'],
            ['/administrador/profile'],
        ];
    }

    /**
     * @dataProvider internalUrls
     * @param string $url Request URL.
     * @return void
     */
    public function testInternalMethodIsNotAnAction($url): void
    {
        $this->get($url);

        $this->assertResponseCode(404);
    }
}
