<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Favor Entity
 *
 * @property int $id
 * @property int|null $id_pago
 * @property int|null $id_condominio
 * @property int|null $id_condomino
 * @property float|null $abono
 * @property \Cake\I18n\FrozenDate|null $fecha
 * @property bool|null $estatus
 */
class Favor extends Entity
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
        'id_pago' => true,
        'id_condominio' => true,
        'id_condomino' => true,
        'abono' => true,
        'fecha' => true,
        'estatus' => true,
    ];
}
