<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TipoGastoTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TipoGastoTable Test Case
 */
class TipoGastoTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TipoGastoTable
     */
    public $TipoGasto;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.TipoGasto',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('TipoGasto') ? [] : ['className' => TipoGastoTable::class];
        $this->TipoGasto = TableRegistry::getTableLocator()->get('TipoGasto', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->TipoGasto);

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
