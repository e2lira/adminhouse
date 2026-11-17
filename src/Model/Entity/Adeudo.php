<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Adeudo Entity
 *
 * @property int $id_adeudo
 * @property int $id_condominio
 * @property int $id_condomino
 * @property \Cake\I18n\FrozenTime $mes
 * @property int $monto
 * @property string $concepto
 */
class Adeudo extends Entity
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
        'id_condomino' => true,
        'mes' => true,
        'monto' => true,
        'concepto' => true,
        'fecha' => true,
        'estatus' => true,
    ];
}
