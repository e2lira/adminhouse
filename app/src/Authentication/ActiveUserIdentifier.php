<?php
declare(strict_types=1);

namespace App\Authentication;

use ArrayAccess;
use Authentication\Identifier\AbstractIdentifier;

/**
 * Reloads a signed-in user by id on every request and only returns them while their account is `active`.
 *
 * This is what makes disabling a user effective on their next request instead of when the session expires
 * (REBUILD-003-F2). It costs one primary-key query per request, through the definer function behind
 * AccountGateway: the user id of the request is not known to PostgreSQL yet at this point (RLS-SEC-001).
 */
class ActiveUserIdentifier extends AbstractIdentifier
{
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

        return (new AccountGateway())->findActive((int)$id);
    }
}
