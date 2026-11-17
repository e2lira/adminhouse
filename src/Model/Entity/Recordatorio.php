<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Recordatorio Entity
 *
 * @property int $id_recordatorio
 * @property string|null $destinatarios
 * @property string|null $asunto
 * @property string|null $mensaje
 * @property string|null $adjunto
 * @property string|null $dir
 */
class Recordatorio extends Entity
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
        'destinatarios' => true,
        'asunto' => true,
        'mensaje' => true,
        'adjunto' => true,
        'dir' => true,
    ];
}
