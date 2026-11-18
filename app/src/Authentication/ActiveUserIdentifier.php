<?php
declare(strict_types=1);

namespace App\Authentication;

use ArrayAccess;
use Authentication\Identifier\AbstractIdentifier;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Reloads a signed-in user by id on every request and only returns them while their account is `active`.
 *
 * This is what makes disabling a user effective on their next request instead of when the session expires
 * (REBUILD-003-F2). It costs one primary-key query per request.
 */
class ActiveUserIdentifier extends AbstractIdentifier
{
    use LocatorAwareTrait;

    public const CREDENTIAL_ID = 'id';

    /**
     * @inheritDoc
     */
    public function identify(array $credentials): ArrayAccess|array|null
    {
        $id = $credentials[self::CREDENTIAL_ID] ?? null;
        if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
            return null;
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');

        return $users->find()
            ->select(['id', 'username', 'status', 'person_id'])
            ->where(['id' => (int)$id, 'status' => $users::STATUS_ACTIVE])
            ->first();
    }
}
