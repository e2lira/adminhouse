<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Indiviso Entity
 *
 * @property int $id_cuotas
 * @property int|null $id_condominio
 * @property string|null $id_condomino
 * @property int|null $id_tipo
 * @property float $cuota
 * @property string $cuota_letra
 * @property string|null $concepto
 * @property \Cake\I18n\FrozenTime|null $fecha_cargo
 */
class Indiviso extends Entity
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
        'concepto' => true,
        'fecha_cargo' => true,
        'fecha' => true
    ];
}
