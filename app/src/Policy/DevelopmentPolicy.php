<?php
declare(strict_types=1);

namespace App\Policy;

use App\Authorization\AccessControl;
use App\Authorization\RoleAssignmentManager;
use App\Model\Entity\Development;
use Authorization\IdentityInterface;

/**
 * Entity-level rules for one development.
 */
class DevelopmentPolicy
{
    private const MANAGE = 'developments.manage';

    /**
     * @param \App\Authorization\RoleAssignmentManager $manager Role-assignment rules.
     * @param \App\Authorization\AccessControl $access Scope resolver.
     */
    public function __construct(
        private RoleAssignmentManager $manager = new RoleAssignmentManager(),
        private AccessControl $access = new AccessControl(),
    ) {
    }

    /**
     * May the user see and change who holds which role in this development (`role_assignments.manage`)?
     *
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \App\Model\Entity\Development $development Development.
     * @return bool
     */
    public function canManageRoleAssignments(IdentityInterface $user, Development $development): bool
    {
        return $this->manager->canManage((int)$user->getIdentifier(), (int)$development->id);
    }

    /**
     * May the user edit this development (`developments.manage`)? No role holds it: only the super-administrator.
     *
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \App\Model\Entity\Development $development Development.
     * @return bool
     */
    public function canEdit(IdentityInterface $user, Development $development): bool
    {
        return $this->canManage($user, $development);
    }

    /**
     * May the user archive this development (`developments.manage`)? Same rule as editing it.
     *
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \App\Model\Entity\Development $development Development.
     * @return bool
     */
    public function canArchive(IdentityInterface $user, Development $development): bool
    {
        return $this->canManage($user, $development);
    }

    /**
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \App\Model\Entity\Development $development Development.
     * @return bool
     */
    private function canManage(IdentityInterface $user, Development $development): bool
    {
        return $this->access
            ->allowedFor((int)$user->getIdentifier(), self::MANAGE)
            ->allows((int)$development->id);
    }
}
