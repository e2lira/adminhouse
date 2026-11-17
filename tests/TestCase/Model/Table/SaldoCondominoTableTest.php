<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SaldoCondominoTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\SaldoCondominoTable Test Case
 */
class SaldoCondominoTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\SaldoCondominoTable
     */
    public $SaldoCondomino;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.SaldoCondomino',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('SaldoCondomino') ? [] : ['className' => SaldoCondominoTable::class];
        $this->SaldoCondomino = TableRegistry::getTableLocator()->get('SaldoCondomino', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->SaldoCondomino);

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
