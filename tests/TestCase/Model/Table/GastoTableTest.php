<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\GastoTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\GastoTable Test Case
 */
class GastoTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\GastoTable
     */
    public $Gasto;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Gasto',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Gasto') ? [] : ['className' => GastoTable::class];
        $this->Gasto = TableRegistry::getTableLocator()->get('Gasto', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Gasto);

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
