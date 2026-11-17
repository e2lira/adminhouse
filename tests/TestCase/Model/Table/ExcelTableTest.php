<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ExcelTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ExcelTable Test Case
 */
class ExcelTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ExcelTable
     */
    public $Excel;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Excel',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Excel') ? [] : ['className' => ExcelTable::class];
        $this->Excel = TableRegistry::getTableLocator()->get('Excel', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Excel);

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
