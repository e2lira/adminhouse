<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Respuesta Entity
 *
 * @property int $id_respuestas
 * @property int|null $id_mensaje
 * @property string|null $mensaje
 * @property string|null $dir
 * @property string|null $adjunto
 * @property int|null $id_condominio
 * @property int|null $id_condomino
 */
class Respuesta extends Entity
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
        'id_mensaje' => true,
        'mensaje' => true,
        'dir' => true,
        'adjunto' => true,
        'id_condominio' => true,
        'id_condomino' => true,
    ];
}
