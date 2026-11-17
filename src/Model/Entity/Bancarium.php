<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Bancarium Entity
 *
 * @property int $id_bancaria
 * @property int $id_condominio
 * @property string $nombre_banco
 * @property int $no_cuenta
 * @property string $estatus
 */
class Bancarium extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'id_condominio' => true,
        'nombre_banco' => true,
        'no_cuenta' => true,
        'estatus' => true
    ];
}
