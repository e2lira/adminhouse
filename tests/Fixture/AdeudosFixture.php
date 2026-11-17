<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AdeudosFixture
 */
class AdeudosFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id_adeudo' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
        'id_condominio' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null],
        'id_condomino' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'id_tipo' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'mes' => ['type' => 'date', 'length' => null, 'null' => true, 'default' => null],
        'monto' => ['type' => 'decimal', 'length' => 19, 'precision' => 2, 'unsigned' => false, 'null' => false, 'default' => null],
        'concepto' => ['type' => 'string', 'length' => 45, 'null' => false, 'default' => null],
        'fecha' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => 'CURRENT_TIMESTAMP'],
        'estatus' => ['type' => 'boolean', 'length' => null, 'null' => true, 'default' => true],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id_adeudo'], 'length' => []],
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
                'id_adeudo' => 1,
                'id_condominio' => 1,
                'id_condomino' => 1,
                'mes' => '2021-03-03 18:48:00',
                'monto' => 1,
                'concepto' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
