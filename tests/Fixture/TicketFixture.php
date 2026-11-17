<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TicketFixture
 */
class TicketFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'ticket';
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id_ticket' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
        'id_condominio' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'id_condomino' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        'nombre' => ['type' => 'string', 'length' => 145, 'null' => true, 'default' => null],
        'prioridad' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'asunto' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => null],
        'mensaje' => ['type' => 'text', 'length' => null, 'null' => true, 'default' => null],
        'dir' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null],
        'adjunto' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null],
        'estatus' => ['type' => 'string', 'length' => 45, 'null' => true, 'default' => 'Activo'],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id_ticket'], 'length' => []],
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
                'id_ticket' => 1,
                'id_condominio' => 1,
                'id_condomino' => 1,
                'nombre' => 'Lorem ipsum dolor sit amet',
                'prioridad' => 'Lorem ipsum dolor sit amet',
                'asunto' => 'Lorem ipsum dolor sit amet',
                'mensaje' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'dir' => 'Lorem ipsum dolor sit amet',
                'adjunto' => '',
            ],
        ];
        parent::init();
    }
}
