<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Folio Entity
 *
 * @property int $id
 * @property int $id_condominio
 * @property int $serie
 * @property int $incremento
 * @property \Cake\I18n\FrozenTime $fecha_alta
 */
class Folio extends Entity
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
        'serie' => true,
        'incremento' => true,
        'fecha_alta' => true
    ];
}
