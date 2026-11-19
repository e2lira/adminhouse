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
     * The key is never assigned from a request, and neither is the archive state: archiving is its own action.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => true,
        'id' => false,
        'archived_at' => false,
    ];
}
