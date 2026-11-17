<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SupervisionTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\SupervisionTable Test Case
 */
class SupervisionTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\SupervisionTable
     */
    public $Supervision;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Supervision',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Supervision') ? [] : ['className' => SupervisionTable::class];
        $this->Supervision = TableRegistry::getTableLocator()->get('Supervision', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Supervision);

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
