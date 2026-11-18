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
 */
class Development extends Entity
{
}
