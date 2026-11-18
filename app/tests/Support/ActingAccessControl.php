<?php
declare(strict_types=1);

namespace App\Test\Support;

use App\Authorization\AccessControl;
use App\Authorization\DevelopmentScope;

/**
 * AccessControl that runs each question inside a session of the user it asks about (see ActsAsSessionUserTrait).
 */
class ActingAccessControl extends AccessControl
{
    use ActsAsSessionUserTrait;

    /**
     * @inheritDoc
     */
    public function allowedFor(int $userId, string $permission): DevelopmentScope
    {
        return $this->asSessionUser($userId, fn(): DevelopmentScope => parent::allowedFor($userId, $permission));
    }
}
