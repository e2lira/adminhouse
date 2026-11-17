<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CierreCondominoTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\CierreCondominoTable Test Case
 */
class CierreCondominoTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\CierreCondominoTable
     */
    public $CierreCondomino;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.CierreCondomino',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('CierreCondomino') ? [] : ['className' => CierreCondominoTable::class];
        $this->CierreCondomino = TableRegistry::getTableLocator()->get('CierreCondomino', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->CierreCondomino);

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
