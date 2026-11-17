<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CuotasTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\CuotasTable Test Case
 */
class CuotasTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\CuotasTable
     */
    public $Cuotas;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Cuotas',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Cuotas') ? [] : ['className' => CuotasTable::class];
        $this->Cuotas = TableRegistry::getTableLocator()->get('Cuotas', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Cuotas);

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
