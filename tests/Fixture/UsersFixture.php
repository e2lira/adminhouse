<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id_user' => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'username' => ['type' => 'string', 'length' => 50, 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'password' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'id_perfil' => ['type' => 'integer', 'length' => 10, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'email' => ['type' => 'string', 'length' => 100, 'null' => false, 'default' => null, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id_user'], 'length' => []],
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
                'id_user' => 1,
                'username' => 'profile-one',
                'password' => '$2y$10$FtRjfvAZUu43IQGFjRInx.NPZgD3niPw6NrMN16zbxFRAyexHwJXG',
                'id_perfil' => 1,
                'email' => 'profile-one@example.test',
                'created' => '2021-03-02 20:04:26',
                'modified' => '2021-03-02 20:04:26',
            ],
            [
                'id_user' => 2,
                'username' => 'profile-six',
                'password' => '$2y$10$FtRjfvAZUu43IQGFjRInx.NPZgD3niPw6NrMN16zbxFRAyexHwJXG',
                'id_perfil' => 6,
                'email' => 'profile-six@example.test',
                'created' => '2021-03-02 20:04:26',
                'modified' => '2021-03-02 20:04:26',
            ],
            [
                'id_user' => 3,
                'username' => 'profile-two',
                'password' => '$2y$10$FtRjfvAZUu43IQGFjRInx.NPZgD3niPw6NrMN16zbxFRAyexHwJXG',
                'id_perfil' => 2,
                'email' => 'profile-two@example.test',
                'created' => '2021-03-02 20:04:26',
                'modified' => '2021-03-02 20:04:26',
            ],
        ];
        parent::init();
    }
}
