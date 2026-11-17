<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Formulario Entity
 *
 * @property int $id_formulario
 * @property \Cake\I18n\FrozenTime $fecha_creacion
 * @property string $nombre_elaboro
 * @property int $id_condominio
 * @property bool $estancia
 * @property string $imagen_estancia
 * @property string $observaciones_estancia
 * @property bool $escalera
 * @property string $observaciones_escalera
 * @property string $imagen_escalera
 * @property bool $pasillo
 * @property string $imagen_pasillo
 * @property string $observaciones_pasillo
 * @property bool $elevadores
 * @property string $observaciones_elevadores
 * @property bool $planta_luz
 * @property string $imagen_planta_luz
 * @property string $observaciones_planta_luz
 * @property bool $cisterna
 * @property string $imagen_cisterna
 * @property string $observaciones_cisterna
 * @property bool $bomba
 * @property string $imagen_bomba
 * @property string $observaciones_bomba
 * @property bool $estacionamiento
 * @property string $imagen_estacionamiento
 * @property string $observaciones_estacionamiento
 * @property bool $caldera
 * @property string $imagen_caldera
 * @property string $observaciones_caldera
 * @property bool $gimnasio
 * @property string $imagen_gimnasio
 * @property string $observaciones_gimnasio
 * @property bool $salon
 * @property string $imagen_salon
 * @property string $observaciones_salon
 * @property bool $roof
 * @property string $imagen_roof
 * @property string $observaciones_roof
 */
class Formulario extends Entity
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
        'fecha_creacion' => true,
        'nombre_elaboro' => true,
        'id_condominio' => true,
        'estancia' => true,
        'imagen_estancia' => true,
        'observaciones_estancia' => true,
        'escalera' => true,
        'observaciones_escalera' => true,
        'imagen_escalera' => true,
        'pasillo' => true,
        'imagen_pasillo' => true,
        'observaciones_pasillo' => true,
        'elevadores' => true,
        'observaciones_elevadores' => true,
        'planta_luz' => true,
        'imagen_planta_luz' => true,
        'observaciones_planta_luz' => true,
        'cisterna' => true,
        'imagen_cisterna' => true,
        'observaciones_cisterna' => true,
        'bomba' => true,
        'imagen_bomba' => true,
        'observaciones_bomba' => true,
        'estacionamiento' => true,
        'imagen_estacionamiento' => true,
        'observaciones_estacionamiento' => true,
        'caldera' => true,
        'imagen_caldera' => true,
        'observaciones_caldera' => true,
        'gimnasio' => true,
        'imagen_gimnasio' => true,
        'observaciones_gimnasio' => true,
        'salon' => true,
        'imagen_salon' => true,
        'observaciones_salon' => true,
        'roof' => true,
        'imagen_roof' => true,
        'observaciones_roof' => true
    ];
}
