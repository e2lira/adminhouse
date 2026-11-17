<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Condominio Entity
 *
 * @property int $id_condominio
 * @property string $nombre_condominio
 * @property string $logo
 * @property string $direccion_condominio
 * @property string $telefono_condominio
 * @property string $no_cuenta_condominio
 * @property string $banco_cuenta
 * @property string $qr
 * @property bool $roof
 * @property bool $salon
 * @property bool $alberca
 * @property bool $gimnasio
 * @property bool $ludoteca
 * @property bool $spa
 * @property bool $business_center
 * @property string $reglamento_condominio
 */
class Condominio extends Entity
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
        'nombre_condominio' => true,
        'logo' => true,
        'direccion_condominio' => true,
        'telefono_condominio' => true,
        'rfc' => true
    ];
}
