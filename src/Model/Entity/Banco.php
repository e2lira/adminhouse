<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Banco Entity
 *
 * @property int $id_movimiento
 * @property int $id_condominio
 * @property int $id_pago
 * @property int $id_cargo
 * @property float $cargo
 * @property float $abono
 * @property \Cake\I18n\FrozenTime $fecha
 * @property string $nombre_banco
 * @property int $no_cuenta
 */
class Banco extends Entity
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
        'id_pago' => true,
        'id_cargo' => true,
        'cargo' => true,
        'abono' => true,
        'fecha' => true,
        'nombre_banco' => true,
        'no_cuenta' => true
    ];
}
