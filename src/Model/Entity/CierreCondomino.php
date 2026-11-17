<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * CierreCondomino Entity
 *
 * @property int $id_cierre
 * @property int|null $id_condomino
 * @property string|null $saldo
 * @property string|null $fecha
 */
class CierreCondomino extends Entity
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
        'id_condomino' => true,
        'saldo' => true,
        'fecha' => true,
    ];
}
