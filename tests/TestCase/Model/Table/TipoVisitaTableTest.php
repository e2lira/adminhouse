<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TipoVisitaTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TipoVisitaTable Test Case
 */
class TipoVisitaTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TipoVisitaTable
     */
    public $TipoVisita;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.TipoVisita',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('TipoVisita') ? [] : ['className' => TipoVisitaTable::class];
        $this->TipoVisita = TableRegistry::getTableLocator()->get('TipoVisita', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->TipoVisita);

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
