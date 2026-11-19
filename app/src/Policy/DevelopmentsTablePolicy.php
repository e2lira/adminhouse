<?php
declare(strict_types=1);

namespace App\Policy;

use App\Authorization\AccessControl;
use App\Authorization\DevelopmentScope;
use Authorization\IdentityInterface;
use Cake\ORM\Query\SelectQuery;

/**
 * Who may list developments, and which ones (`developments.view`, RB-R3).
 *
 * `canIndex` refuses a user who holds the permission nowhere (403); `scopeIndex` then sets the scope on the query,
 * so the table narrows it to the developments they may see. Each method asks AccessControl, one query each.
 */
class DevelopmentsTablePolicy
{
    private const VIEW = 'developments.view';

    /**
     * @param \App\Authorization\AccessControl $access Scope resolver.
     */
    public function __construct(private AccessControl $access = new AccessControl())
    {
    }

    /**
     * @param \Authorization\IdentityInterface $user Identity.
     * @param mixed $resource Table or query being listed.
     * @return bool
     */
    public function canIndex(IdentityInterface $user, mixed $resource): bool
    {
        return !$this->scopeFor($user)->isEmpty();
    }

    /**
     * May the user create a development (`developments.manage`)? Only an all-development scope can: a new
     * development belongs to no existing one. No role holds it; the super-administrator does everywhere.
     *
     * @param \Authorization\IdentityInterface $user Identity.
     * @param mixed $resource Table the development would be added to.
     * @return bool
     */
    public function canAdd(IdentityInterface $user, mixed $resource): bool
    {
        return $this->access->allowedFor((int)$user->getIdentifier(), DevelopmentPolicy::MANAGE)->isAll();
    }

    /**
     * Hands the user's scope to the query as the `developmentScope` option, which DevelopmentsTable enforces.
     *
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \Cake\ORM\Query\SelectQuery<mixed> $query Query to narrow.
     * @return \Cake\ORM\Query\SelectQuery<mixed>
     */
    public function scopeIndex(IdentityInterface $user, SelectQuery $query): SelectQuery
    {
        return $query->applyOptions(['developmentScope' => $this->scopeFor($user)]);
    }

    /**
     * @param \Authorization\IdentityInterface $user Identity.
     * @return \App\Authorization\DevelopmentScope
     */
    private function scopeFor(IdentityInterface $user): DevelopmentScope
    {
        return $this->access->allowedFor((int)$user->getIdentifier(), self::VIEW);
    }
}
