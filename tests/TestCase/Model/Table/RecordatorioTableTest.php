<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\RecordatorioTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\RecordatorioTable Test Case
 */
class RecordatorioTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\RecordatorioTable
     */
    public $Recordatorio;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Recordatorio',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Recordatorio') ? [] : ['className' => RecordatorioTable::class];
        $this->Recordatorio = TableRegistry::getTableLocator()->get('Recordatorio', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Recordatorio);

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
