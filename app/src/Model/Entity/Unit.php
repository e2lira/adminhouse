<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * An apartment, house or lot of a development. Entity-level policies (App\Policy\UnitPolicy) hang from this class.
 *
 * @property int $id
 * @property int $development_id
 * @property int|null $section_id
 * @property string $number
 * @property string $type
 * @property string|null $lot_area_m2
 * @property string|null $indiviso_pct
 * @property bool $is_active
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \App\Model\Entity\Section|null $section
 */
class Unit extends Entity
{
    /**
     * Only the editable fields are assignable from a request. The key, the development (set from the save scope) and
     * the timestamps are not.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => false,
        'section_id' => true,
        'number' => true,
        'type' => true,
        'lot_area_m2' => true,
        'indiviso_pct' => true,
        'is_active' => true,
    ];
}
