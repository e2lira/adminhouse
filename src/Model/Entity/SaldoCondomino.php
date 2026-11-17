<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SaldoCondomino Entity
 *
 * @property int $id_saldo
 * @property int|null $id_condominio
 * @property int|null $id_condomino
 * @property string|null $saldo
 * @property \Cake\I18n\FrozenTime|null $fecha
 */
class SaldoCondomino extends Entity
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
        'saldo' => true,
        'contra' => true,
        'fecha' => true,
    ];
}
