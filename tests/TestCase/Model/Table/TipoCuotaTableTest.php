<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TipoCuotaTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TipoCuotaTable Test Case
 */
class TipoCuotaTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TipoCuotaTable
     */
    public $TipoCuota;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.TipoCuota',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('TipoCuota') ? [] : ['className' => TipoCuotaTable::class];
        $this->TipoCuota = TableRegistry::getTableLocator()->get('TipoCuota', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->TipoCuota);

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
