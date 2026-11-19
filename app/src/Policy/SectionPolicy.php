<?php
declare(strict_types=1);

namespace App\Policy;

use App\Authorization\AccessControl;
use App\Model\Entity\Section;
use Authorization\IdentityInterface;

/**
 * Entity-level rules for one section: see it with `units.view`, create or change it with `units.manage` in a
 * development that is not archived. There is no delete rule: the application never deletes a section; only the
 * super-administrator does, at the database.
 */
class SectionPolicy
{
    private UnitsAccess $units;

    /**
     * @param \App\Authorization\AccessControl $access Scope resolver.
     */
    public function __construct(AccessControl $access = new AccessControl())
    {
        $this->units = new UnitsAccess($access);
    }

    /**
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \App\Model\Entity\Section $section Section.
     * @return bool
     */
    public function canView(IdentityInterface $user, Section $section): bool
    {
        return $this->units->canView($user, $this->units->developmentOf($section));
    }

    /**
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \App\Model\Entity\Section $section Section, already carrying its development.
     * @return bool
     */
    public function canAdd(IdentityInterface $user, Section $section): bool
    {
        return $this->units->canWrite($user, $this->units->developmentOf($section));
    }

    /**
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \App\Model\Entity\Section $section Section.
     * @return bool
     */
    public function canEdit(IdentityInterface $user, Section $section): bool
    {
        return $this->units->canWrite($user, $this->units->developmentOf($section));
    }
}
