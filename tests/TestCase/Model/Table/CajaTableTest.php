<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CajaTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\CajaTable Test Case
 */
class CajaTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\CajaTable
     */
    public $Caja;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Caja',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Caja') ? [] : ['className' => CajaTable::class];
        $this->Caja = TableRegistry::getTableLocator()->get('Caja', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Caja);

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
