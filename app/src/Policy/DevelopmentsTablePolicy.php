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
 * `canIndex` refuses a user who holds the permission nowhere (403); `scopeIndex` then narrows the query
 * to the developments they may see. Each method asks AccessControl, one query each.
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
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \Cake\ORM\Query\SelectQuery<mixed> $query Query to narrow.
     * @return \Cake\ORM\Query\SelectQuery<mixed>
     */
    public function scopeIndex(IdentityInterface $user, SelectQuery $query): SelectQuery
    {
        $scope = $this->scopeFor($user);
        if ($scope->isAll()) {
            return $query;
        }

        $alias = $query->getRepository()->getAlias();

        // "WHERE 1 = 0" when empty: an empty IN () list is not valid SQL.
        return $scope->isEmpty()
            ? $query->where('1 = 0')
            : $query->where([$alias . '.id IN' => $scope->ids()]);
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
