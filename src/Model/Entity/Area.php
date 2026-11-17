<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Area Entity
 *
 * @property int $id_area
 * @property int|null $id_supervision
 * @property int|null $id_condominio
 * @property string $concepto
 * @property string|null $estado
 * @property string|null $imagen
 * @property string|null $url
 * @property string|null $observaciones
 * @property \Cake\I18n\FrozenTime|null $fecha
 */
class Area extends Entity
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
        'id_supervision' => true,
        'id_condominio' => true,
        'concepto' => true,
        'estado' => true,
        'imagen' => true,
        'url' => true,
        'observaciones' => true,
        'fecha' => true,
    ];
}
