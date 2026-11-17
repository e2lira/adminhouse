<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BloqueTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\BloqueTable Test Case
 */
class BloqueTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\BloqueTable
     */
    public $Bloque;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Bloque',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Bloque') ? [] : ['className' => BloqueTable::class];
        $this->Bloque = TableRegistry::getTableLocator()->get('Bloque', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Bloque);

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
