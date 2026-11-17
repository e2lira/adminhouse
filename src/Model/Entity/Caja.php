<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Caja Entity
 *
 * @property int $id_gasto
 * @property int|null $id_condominio
 * @property string|null $beneficiario
 * @property string|null $id_tipo
 * @property string|null $id_categoria
 * @property \Cake\I18n\FrozenTime|null $fecha_pago
 * @property \Cake\I18n\FrozenDate|null $fecha
 * @property string|null $recibo
 */
class Caja extends Entity
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
        'beneficiario' => true,
        'id_tipo' => true,
        'id_categoria' => true,
        'fecha_pago' => true,
        'fecha' => true,
        'recibo' => true,
    ];
}
