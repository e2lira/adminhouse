<?php
declare(strict_types=1);

namespace App\Authorization;

use RuntimeException;

/**
 * A role-assignment rule refused the change. `$reason` is a stable code; the screen maps it to Spanish copy.
 */
final class RoleAssignmentException extends RuntimeException
{
    public const USER_NOT_FOUND = 'user_not_found';
    public const ROLE_NOT_FOUND = 'role_not_found';
    public const ROLE_NOT_GRANTABLE = 'role_not_grantable';
    public const PRIVILEGED_ROLE = 'privileged_role';
    public const SELF_CHANGE = 'self_change';
    public const DATE_INVALID = 'date_invalid';
    public const PERIOD_INVALID = 'period_invalid';
    public const DUPLICATE = 'duplicate';
    public const ALREADY_ENDED = 'already_ended';
    public const ASSIGNMENT_NOT_FOUND = 'assignment_not_found';
    public const RULE_VIOLATION = 'rule_violation';

    private const SQLSTATE_UNIQUE_VIOLATION = '23505';
    private const SQLSTATE_CHECK_VIOLATION = '23514';

    /**
     * @param string $reason One of the class constants.
     * @param string $message Technical message for logs.
     */
    public function __construct(public readonly string $reason, string $message = '')
    {
        parent::__construct($message !== '' ? $message : $reason);
    }

    /**
     * Translate a database error raised by a constraint or trigger into a rule violation.
     *
     * @param string $sqlState PostgreSQL SQLSTATE.
     * @param string $message Database message.
     * @return self|null Null when the error is not one a user can fix.
     */
    public static function fromDatabaseError(string $sqlState, string $message): ?self
    {
        return match ($sqlState) {
            self::SQLSTATE_UNIQUE_VIOLATION => new self(self::DUPLICATE, $message),
            self::SQLSTATE_CHECK_VIOLATION => new self(self::RULE_VIOLATION, $message),
            default => null,
        };
    }
}
