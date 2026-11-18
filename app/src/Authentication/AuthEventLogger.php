<?php
declare(strict_types=1);

namespace App\Authentication;

use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Writes the sign-in audit trail (`auth_events`, append-only in the database).
 *
 * The runtime database role cannot insert into the table, and there is no identity yet when a sign-in fails: rows are
 * written by the definer function `auth_event_log()` (RLS-SEC-001).
 *
 * The row is written on the separate `auth_side` connection (autocommit), not inside the request transaction: a failed
 * attempt followed by a forced error must still leave its trace.
 *
 * Only identifiers are recorded: never a password, a code or a secret. A failure to write is not swallowed:
 * a sign-in that cannot be audited must not succeed silently.
 */
final class AuthEventLogger
{
    public const PASSWORD_OK = 'password_ok';
    public const PASSWORD_FAILED = 'password_failed';
    public const TOTP_OK = 'totp_ok';
    public const TOTP_FAILED = 'totp_failed';
    public const TOTP_LOCKOUT = 'totp_lockout';
    public const TOTP_ENROLLED = 'totp_enrolled';
    public const BACKUP_CODE_USED = 'backup_code_used';
    public const SIGNED_OUT = 'signed_out';

    private const MAX_TEXT = 255;

    /**
     * @param \Cake\Database\Connection|null $connection Connection; defaults to `auth_side`, the autocommit
     *  connection of the runtime role, so the row outlives a request that rolls back.
     */
    public function __construct(private ?Connection $connection = null)
    {
    }

    /**
     * @param string $type One of the class constants.
     * @param int|null $userId User the event is about, when known.
     * @param string|null $username Username as submitted (kept for failed attempts on unknown users).
     * @param \Psr\Http\Message\ServerRequestInterface|null $request Request, for the client address and agent.
     * @return void
     */
    public function log(
        string $type,
        ?int $userId,
        ?string $username = null,
        ?ServerRequestInterface $request = null,
    ): void {
        $this->connection()->execute(
            'SELECT auth_event_log(:user_id, :username, :type, :ip, :agent)',
            [
                'user_id' => $userId,
                'username' => $this->clip($username),
                'type' => $type,
                'ip' => $request === null ? null : $this->clientIp($request),
                'agent' => $request === null ? null : $this->clip($request->getHeaderLine('User-Agent')),
            ],
            [
                'user_id' => 'integer',
                'username' => 'string',
                'type' => 'string',
                'ip' => 'string',
                'agent' => 'string',
            ],
        );
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request Request.
     * @return string|null
     */
    private function clientIp(ServerRequestInterface $request): ?string
    {
        $ip = $request instanceof ServerRequest
            ? $request->clientIp()
            : ($request->getServerParams()['REMOTE_ADDR'] ?? null);

        return $this->clip(is_string($ip) ? $ip : null);
    }

    /**
     * @return string|null
     */
    private function clip(?string $text): ?string
    {
        return $text === null || $text === '' ? null : mb_substr($text, 0, self::MAX_TEXT);
    }

    /**
     * @return \Cake\Database\Connection
     */
    private function connection(): Connection
    {
        /** @var \Cake\Database\Connection $connection */
        $connection = $this->connection ??= ConnectionManager::get('auth_side');

        return $connection;
    }
}
