<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BancariaTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\BancariaTable Test Case
 */
class BancariaTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\BancariaTable
     */
    public $Bancaria;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Bancaria',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Bancaria') ? [] : ['className' => BancariaTable::class];
        $this->Bancaria = TableRegistry::getTableLocator()->get('Bancaria', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Bancaria);

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
