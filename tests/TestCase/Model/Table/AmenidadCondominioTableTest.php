<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AmenidadCondominioTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AmenidadCondominioTable Test Case
 */
class AmenidadCondominioTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AmenidadCondominioTable
     */
    public $AmenidadCondominio;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.AmenidadCondominio',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('AmenidadCondominio') ? [] : ['className' => AmenidadCondominioTable::class];
        $this->AmenidadCondominio = TableRegistry::getTableLocator()->get('AmenidadCondominio', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->AmenidadCondominio);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
