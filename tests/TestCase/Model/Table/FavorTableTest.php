<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\FavorTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\FavorTable Test Case
 */
class FavorTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\FavorTable
     */
    public $Favor;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Favor',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Favor') ? [] : ['className' => FavorTable::class];
        $this->Favor = TableRegistry::getTableLocator()->get('Favor', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Favor);

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
