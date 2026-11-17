<?php
namespace App\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * The "new concept" form on the list page must work before the first concept exists.
 */
class ConceptosIndexTest extends TestCase
{
    use IntegrationTestTrait;

    public $fixtures = ['app.Conceptos', 'app.Caja'];

    public function setUp()
    {
        parent::setUp();
        $this->session(['Auth' => ['User' => ['id_user' => 1, 'id_perfil' => 1]]]);
    }

    public function testEmptyListRendersFormBoundToTheCajaRecord(): void
    {
        TableRegistry::getTableLocator()->get('Conceptos')->deleteAll([]);

        $this->get('/conceptos/index/1');

        $this->assertResponseOk();
        $this->assertHiddenValue('id_gasto', '1');
        $this->assertHiddenValue('id_condominio', '1');
    }

    public function testListWithRowsStillRendersForm(): void
    {
        $this->get('/conceptos/index/1');

        $this->assertResponseOk();
        $this->assertHiddenValue('id_gasto', '1');
    }

    public function testListWithoutCajaIdRendersWithoutError(): void
    {
        TableRegistry::getTableLocator()->get('Conceptos')->deleteAll([]);

        $this->get('/conceptos');

        $this->assertResponseOk();
    }

    private function assertHiddenValue(string $field, string $expected): void
    {
        $body = (string)$this->_response->getBody();
        $found = preg_match('/<input type="hidden" name="' . preg_quote($field, '/') . '"[^>]*value="([^"]*)"/', $body, $match);

        $this->assertSame(1, $found, 'Hidden field ' . $field . ' was not rendered with a value.');
        $this->assertSame($expected, $match[1]);
    }
}
