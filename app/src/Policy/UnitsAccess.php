<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Development;
use Authorization\IdentityInterface;
use Cake\Datasource\EntityInterface;

/**
 * The rules shared by the unit and section policies: reading follows `units.view`, writing follows `units.manage`
 * and the archive state of the development (`DevelopmentPolicy::canManageUnits`, W-E). Units and sections are one
 * domain, so they answer the same way.
 */
class UnitsAccess extends DomainAccess
{
    public const VIEW = 'units.view';

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
        return DevelopmentPolicy::UNITS_MANAGE;
    }

    /**
     * @inheritDoc
     */
    protected function mayWriteIn(IdentityInterface $user, Development $development): bool
    {
        return $this->developments->canManageUnits($user, $development);
    }

    /**
     * The development a row is judged by: the stored one for a row read from the database (a changed value in memory
     * must not change the decision), the current one for a row not yet saved.
     *
     * @param \Cake\Datasource\EntityInterface $row Unit or section.
     * @return mixed
     */
    public function developmentOf(EntityInterface $row): mixed
    {
        return $row->isNew() ? $row->get('development_id') : $row->getOriginal('development_id');
    }
}
