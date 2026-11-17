<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\RubroTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\RubroTable Test Case
 */
class RubroTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\RubroTable
     */
    public $Rubro;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Rubro',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Rubro') ? [] : ['className' => RubroTable::class];
        $this->Rubro = TableRegistry::getTableLocator()->get('Rubro', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Rubro);

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
