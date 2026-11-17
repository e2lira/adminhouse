<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Mensaje Entity
 *
 * @property int $id_mensaje
 * @property string|null $titulo
 * @property string|null $mensaje
 * @property string|null $dir
 * @property string|null $adjunto
 * @property \Cake\I18n\FrozenTime|null $fecha
 * @property int|null $id_condominio
 * @property int|null $id_condomino
 */
class Mensaje extends Entity
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
        'titulo' => true,
        'mensaje' => true,
        'dir' => true,
        'adjunto' => true,
        'fecha' => true,
        'id_condominio' => true,
        'id_condomino' => true,
        'estatus' => true
    ];
}
