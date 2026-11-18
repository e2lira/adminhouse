<?php
declare(strict_types=1);

namespace App\Policy;

use App\Authorization\RoleAssignmentManager;
use App\Model\Entity\Development;
use Authorization\IdentityInterface;

/**
 * Entity-level rules for one development.
 */
class DevelopmentPolicy
{
    /**
     * @param \App\Authorization\RoleAssignmentManager $manager Role-assignment rules.
     */
    public function __construct(private RoleAssignmentManager $manager = new RoleAssignmentManager())
    {
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
}
