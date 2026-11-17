<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\UsersCondominioTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\UsersCondominioTable Test Case
 */
class UsersCondominioTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\UsersCondominioTable
     */
    public $UsersCondominio;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.UsersCondominio',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('UsersCondominio') ? [] : ['className' => UsersCondominioTable::class];
        $this->UsersCondominio = TableRegistry::getTableLocator()->get('UsersCondominio', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->UsersCondominio);

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
