<?php
declare(strict_types=1);

namespace App\Policy;

use App\Authorization\AccessControl;
use App\Authorization\DevelopmentScope;
use App\Model\Entity\Development;
use Authorization\IdentityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * The rules shared by the policies of a managed domain (units, people): reading follows the domain's `.view`
 * permission, writing follows its `.manage` permission and the archive state of the development (W-E).
 *
 * The development a decision is about always arrives as an id or a route string and is normalized first: anything that
 * is not a canonical positive integer is refused, so type juggling never decides access.
 */
abstract class DomainAccess
{
    use LocatorAwareTrait;

    protected DevelopmentPolicy $developments;

    /**
     * @param \App\Authorization\AccessControl $access Scope resolver.
     */
    public function __construct(protected AccessControl $access = new AccessControl())
    {
        $this->developments = new DevelopmentPolicy(access: $access);
    }

    /**
     * @return string Permission that lets the user read the domain.
     */
    abstract protected function viewCode(): string;

    /**
     * @return string Permission that lets the user write the domain.
     */
    abstract protected function manageCode(): string;

    /**
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \App\Model\Entity\Development $development Development, loaded with `archived_at`.
     * @return bool Whether the development policy lets the user write the domain there (never when archived).
     */
    abstract protected function mayWriteIn(IdentityInterface $user, Development $development): bool;

    /**
     * @param \Authorization\IdentityInterface $user Identity.
     * @return \App\Authorization\DevelopmentScope Developments whose rows the user may see.
     */
    public function viewScope(IdentityInterface $user): DevelopmentScope
    {
        return $this->access->allowedFor((int)$user->getIdentifier(), $this->viewCode());
    }

    /**
     * @param \Authorization\IdentityInterface $user Identity.
     * @return \App\Authorization\DevelopmentScope Developments whose rows the user may change.
     */
    public function manageScope(IdentityInterface $user): DevelopmentScope
    {
        return $this->access->allowedFor((int)$user->getIdentifier(), $this->manageCode());
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
     * May the user create or change a row of this development? The development is read under the user's own manage
     * scope, with its archive state, and the development policy decides: an archived one refuses all.
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

        return $development instanceof Development && $this->mayWriteIn($user, $development);
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
}
