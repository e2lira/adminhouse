<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Condomino Entity
 *
 * @property int $id_condomino
 * @property int $id_condominio
 * @property string $numero_departamento
 * @property string $residente_condominio
 * @property string $numero_contacto
 * @property string $correo_contacto
 * @property string $qr
 * @property string $nota
 * @property string $arrendatario
 * @property string $telefono_arrendatario
 * @property string $marca_auto
 * @property string $modelo_auto
 * @property string $color_auto
 * @property string $placas_auto
 */
class Condomino extends Entity
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
        'numero_departamento' => true,
        'saldo_inicial'=>true,
        'cuota'=>true,
        'residente_condominio' => true,
        'numero_contacto' => true,
        'correo_contacto' => true,
        'qr' => true,
        'nota' => true,
        'arrendatario' => true,
        'telefono_arrendatario' => true,
        'marca_auto' => true,
        'modelo_auto' => true,
        'color_auto' => true,
        'placas_auto' => true,
        'rfc' => true,
        'correo_prop' => true,
        'correo_prop2' => true,
        'celular' => true,
        'celular_adicional' => true,
        'estatus'=> true
    ];
}
