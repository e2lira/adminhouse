<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BancoFixture
 */
class BancoFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'banco';
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id_movimiento' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'id_condominio' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'id_pago' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'id_cargo' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'cargo' => ['type' => 'float', 'length' => 19, 'precision' => 2, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => ''],
        'abono' => ['type' => 'float', 'length' => 19, 'precision' => 2, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => ''],
        'fecha' => ['type' => 'timestamp', 'length' => null, 'null' => false, 'default' => 'CURRENT_TIMESTAMP', 'comment' => '', 'precision' => null],
        'no_cuenta' => ['type' => 'integer', 'length' => 55, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'nombre_banco' => ['type' => 'string', 'length' => 55, 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        '_indexes' => [
            'fk_banco_id_pago_idx' => ['type' => 'index', 'columns' => ['id_pago'], 'length' => []],
            'fk_cargo_id_cargo_idx' => ['type' => 'index', 'columns' => ['id_cargo'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id_movimiento'], 'length' => []],
            'fk_banco_id_pago' => ['type' => 'foreign', 'columns' => ['id_pago'], 'references' => ['pago', 'id_pago'], 'update' => 'noAction', 'delete' => 'noAction', 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'latin1_swedish_ci'
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
                'id_movimiento' => 1,
                'id_condominio' => 1,
                'id_pago' => 1,
                'id_cargo' => 1,
                'cargo' => 1,
                'abono' => 1,
                'fecha' => 1614719405,
                'no_cuenta' => 1,
                'nombre_banco' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
