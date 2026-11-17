<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ExcelFixture
 */
class ExcelFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'excel';
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id_excel' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
        'dir' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null],
        'archivo' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null],
        'id_user' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id_excel'], 'length' => []],
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
                'id_excel' => 1,
                'dir' => 'Lorem ipsum dolor sit amet',
                'archivo' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
