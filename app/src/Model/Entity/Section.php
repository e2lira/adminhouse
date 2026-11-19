<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * A named group of units inside a development (a tower, a block, a street). The development's own label for it
 * (`developments.section_label`) is what screens show.
 *
 * @property int $id
 * @property int $development_id
 * @property string $name
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class Section extends Entity
{
    /**
     * Only the name is assignable from a request. The key and the development (set from the save scope) are not.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => false,
        'name' => true,
    ];
}
