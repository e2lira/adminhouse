<?php
declare(strict_types=1);

namespace App\Policy;

use App\Authorization\AccessControl;
use Authorization\IdentityInterface;
use Cake\ORM\Query\SelectQuery;

/**
 * Who may list sections, and in which developments: whoever holds `units.view` somewhere (a resident does not), and
 * the list is narrowed to those developments. Adding is offered to whoever holds `units.manage` somewhere; the entity
 * policy then decides for the development the row belongs to.
 */
class SectionsTablePolicy
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
     * @param mixed $resource Table or query being listed.
     * @return bool
     */
    public function canIndex(IdentityInterface $user, mixed $resource): bool
    {
        return !$this->units->viewScope($user)->isEmpty();
    }

    /**
     * Only a gate for the interface (show the add button, answer a bare 403): the table cannot know the development.
     * It is NOT an authorization to save. A controller MUST authorize the entity itself before saving, with its
     * development set from the route: `authorize($entity, 'add')` (entity policy), and save under the
     * `units.manage` scope, never the view scope.
     *
     * @param \Authorization\IdentityInterface $user Identity.
     * @param mixed $resource Table the row would be added to.
     * @return bool
     */
    public function canAdd(IdentityInterface $user, mixed $resource): bool
    {
        return !$this->units->manageScope($user)->isEmpty();
    }

    /**
     * Hands the user's `units.view` scope to the query as the `developmentScope` option.
     *
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \Cake\ORM\Query\SelectQuery<mixed> $query Query to narrow.
     * @return \Cake\ORM\Query\SelectQuery<mixed>
     */
    public function scopeIndex(IdentityInterface $user, SelectQuery $query): SelectQuery
    {
        return $query->applyOptions(['developmentScope' => $this->units->viewScope($user)]);
    }
}
