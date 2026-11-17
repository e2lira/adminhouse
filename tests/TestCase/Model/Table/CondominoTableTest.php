<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CondominoTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\CondominoTable Test Case
 */
class CondominoTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\CondominoTable
     */
    public $Condomino;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Condomino',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Condomino') ? [] : ['className' => CondominoTable::class];
        $this->Condomino = TableRegistry::getTableLocator()->get('Condomino', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Condomino);

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
