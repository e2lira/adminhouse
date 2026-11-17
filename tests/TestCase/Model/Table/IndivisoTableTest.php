<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\IndivisoTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\IndivisoTable Test Case
 */
class IndivisoTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\IndivisoTable
     */
    public $Indiviso;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Indiviso',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Indiviso') ? [] : ['className' => IndivisoTable::class];
        $this->Indiviso = TableRegistry::getTableLocator()->get('Indiviso', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Indiviso);

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
