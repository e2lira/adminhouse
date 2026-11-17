<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * IndivisoFixture
 */
class IndivisoFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'indiviso';
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id_cuotas' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
        'id_condominio' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'id_condomino' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'id_tipo' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'cuota' => ['type' => 'decimal', 'length' => 19, 'precision' => 2, 'unsigned' => false, 'null' => false, 'default' => null],
        'cuota_letra' => ['type' => 'string', 'length' => 100, 'null' => false, 'default' => null],
        'concepto' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'fecha_cargo' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => 'CURRENT_TIMESTAMP'],
        'fecha' => ['type' => 'date', 'length' => null, 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id_cuotas'], 'length' => []],
        ],
    ];
    // @codingStandardsIgnoreEnd
    /**
     * Init method
     *
     * @return void
     */
    public function init()
    {
        $this->records = [
            [
                'id_cuotas' => 1,
                'id_condominio' => 1,
                'id_condomino' => 'Lorem ipsum dolor sit amet',
                'id_tipo' => 1,
                'cuota' => 1,
                'cuota_letra' => 'Lorem ipsum dolor sit amet',
                'concepto' => 'Lorem ipsum dolor sit amet',
                'fecha_cargo' => '2021-04-06 14:23:45',
            ],
        ];
        parent::init();
    }
}
