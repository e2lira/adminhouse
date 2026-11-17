<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Proveedore Entity
 *
 * @property int $id_proveedores
 * @property string $nom_proveedor
 * @property int $no_cuenta_proveedor
 * @property string $rubro_proveedor
 * @property string $nota
 */
class Proveedore extends Entity
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
        'nom_proveedor' => true,
        'no_cuenta_proveedor' => true,
        'rubro_proveedor' => true,
        'nota' => true
    ];
}
