<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RespuestasFixture
 */
class RespuestasFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id_respuestas' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
        'id_mensaje' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'mensaje' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'dir' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'adjunto' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'id_condominio' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'id_condomino' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'estatus' => ['type' => 'boolean', 'length' => null, 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id_respuestas'], 'length' => []],
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
                'id_respuestas' => 1,
                'id_mensaje' => 1,
                'mensaje' => 'Lorem ipsum dolor sit amet',
                'dir' => 'Lorem ipsum dolor sit amet',
                'adjunto' => 'Lorem ipsum dolor sit amet',
                'id_condominio' => 1,
                'id_condomino' => 1,
            ],
        ];
        parent::init();
    }
}
