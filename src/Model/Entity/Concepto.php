<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Concepto Entity
 *
 * @property int $id_conceptos
 * @property int|null $id_gasto
 * @property int|null $id_condominio
 * @property string|null $concept
 * @property string|null $gasto
 * @property string|null $monto_letra
 * @property \Cake\I18n\FrozenDate|null $fecha
 * @property \Cake\I18n\FrozenTime|null $fecha_pago
 * @property string|null $tipo
 */
class Concepto extends Entity
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
        'id_gasto' => true,
        'id_condominio' => true,
        'concept' => true,
        'gasto' => true,
        'monto_letra' => true,
        'fecha' => true,
        'fecha_pago' => true,
        'tipo' => true,
    ];
}
