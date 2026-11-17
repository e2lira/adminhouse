<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Gasto Entity
 *
 * @property int $id_gasto
 * @property int $id_tipo
 * @property float $gasto
 * @property string $monto_letra
 * @property string $concepto
 * @property \Cake\I18n\FrozenTime $fecha_pago
 * @property \Cake\I18n\FrozenDate $fecha
 * @property string $referencia
 * @property string $recibo
 */
class Gasto extends Entity
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
        'id_tipo' => true,
        'id_proveedores' => true,
        'id_condominio' => true,
        'id_categoria' => true,
        'gasto' => true,
        'monto_letra' => true,
        'concepto' => true,
        'fecha_pago' => true,
        'fecha' => true,
        'referencia' => true,
        'recibo' => true,
        'confirmacion' => true
    ];
}
