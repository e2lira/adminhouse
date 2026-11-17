<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Visita Entity
 *
 * @property int $id_visita
 * @property int|null $id_condominio
 * @property int|null $id_condomino
 * @property string $nombre
 * @property string|null $departamento
 * @property \Cake\I18n\FrozenDate $fecha
 * @property \Cake\I18n\FrozenTime $hora
 * @property string $tipo_visita
 * @property string|null $auto
 * @property string|null $color
 * @property string|null $marca
 * @property string|null $placas
 */
class Visita extends Entity
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
        'nombre' => true,
        'departamento' => true,
        'fecha' => true,
        'hora' => true,
        'tipo_visita' => true,
        'auto' => true,
        'color' => true,
        'marca' => true,
        'placas' => true,
    ];
}
