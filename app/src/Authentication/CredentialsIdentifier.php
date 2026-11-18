<?php
declare(strict_types=1);

namespace App\Authentication;

use ArrayAccess;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Identifies a user by username and password.
 *
 * The password hash lives in `user_credentials`, not in `users` (ADR-0005), so the stock
 * password identifier cannot be used: it would need the hash on the identity, and the identity is
 * stored in the session. This identifier checks the hash itself and returns the user without it.
 */
class CredentialsIdentifier extends AbstractIdentifier
{
    use LocatorAwareTrait;

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

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $user = $users->find()
            ->select(['id', 'username', 'status', 'person_id'])
            ->where(['username' => $username])
            ->first();

        /** @var \App\Model\Table\UserCredentialsTable $credentialsTable */
        $credentialsTable = $this->fetchTable('UserCredentials');
        $credential = $user === null ? null : $credentialsTable->find()
            ->select(['user_id', 'password_hash'])
            ->where(['user_id' => $user->id])
            ->first();

        $hasher = new DefaultPasswordHasher();
        $hash = $credential?->password_hash ?? self::decoyHash();
        $matches = $hasher->check($password, $hash);

        if ($user === null || $credential === null || !$matches || $user->status !== 'active') {
            return null;
        }

        if ($hasher->needsRehash($hash)) {
            $credential->password_hash = $hasher->hash($password);
            $credentialsTable->save($credential);
        }

        return $user;
    }

    /**
     * @return string
     */
    private static function decoyHash(): string
    {
        return self::$decoyHash ??= (new DefaultPasswordHasher())->hash('decoy password for timing');
    }
}
