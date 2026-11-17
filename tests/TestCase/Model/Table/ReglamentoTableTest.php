<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ReglamentoTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ReglamentoTable Test Case
 */
class ReglamentoTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ReglamentoTable
     */
    public $Reglamento;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Reglamento',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Reglamento') ? [] : ['className' => ReglamentoTable::class];
        $this->Reglamento = TableRegistry::getTableLocator()->get('Reglamento', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Reglamento);

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
