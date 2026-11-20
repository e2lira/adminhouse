<?php
declare(strict_types=1);

namespace App\Policy;

use App\Authorization\AccessControl;
use App\Model\Entity\Person;
use Authorization\IdentityInterface;

/**
 * Entity-level rules for one person, judged for the development the controller states with `Person::inDevelopment()`
 * (a person has no development of its own): see it with `people.view`, create or change it with `people.manage` in a
 * development that is not archived. A person with no stated development is refused everything. Nobody deletes people
 * through the application.
 *
 * Whether the person may be changed at all (a person shared with a development the editor cannot manage is not) is
 * decided by the database update policy, not here: this layer cannot see links to developments the user cannot see.
 */
class PersonPolicy
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
     * @param \App\Model\Entity\Person $person Person, with the development it is handled in.
     * @return bool
     */
    public function canView(IdentityInterface $user, Person $person): bool
    {
        return $this->people->canView($user, $person->development());
    }

    /**
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \App\Model\Entity\Person $person Person, with the development it is created in.
     * @return bool
     */
    public function canAdd(IdentityInterface $user, Person $person): bool
    {
        return $this->people->canWrite($user, $person->development());
    }

    /**
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \App\Model\Entity\Person $person Person, with the development it is edited in.
     * @return bool
     */
    public function canEdit(IdentityInterface $user, Person $person): bool
    {
        return $this->people->canWrite($user, $person->development());
    }

    /**
     * Explicit, so that asking for `delete` is a clean refusal and not a missing-method error.
     *
     * @param \Authorization\IdentityInterface $user Identity.
     * @param \App\Model\Entity\Person $person Person.
     * @return bool
     */
    public function canDelete(IdentityInterface $user, Person $person): bool
    {
        return false;
    }
}
