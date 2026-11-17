<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * GastoFixture
 */
class GastoFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'gasto';
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id_gasto' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
        'id_condominio' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'id_proveedores' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'id_tipo' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null],
        'id_categoria' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'gasto' => ['type' => 'decimal', 'length' => 19, 'precision' => 2, 'unsigned' => false, 'null' => true, 'default' => null],
        'monto_letra' => ['type' => 'string', 'length' => 150, 'null' => true, 'default' => null],
        'concepto' => ['type' => 'string', 'length' => 200, 'null' => true, 'default' => null],
        'fecha_pago' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => 'CURRENT_TIMESTAMP'],
        'fecha' => ['type' => 'date', 'length' => null, 'null' => true, 'default' => null],
        'referencia' => ['type' => 'string', 'length' => 150, 'null' => true, 'default' => null],
        'recibo' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null],
        'dir' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null],
        'confirmacion' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id_gasto'], 'length' => []],
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
                'id_gasto' => 1,
                'id_condominio' => 1,
                'id_proveedores' => 1,
                'id_tipo' => 1,
                'gasto' => 1,
                'monto_letra' => 'Lorem ipsum dolor sit amet',
                'concepto' => 'Lorem ipsum dolor sit amet',
                'fecha_pago' => 1614719466,
                'fecha' => '2021-03-02',
                'referencia' => 'Lorem ipsum dolor sit amet',
                'recibo' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
