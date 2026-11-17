<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SaldoCondominioTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\SaldoCondominioTable Test Case
 */
class SaldoCondominioTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\SaldoCondominioTable
     */
    public $SaldoCondominio;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.SaldoCondominio',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('SaldoCondominio') ? [] : ['className' => SaldoCondominioTable::class];
        $this->SaldoCondominio = TableRegistry::getTableLocator()->get('SaldoCondominio', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->SaldoCondominio);

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
