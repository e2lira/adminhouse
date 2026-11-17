<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BancoTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\BancoTable Test Case
 */
class BancoTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\BancoTable
     */
    public $Banco;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Banco',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Banco') ? [] : ['className' => BancoTable::class];
        $this->Banco = TableRegistry::getTableLocator()->get('Banco', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Banco);

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
