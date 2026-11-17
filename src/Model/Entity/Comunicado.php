<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Comunicado Entity
 *
 * @property int $id_comunicado
 * @property string $titulo
 * @property string $comunicado
 * @property string|null $dir
 * @property string|null $adjunto
 * @property int|null $id_condominio
 */
class Comunicado extends Entity
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
        'comunicado' => true,
        'dir' => true,
        'adjunto' => true,
        'id_condominio' => true,
    ];
}
