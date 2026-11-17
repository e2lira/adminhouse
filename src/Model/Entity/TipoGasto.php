<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TipoGasto Entity
 *
 * @property int $id_tipo
 * @property string $tipo_gasto
 * @property float $monto
 */
class TipoGasto extends Entity
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
        'tipo_gasto' => true,
        'monto' => true,
        'id_condominio' => true
    ];
}
