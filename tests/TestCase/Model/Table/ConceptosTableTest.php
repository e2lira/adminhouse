<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ConceptosTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ConceptosTable Test Case
 */
class ConceptosTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ConceptosTable
     */
    public $Conceptos;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Conceptos',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Conceptos') ? [] : ['className' => ConceptosTable::class];
        $this->Conceptos = TableRegistry::getTableLocator()->get('Conceptos', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Conceptos);

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
