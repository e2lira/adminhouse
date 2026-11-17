<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CondominioTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\CondominioTable Test Case
 */
class CondominioTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\CondominioTable
     */
    public $Condominio;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Condominio',
        'app.Amenidad',
        'app.Users',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Condominio') ? [] : ['className' => CondominioTable::class];
        $this->Condominio = TableRegistry::getTableLocator()->get('Condominio', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Condominio);

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
