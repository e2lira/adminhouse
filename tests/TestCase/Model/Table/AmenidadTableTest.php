<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AmenidadTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AmenidadTable Test Case
 */
class AmenidadTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AmenidadTable
     */
    public $Amenidad;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Amenidad',
        'app.Condominio',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Amenidad') ? [] : ['className' => AmenidadTable::class];
        $this->Amenidad = TableRegistry::getTableLocator()->get('Amenidad', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Amenidad);

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
