<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ComunicadoTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ComunicadoTable Test Case
 */
class ComunicadoTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ComunicadoTable
     */
    public $Comunicado;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Comunicado',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Comunicado') ? [] : ['className' => ComunicadoTable::class];
        $this->Comunicado = TableRegistry::getTableLocator()->get('Comunicado', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Comunicado);

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
