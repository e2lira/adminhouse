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
    public const MANAGE = 'developments.manage';
    public const UNITS_MANAGE = 'units.manage';
    public const PEOPLE_MANAGE = 'people.manage';
    public const OCCUPANCIES_MANAGE = 'occupancies.manage';

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
     * May the user create, change or end units and sections here (`units.manage`)? Never in an archived development.
     *
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \App\Model\Entity\Development $development Development, loaded with `archived_at`.
     * @return bool
     */
    public function canManageUnits(IdentityInterface $user, Development $development): bool
    {
        return $this->canWriteDomain($user, $development, self::UNITS_MANAGE);
    }

    /**
     * May the user create or change people here (`people.manage`)? Never in an archived development.
     *
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \App\Model\Entity\Development $development Development, loaded with `archived_at`.
     * @return bool
     */
    public function canManagePeople(IdentityInterface $user, Development $development): bool
    {
        return $this->canWriteDomain($user, $development, self::PEOPLE_MANAGE);
    }

    /**
     * May the user create or end occupancies here (`occupancies.manage`)? Never in an archived development.
     *
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \App\Model\Entity\Development $development Development, loaded with `archived_at`.
     * @return bool
     */
    public function canManageOccupancies(IdentityInterface $user, Development $development): bool
    {
        return $this->canWriteDomain($user, $development, self::OCCUPANCIES_MANAGE);
    }

    /**
     * W-E: an archived development takes no writes to its units, people or occupancies, the super-administrator
     * included: they unarchive first. A development read without its archive state is refused (fail closed).
     *
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \App\Model\Entity\Development $development Development.
     * @param string $permission Permission code of the write.
     * @return bool
     */
    private function canWriteDomain(IdentityInterface $user, Development $development, string $permission): bool
    {
        if ($this->isArchivedOrUnknown($development)) {
            return false;
        }

        return $this->access
            ->allowedFor((int)$user->getIdentifier(), $permission)
            ->allows((int)$development->id);
    }

    /**
     * The stored archive state decides, whatever happened to the entity in memory since it was read: the original
     * value counts, and so does the current one. Fail closed when the field was never loaded. Original values are
     * used because, unlike `toArray()`, they include hidden fields.
     *
     * @param \App\Model\Entity\Development $development Development.
     * @return bool True when archived, or when the archive state is unknown.
     */
    private function isArchivedOrUnknown(Development $development): bool
    {
        $original = $development->getOriginalValues();

        return !array_key_exists('archived_at', $original)
            || $original['archived_at'] !== null
            || $development->get('archived_at') !== null;
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
