<?php
declare(strict_types=1);

namespace App\Policy;

use App\Authorization\AccessControl;
use App\Authorization\DevelopmentScope;
use App\Model\Entity\Development;
use Authorization\IdentityInterface;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * The rules shared by the unit and section policies: reading follows `units.view`, writing follows `units.manage`
 * and the archive state of the development (`DevelopmentPolicy::canManageUnits`, W-E). Units and sections are one
 * domain, so they answer the same way.
 */
class UnitsAccess
{
    use LocatorAwareTrait;

    public const VIEW = 'units.view';

    private DevelopmentPolicy $developments;

    /**
     * @param \App\Authorization\AccessControl $access Scope resolver.
     */
    public function __construct(private AccessControl $access = new AccessControl())
    {
        $this->developments = new DevelopmentPolicy(access: $access);
    }

    /**
     * @param \Authorization\IdentityInterface $user Identity.
     * @return \App\Authorization\DevelopmentScope Developments whose units the user may see.
     */
    public function viewScope(IdentityInterface $user): DevelopmentScope
    {
        return $this->access->allowedFor((int)$user->getIdentifier(), self::VIEW);
    }

    /**
     * @param \Authorization\IdentityInterface $user Identity.
     * @return \App\Authorization\DevelopmentScope Developments whose units the user may change.
     */
    public function manageScope(IdentityInterface $user): DevelopmentScope
    {
        return $this->access->allowedFor((int)$user->getIdentifier(), DevelopmentPolicy::UNITS_MANAGE);
    }

    /**
     * @param \Authorization\IdentityInterface $user Identity.
     * @param mixed $developmentId Development of the row, as an id or a route string; anything else is refused.
     * @return bool
     */
    public function canView(IdentityInterface $user, mixed $developmentId): bool
    {
        $id = self::normalize($developmentId);

        return $id !== null && $this->viewScope($user)->allows($id);
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

    /**
     * @param mixed $value Candidate development id.
     * @return int|null A positive integer id, or null for anything else (type juggling never decides access).
     */
    private static function normalize(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        return is_string($value) && preg_match('/^[1-9][0-9]*$/', $value) === 1 && (string)(int)$value === $value
            ? (int)$value
            : null;
    }

    /**
     * May the user create or change a row of this development? The development is read under the user's own
     * `units.manage` scope, with its archive state, and the development policy decides: an archived one refuses all.
     *
     * @param \Authorization\IdentityInterface $user Identity.
     * @param mixed $developmentId Development of the row, as an id or a route string; anything else is refused.
     * @return bool
     */
    public function canWrite(IdentityInterface $user, mixed $developmentId): bool
    {
        $developmentId = self::normalize($developmentId);
        if ($developmentId === null) {
            return false;
        }
        $scope = $this->manageScope($user)->intersect(DevelopmentScope::only([$developmentId]));
        if ($scope->isEmpty()) {
            return false;
        }

        /** @var \App\Model\Entity\Development|null $development */
        $development = $this->fetchTable('Developments')
            ->find('all', developmentScope: $scope)
            ->select(['Developments.id', 'Developments.archived_at'])
            ->where(['Developments.id' => $developmentId])
            ->first();

        return $development instanceof Development && $this->developments->canManageUnits($user, $development);
    }
}
