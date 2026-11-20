<?php
declare(strict_types=1);

namespace App\Policy;

use App\Authorization\AccessControl;
use Authorization\IdentityInterface;
use Cake\ORM\Query\SelectQuery;

/**
 * Who may list people, and in which developments: whoever holds `people.view` somewhere (a resident does not), and the
 * list is narrowed to those developments. Adding is offered to whoever holds `people.manage` somewhere; the entity
 * policy then decides for the development the person belongs to.
 */
class PeopleTablePolicy
{
    private PeopleAccess $people;

    /**
     * @param \App\Authorization\AccessControl $access Scope resolver.
     */
    public function __construct(AccessControl $access = new AccessControl())
    {
        $this->people = new PeopleAccess($access);
    }

    /**
     * @param \Authorization\IdentityInterface $user Identity.
     * @param mixed $resource Table or query being listed.
     * @return bool
     */
    public function canIndex(IdentityInterface $user, mixed $resource): bool
    {
        return !$this->people->viewScope($user)->isEmpty();
    }

    /**
     * Only a gate for the interface (show the add button, answer a bare 403): the table cannot know the development.
     * It is NOT an authorization to save. A controller MUST authorize the entity itself before creating, with its
     * development stated from the route: `authorize($person->inDevelopment($id), 'add')`.
     *
     * @param \Authorization\IdentityInterface $user Identity.
     * @param mixed $resource Table the row would be added to.
     * @return bool
     */
    public function canAdd(IdentityInterface $user, mixed $resource): bool
    {
        return !$this->people->manageScope($user)->isEmpty();
    }

    /**
     * Hands the user's `people.view` scope to the query as the `developmentScope` option.
     *
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \Cake\ORM\Query\SelectQuery<mixed> $query Query to narrow.
     * @return \Cake\ORM\Query\SelectQuery<mixed>
     */
    public function scopeIndex(IdentityInterface $user, SelectQuery $query): SelectQuery
    {
        return $query->applyOptions(['developmentScope' => $this->people->viewScope($user)]);
    }
}
