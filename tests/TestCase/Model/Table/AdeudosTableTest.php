<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AdeudosTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AdeudosTable Test Case
 */
class AdeudosTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AdeudosTable
     */
    public $Adeudos;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Adeudos',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Adeudos') ? [] : ['className' => AdeudosTable::class];
        $this->Adeudos = TableRegistry::getTableLocator()->get('Adeudos', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Adeudos);

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
