<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SaldoCierreTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\SaldoCierreTable Test Case
 */
class SaldoCierreTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\SaldoCierreTable
     */
    public $SaldoCierre;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.SaldoCierre',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('SaldoCierre') ? [] : ['className' => SaldoCierreTable::class];
        $this->SaldoCierre = TableRegistry::getTableLocator()->get('SaldoCierre', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->SaldoCierre);

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
