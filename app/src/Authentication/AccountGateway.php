<?php
declare(strict_types=1);

namespace App\Authentication;

use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Entity;

/**
 * Reads accounts for sign-in through the definer functions of RLS-SEC-001.
 *
 * Before sign-in there is no identity, and the runtime role has no privilege on `user_credentials`, so everything
 * that happens before a user is known goes through `auth_sign_in()` and `auth_find_active_user()`. They return the
 * columns the step needs and nothing more.
 */
class AccountGateway
{
    /**
     * @param \Cake\Database\Connection|null $connection Connection; defaults to `default`.
     */
    public function __construct(private ?Connection $connection = null)
    {
    }

    /**
     * The account and its password hash by username, whatever its status (the caller checks the hash first, so every
     * outcome costs the same, and the status afterwards).
     *
     * @param string $username Username as submitted.
     * @return array{id: int, username: string, status: string, person_id: int|null, password_hash: string|null}|null
     */
    public function findForSignIn(string $username): ?array
    {
        $row = $this->connection()->execute(
            'SELECT id, username, status, person_id, password_hash FROM auth_sign_in(:username)',
            ['username' => $username],
            ['username' => 'string'],
        )->fetch('assoc');
        if ($row === false) {
            return null;
        }

        return [
            'id' => (int)$row['id'],
            'username' => (string)$row['username'],
            'status' => (string)$row['status'],
            'person_id' => $row['person_id'] === null ? null : (int)$row['person_id'],
            'password_hash' => $row['password_hash'] === null ? null : (string)$row['password_hash'],
        ];
    }

    /**
     * The user as an identity (no hash), only while the account is active.
     *
     * @param int $id User id.
     * @return \Cake\ORM\Entity|null
     */
    public function findActive(int $id): ?Entity
    {
        $row = $this->connection()->execute(
            'SELECT id, username, status, person_id FROM auth_find_active_user(:id)',
            ['id' => $id],
            ['id' => 'integer'],
        )->fetch('assoc');

        return $row === false ? null : $this->identity($row);
    }

    /**
     * Store an upgraded hash, but only if the stored one is still the one that was verified.
     *
     * @param int $userId User id.
     * @param string $oldHash Hash that was checked.
     * @param string $newHash Replacement.
     * @return bool
     */
    public function rehash(int $userId, string $oldHash, string $newHash): bool
    {
        return (bool)$this->connection()->execute(
            'SELECT auth_rehash(:id, :old, :new)',
            ['id' => $userId, 'old' => $oldHash, 'new' => $newHash],
            ['id' => 'integer', 'old' => 'string', 'new' => 'string'],
        )->fetch()[0];
    }

    /**
     * @param array<string, mixed> $row Account columns.
     * @return \Cake\ORM\Entity
     */
    public function identity(array $row): Entity
    {
        $entity = new Entity([
            'id' => (int)$row['id'],
            'username' => (string)$row['username'],
            'status' => (string)$row['status'],
            'person_id' => $row['person_id'] === null ? null : (int)$row['person_id'],
        ]);
        $entity->setNew(false);
        $entity->clean();

        return $entity;
    }

    /**
     * @return \Cake\Database\Connection
     */
    private function connection(): Connection
    {
        /** @var \Cake\Database\Connection $connection */
        $connection = $this->connection ??= ConnectionManager::get('default');

        return $connection;
    }
}
