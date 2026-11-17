<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SaldoCondominoFixture
 */
class SaldoCondominoFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'saldo_condomino';
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id_saldo' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
        'id_condominio' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'id_condomino' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'saldo' => ['type' => 'decimal', 'length' => 9, 'precision' => 2, 'unsigned' => false, 'null' => true, 'default' => null],
        'contra' => ['type' => 'decimal', 'length' => 9, 'precision' => 2, 'unsigned' => false, 'null' => true, 'default' => null],
        'fecha' => ['type' => 'date', 'length' => null, 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id_saldo'], 'length' => []],
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
                'id_saldo' => 1,
                'id_condominio' => 1,
                'id_condomino' => 1,
                'saldo' => 'Lorem ipsum dolor sit amet',
                'fecha' => 1616724884,
            ],
        ];
        parent::init();
    }
}
