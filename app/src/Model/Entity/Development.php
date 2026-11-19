<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * A condominium or subdivision. Entity-level policies (App\Policy\DevelopmentPolicy) hang from this class.
 *
 * @property int $id
 * @property string $type
 * @property string $name
 * @property string $section_label
 * @property string $number_label
 * @property string $timezone
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $rfc
 * @property string|null $bank_name
 * @property string|null $bank_account
 * @property \Cake\I18n\DateTime|null $archived_at
 */
class Development extends Entity
{
    /**
     * Only the ten editable fields are assignable from a request. The key, the archive state (its own action) and the
     * timestamps are not.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => false,
        'type' => true,
        'name' => true,
        'section_label' => true,
        'number_label' => true,
        'timezone' => true,
        'address' => true,
        'phone' => true,
        'rfc' => true,
        'bank_name' => true,
        'bank_account' => true,
    ];
}
