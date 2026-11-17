<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CondominoFixture
 */
class CondominoFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'condomino';
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id_condomino' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
        'id_condominio' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null],
        'numero_departamento' => ['type' => 'string', 'length' => 45, 'null' => false, 'default' => null],
        'saldo_inicial' => ['type' => 'float', 'length' => null, 'unsigned' => false, 'null' => true, 'default' => null],
        'cuota' => ['type' => 'float', 'length' => null, 'unsigned' => false, 'null' => true, 'default' => null],
        'residente_condominio' => ['type' => 'string', 'length' => 150, 'null' => false, 'default' => null],
        'numero_contacto' => ['type' => 'string', 'length' => 150, 'null' => true, 'default' => null],
        'correo_contacto' => ['type' => 'string', 'length' => 150, 'null' => true, 'default' => null],
        'nota' => ['type' => 'text', 'length' => null, 'null' => true, 'default' => null],
        'arrendatario' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'telefono_arrendatario' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'marca_auto' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'modelo_auto' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'color_auto' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'placas_auto' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'rfc' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'correo_prop' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'correo_prop2' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'celular' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'celular_adicional' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'estatus' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => 'ACTIVO'],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id_condomino'], 'length' => []],
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
                'id_condomino' => 1,
                'id_condominio' => 1,
                'numero_departamento' => 'Lorem ipsum dolor sit amet',
                'saldo_inicial' => 1,
                'cuota' => 1,
                'residente_condominio' => 'Lorem ipsum dolor sit amet',
                'numero_contacto' => 'Lorem ipsum dolor sit amet',
                'correo_contacto' => 'Lorem ipsum dolor sit amet',
                'nota' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'arrendatario' => 'Lorem ipsum dolor sit amet',
                'telefono_arrendatario' => 'Lorem ipsum dolor sit amet',
                'marca_auto' => 'Lorem ipsum dolor sit amet',
                'modelo_auto' => 'Lorem ipsum dolor sit amet',
                'color_auto' => 'Lorem ipsum dolor sit amet',
                'placas_auto' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
