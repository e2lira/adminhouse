<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\FoliosTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\FoliosTable Test Case
 */
class FoliosTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\FoliosTable
     */
    public $Folios;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Folios',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Folios') ? [] : ['className' => FoliosTable::class];
        $this->Folios = TableRegistry::getTableLocator()->get('Folios', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Folios);

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
