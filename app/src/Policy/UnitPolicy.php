<?php
declare(strict_types=1);

namespace App\Policy;

use App\Authorization\AccessControl;
use App\Model\Entity\Unit;
use Authorization\IdentityInterface;

/**
 * Entity-level rules for one unit: see it with `units.view`, create or change it with `units.manage` in a
 * development that is not archived. There is no delete rule: units are deactivated (`is_active`), never deleted by
 * the application.
 */
class UnitPolicy
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
     * @param \App\Model\Entity\Unit $unit Unit.
     * @return bool
     */
    public function canView(IdentityInterface $user, Unit $unit): bool
    {
        return $this->units->canView($user, $unit->get('development_id'));
    }

    /**
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \App\Model\Entity\Unit $unit Unit, already carrying its development.
     * @return bool
     */
    public function canAdd(IdentityInterface $user, Unit $unit): bool
    {
        return $this->units->canWrite($user, $unit->get('development_id'));
    }

    /**
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \App\Model\Entity\Unit $unit Unit.
     * @return bool
     */
    public function canEdit(IdentityInterface $user, Unit $unit): bool
    {
        return $this->units->canWrite($user, $unit->get('development_id'));
    }
}
