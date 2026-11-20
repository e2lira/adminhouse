<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Development;
use Authorization\IdentityInterface;

/**
 * The rules shared by the people policies: reading follows `people.view` (a resident-only user holds none, so is
 * refused, RFC included), writing follows `people.manage` and the archive state of the development
 * (`DevelopmentPolicy::canManagePeople`, W-E).
 */
class PeopleAccess extends DomainAccess
{
    public const VIEW = 'people.view';

    /**
     * @inheritDoc
     */
    protected function viewCode(): string
    {
        return self::VIEW;
    }

    /**
     * @inheritDoc
     */
    protected function manageCode(): string
    {
        return DevelopmentPolicy::PEOPLE_MANAGE;
    }

    /**
     * @inheritDoc
     */
    protected function mayWriteIn(IdentityInterface $user, Development $development): bool
    {
        return $this->developments->canManagePeople($user, $development);
    }
}
