<?php
declare(strict_types=1);

namespace App\Authentication;

use ArrayAccess;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\PasswordHasher\DefaultPasswordHasher;

/**
 * Identifies a user by username and password.
 *
 * The password hash lives in `user_credentials`, not in `users` (ADR-0005), so the stock
 * password identifier cannot be used: it would need the hash on the identity, and the identity is
 * stored in the session. This identifier checks the hash itself and returns the user without it.
 *
 * The runtime database role has no privilege on `user_credentials`: the hash is read through the definer function
 * behind AccountGateway (RLS-SEC-001).
 */
class CredentialsIdentifier extends AbstractIdentifier
{
    /**
     * Hash checked when the user does not exist, so both outcomes cost the same time.
     */
    private static ?string $decoyHash = null;

    /**
     * @inheritDoc
     */
    public function identify(array $credentials): ArrayAccess|array|null
    {
        $username = $credentials[self::CREDENTIAL_USERNAME] ?? null;
        $password = $credentials[self::CREDENTIAL_PASSWORD] ?? null;
        if (!is_string($username) || !is_string($password) || $username === '') {
            return null;
        }

        $gateway = new AccountGateway();
        $account = $gateway->findForSignIn($username);

        $hasher = new DefaultPasswordHasher();
        $hash = $account['password_hash'] ?? self::decoyHash();
        $matches = $hasher->check($password, $hash);

        if ($account === null || $account['password_hash'] === null || !$matches || $account['status'] !== 'active') {
            return null;
        }

        if ($hasher->needsRehash($hash)) {
            $gateway->rehash($account['id'], $hash, $hasher->hash($password));
        }

        return $gateway->identity($account);
    }

    /**
     * @return string
     */
    private static function decoyHash(): string
    {
        return self::$decoyHash ??= (new DefaultPasswordHasher())->hash('decoy password for timing');
    }
}
