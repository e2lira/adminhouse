<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Cuota Entity
 *
 * @property int $id_cuotas
 * @property int $id_condominio
 * @property int $id_condomino
 * @property int $id_tipo
 * @property float $cuota
 * @property string $cuota_letra
 * @property \Cake\I18n\FrozenDate $fecha_cargo
 */
class Cuota extends Entity
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
        'cuota' => true,
        'cuota_letra' => true,
        'fecha_cargo' => true,
        'concepto' => true,
        'fecha' => true
    ];
}
