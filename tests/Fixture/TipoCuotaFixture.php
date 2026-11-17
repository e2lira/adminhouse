<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TipoCuotaFixture
 */
class TipoCuotaFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'tipo_cuota';
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id_tipo' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
        'tipo_cuota' => ['type' => 'string', 'length' => 30, 'null' => false, 'default' => null],
        'id_condominio' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id_tipo'], 'length' => []],
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
                'id_tipo' => 1,
                'tipo_cuota' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
