<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Pago Entity
 *
 * @property int $id_pago
 * @property int $id_condominio
 * @property int $id_condomino
 * @property float $abono
 * @property string $monto_letra
 * @property string $concepto
 * @property \Cake\I18n\FrozenTime $fecha_pago
 * @property \Cake\I18n\FrozenDate $fecha
 * @property string $referencia
 */
class Pago extends Entity
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
        'id_tipo' => true,
        'abono' => true,
        'bonificacion' => true,
        'monto_letra' => true,
        'concepto' => true,
        'fecha_pago' => true,
        'fecha' => true,
        'referencia' => true,
        'folio'=> true,
        'nombre_banco'=> true,
        'no_cuenta'=> true
    ];
}
