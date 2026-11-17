<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TipoPagoTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TipoPagoTable Test Case
 */
class TipoPagoTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TipoPagoTable
     */
    public $TipoPago;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.TipoPago',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('TipoPago') ? [] : ['className' => TipoPagoTable::class];
        $this->TipoPago = TableRegistry::getTableLocator()->get('TipoPago', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->TipoPago);

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
