<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PagoFixture
 */
class PagoFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'pago';
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id_pago' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
        'id_condominio' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null],
        'id_condomino' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null],
        'abono' => ['type' => 'decimal', 'length' => 19, 'precision' => 2, 'unsigned' => false, 'null' => false, 'default' => null],
        'bonificacion' => ['type' => 'decimal', 'length' => 19, 'precision' => 2, 'unsigned' => false, 'null' => true, 'default' => null],
        'monto_letra' => ['type' => 'string', 'length' => 150, 'null' => false, 'default' => null],
        'concepto' => ['type' => 'string', 'length' => 200, 'null' => false, 'default' => null],
        'fecha_pago' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => 'CURRENT_TIMESTAMP'],
        'fecha' => ['type' => 'date', 'length' => null, 'null' => false, 'default' => null],
        'referencia' => ['type' => 'string', 'length' => 150, 'null' => true, 'default' => null],
        'id_tipo' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'id_cuotas' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'folio' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id_pago'], 'length' => []],
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
                'id_pago' => 1,
                'id_condominio' => 1,
                'id_condomino' => 1,
                'abono' => 1,
                'monto_letra' => 'Lorem ipsum dolor sit amet',
                'concepto' => 'Lorem ipsum dolor sit amet',
                'fecha_pago' => 1614719477,
                'fecha' => '2021-03-02',
                'referencia' => 'Lorem ipsum dolor sit amet',
                'id_tipo' => 1,
                'id_cuotas' => 1,
                'folio' => 1,
            ],
        ];
        parent::init();
    }
}
