<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CondominioFixture
 */
class CondominioFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'condominio';
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id_condominio' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
        'nombre_condominio' => ['type' => 'string', 'length' => 100, 'null' => false, 'default' => null],
        'logo' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null],
        'dir' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null],
        'direccion_condominio' => ['type' => 'string', 'length' => 100, 'null' => false, 'default' => null],
        'telefono_condominio' => ['type' => 'string', 'length' => 100, 'null' => false, 'default' => null],
        'no_cuenta_condominio' => ['type' => 'string', 'length' => 150, 'null' => true, 'default' => null],
        'banco_cuenta' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'rfc' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id_condominio'], 'length' => []],
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
                'id_condominio' => 1,
                'nombre_condominio' => 'Lorem ipsum dolor sit amet',
                'logo' => 'Lorem ipsum dolor sit amet',
                'direccion_condominio' => 'Lorem ipsum dolor sit amet',
                'telefono_condominio' => 'Lorem ipsum dolor sit amet',
                'no_cuenta_condominio' => 'Lorem ipsum dolor sit amet',
                'banco_cuenta' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
