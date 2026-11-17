<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AsignaTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AsignaTable Test Case
 */
class AsignaTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AsignaTable
     */
    public $Asigna;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Asigna',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Asigna') ? [] : ['className' => AsignaTable::class];
        $this->Asigna = TableRegistry::getTableLocator()->get('Asigna', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Asigna);

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
