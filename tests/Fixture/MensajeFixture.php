<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * MensajeFixture
 */
class MensajeFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'mensaje';
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id_mensaje' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
        'titulo' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'mensaje' => ['type' => 'text', 'length' => null, 'null' => true, 'default' => null],
        'dir' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null],
        'adjunto' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null],
        'fecha' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => 'CURRENT_TIMESTAMP'],
        'id_condominio' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'id_condomino' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'estatus' => ['type' => 'boolean', 'length' => null, 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id_mensaje'], 'length' => []],
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
                'id_mensaje' => 1,
                'titulo' => 'Lorem ipsum dolor sit amet',
                'mensaje' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'dir' => 'Lorem ipsum dolor sit amet',
                'adjunto' => 'Lorem ipsum dolor sit amet',
                'fecha' => '2021-06-03 14:28:06',
                'id_condominio' => 1,
                'id_condomino' => 1,
            ],
        ];
        parent::init();
    }
}
