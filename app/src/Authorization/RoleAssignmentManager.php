<?php
declare(strict_types=1);

namespace App\Authorization;

use App\Clock\BusinessClock;
use Cake\Database\Connection;
use Cake\Database\Exception\DatabaseException;
use Cake\Datasource\ConnectionManager;
use DateTimeImmutable;
use PDOException;
use Throwable;

/**
 * Who may hold which role in a development, and who may change that (RB-R3, ADR-0005 section 3).
 *
 * Every public method re-checks `role_assignments.manage` in the development, so the rules hold whoever calls
 * (controller, command, test) and not only behind a policy. Rules:
 * - the actor must hold `role_assignments.manage` in that development, or be a super-administrator; otherwise
 *   RoleAssignmentNotAllowed (callers answer 404);
 * - nobody grants `super_administrator` here: it is global and stays in `bin/cake create_user` (ADR-0005,
 *   AC-RB-9); this keeps the two-account limit out of an everyday screen;
 * - privilege escalation (spec silent, decision): a role that carries `role_assignments.manage` (today only
 *   `administrator`) is granted and ended only by a super-administrator, so an administrator cannot mint peers
 *   or remove them. Derived from the permission matrix, not from a role name, so it follows later matrix changes;
 * - a user cannot grant or end their own assignments, except a super-administrator (spec silent, decision: it
 *   prevents self-escalation and self lock-out; super-administrators are the recovery path);
 * - `ends_on` >= `starts_on`; a duplicate (user, role, development) is a friendly error;
 * - ending sets `ends_on` and never deletes: history is kept. Every grant and end writes an append-only row in
 *   `role_assignment_events`, in the same transaction as the change.
 *
 * "Today" is the business day of BusinessClock, as in AccessControl.
 */
class RoleAssignmentManager
{
    public const PERMISSION = 'role_assignments.manage';

    private const SUPER_ADMINISTRATOR = 'super_administrator';
    private const DATE_FORMAT = 'Y-m-d';

    /**
     * @param \Cake\Database\Connection|null $connection Connection; defaults to `default`.
     * @param \App\Authorization\AccessControl|null $access Scope resolver; defaults to one on the same connection.
     * @param \App\Clock\BusinessClock|null $clock Source of "today"; defaults to the business clock.
     */
    public function __construct(
        private ?Connection $connection = null,
        private ?AccessControl $access = null,
        private ?BusinessClock $clock = null,
    ) {
    }

    /**
     * @param int $actorId Acting user id.
     * @param int $developmentId Development id.
     * @return bool
     */
    public function canManage(int $actorId, int $developmentId): bool
    {
        return $this->scopeFor($actorId)->allows($developmentId);
    }

    /**
     * Assignments of a development in one query, with the user name, role name and state.
     *
     * `state` is `current`, `future` or `ended`; `endable` says whether this actor may end the row.
     *
     * @param int $actorId Acting user id.
     * @param int $developmentId Development id.
     * @return list<array<string, mixed>>
     * @throws \App\Authorization\RoleAssignmentNotAllowed
     */
    public function listFor(int $actorId, int $developmentId): array
    {
        $isSuper = $this->requireManage($actorId, $developmentId)->isAll();
        $today = $this->today();

        $rows = $this->connection()->execute(
            'SELECT ra.id, ra.user_id, u.username, ra.role_id, r.code AS role_code, r.name AS role_name,
                    ra.starts_on, ra.ends_on, ra.read_only, ' . $this->privilegedSql('r.id') . ' AS privileged
             FROM role_assignments ra
             JOIN users u ON u.id = ra.user_id
             JOIN roles r ON r.id = ra.role_id
             WHERE ra.development_id = :development
             ORDER BY u.username, r.code, ra.id',
            ['development' => $developmentId, 'permission' => self::PERMISSION],
            ['development' => 'integer', 'permission' => 'string'],
        )->fetchAll('assoc');

        $list = [];
        foreach ($rows as $row) {
            $endsOn = $row['ends_on'] === null ? null : (string)$row['ends_on'];
            $startsOn = (string)$row['starts_on'];
            $state = $endsOn !== null && $endsOn < $today
                ? 'ended'
                : ($startsOn > $today ? 'future' : 'current');
            $privileged = $this->bool($row['privileged']);

            $list[] = [
                'id' => (int)$row['id'],
                'user_id' => (int)$row['user_id'],
                'username' => (string)$row['username'],
                'role_id' => (int)$row['role_id'],
                'role_code' => (string)$row['role_code'],
                'role_name' => (string)$row['role_name'],
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'read_only' => $this->bool($row['read_only']),
                'state' => $state,
                'endable' => $this->canEndRow($isSuper, $actorId, (int)$row['user_id'], $privileged)
                    && $this->endDateFor($startsOn, $endsOn) !== null,
            ];
        }

        $rank = ['current' => 0, 'future' => 1, 'ended' => 2];
        usort($list, static fn(array $a, array $b): int => [$rank[$a['state']], $a['username'], $a['id']]
            <=> [$rank[$b['state']], $b['username'], $b['id']]);

        return $list;
    }

    /**
     * Roles the actor may choose in the grant form.
     *
     * @param int $actorId Acting user id.
     * @param int $developmentId Development id.
     * @return list<array{id: int, code: string, name: string}>
     * @throws \App\Authorization\RoleAssignmentNotAllowed
     */
    public function assignableRoles(int $actorId, int $developmentId): array
    {
        $isSuper = $this->requireManage($actorId, $developmentId)->isAll();

        $rows = $this->connection()->execute(
            'SELECT r.id, r.code, r.name FROM roles r
             WHERE r.code <> :super' . ($isSuper ? '' : ' AND NOT ' . $this->privilegedSql('r.id')) . '
             ORDER BY r.name',
            $isSuper
                ? ['super' => self::SUPER_ADMINISTRATOR]
                : ['super' => self::SUPER_ADMINISTRATOR, 'permission' => self::PERMISSION],
        )->fetchAll('assoc');

        return array_map(
            static fn(array $row): array => [
                'id' => (int)$row['id'],
                'code' => (string)$row['code'],
                'name' => (string)$row['name'],
            ],
            $rows,
        );
    }

    /**
     * Give an existing active user a role in a development.
     *
     * @param int $actorId Acting user id.
     * @param int $developmentId Development id.
     * @param string $username Username of the grantee.
     * @param string|int $roleId Role id.
     * @param string|null $startsOn `Y-m-d`; null or empty means today.
     * @param string|null $endsOn `Y-m-d`; null or empty means open ended.
     * @param bool $readOnly Whether the assignment keeps only read permissions.
     * @return int New assignment id.
     * @throws \App\Authorization\RoleAssignmentNotAllowed
     * @throws \App\Authorization\RoleAssignmentException
     */
    public function grant(
        int $actorId,
        int $developmentId,
        string $username,
        int|string $roleId,
        ?string $startsOn,
        ?string $endsOn,
        bool $readOnly,
    ): int {
        $this->requireManage($actorId, $developmentId);
        $starts = $this->parseDate($startsOn) ?? $this->today();
        $ends = $this->parseDate($endsOn);
        if ($ends !== null && $ends < $starts) {
            throw new RoleAssignmentException(RoleAssignmentException::PERIOD_INVALID);
        }

        try {
            return $this->connection()->transactional(
                function () use ($actorId, $developmentId, $username, $roleId, $starts, $ends, $readOnly): int {
                    $isSuper = $this->requireManage($actorId, $developmentId)->isAll();
                    $this->requireExistingDevelopment($developmentId);

                    $role = $this->findRole($roleId);
                    if ($role['code'] === self::SUPER_ADMINISTRATOR) {
                        throw new RoleAssignmentException(RoleAssignmentException::ROLE_NOT_GRANTABLE);
                    }
                    $granteeId = $this->findActiveUserId($username);
                    if ($granteeId === $actorId && !$isSuper) {
                        throw new RoleAssignmentException(RoleAssignmentException::SELF_CHANGE);
                    }
                    if ($role['privileged'] && !$isSuper) {
                        throw new RoleAssignmentException(RoleAssignmentException::PRIVILEGED_ROLE);
                    }

                    $id = (int)$this->connection()->execute(
                        'INSERT INTO role_assignments
                            (user_id, role_id, development_id, starts_on, ends_on, read_only, created, modified)
                         VALUES (:user, :role, :development, :starts, :ends, :read_only, NOW(), NOW())
                         RETURNING id',
                        [
                            'user' => $granteeId,
                            'role' => $role['id'],
                            'development' => $developmentId,
                            'starts' => $starts,
                            'ends' => $ends,
                            'read_only' => $readOnly,
                        ],
                        [
                            'user' => 'integer',
                            'role' => 'integer',
                            'development' => 'integer',
                            'starts' => 'string',
                            'ends' => 'string',
                            'read_only' => 'boolean',
                        ],
                    )->fetch()[0];

                    $this->record($actorId, $id, $developmentId, 'granted', null, [
                        'id' => $id,
                        'user_id' => $granteeId,
                        'role_id' => $role['id'],
                        'development_id' => $developmentId,
                        'starts_on' => $starts,
                        'ends_on' => $ends,
                        'read_only' => $readOnly,
                    ]);

                    return $id;
                },
            );
        } catch (RoleAssignmentException | RoleAssignmentNotAllowed $e) {
            throw $e;
        } catch (Throwable $e) {
            throw $this->translate($e) ?? $e;
        }
    }

    /**
     * End an assignment. The row stays; `ends_on` becomes the last day the assignment still counts.
     *
     * The end is the day before today, so access stops at once (AccessControl counts `ends_on` inclusive). An
     * assignment that starts today or later cannot end before it starts (`ends_on >= starts_on`): it ends on its
     * start day, which is the shortest period the schema allows.
     *
     * @param int $actorId Acting user id.
     * @param int $developmentId Development id.
     * @param int $assignmentId Assignment id.
     * @return void
     * @throws \App\Authorization\RoleAssignmentNotAllowed
     * @throws \App\Authorization\RoleAssignmentException
     */
    public function end(int $actorId, int $developmentId, int $assignmentId): void
    {
        try {
            $this->connection()->transactional(function () use ($actorId, $developmentId, $assignmentId): void {
                $isSuper = $this->requireManage($actorId, $developmentId)->isAll();

                $row = $this->connection()->execute(
                    'SELECT ra.id, ra.user_id, ra.role_id, ra.development_id, ra.starts_on, ra.ends_on, ra.read_only,
                            ' . $this->privilegedSql('ra.role_id') . ' AS privileged
                     FROM role_assignments ra
                     WHERE ra.id = :id AND ra.development_id = :development
                     FOR UPDATE OF ra',
                    ['id' => $assignmentId, 'development' => $developmentId, 'permission' => self::PERMISSION],
                    ['id' => 'integer', 'development' => 'integer', 'permission' => 'string'],
                )->fetch('assoc');
                if ($row === false) {
                    throw new RoleAssignmentException(RoleAssignmentException::ASSIGNMENT_NOT_FOUND);
                }

                if (!$isSuper && (int)$row['user_id'] === $actorId) {
                    throw new RoleAssignmentException(RoleAssignmentException::SELF_CHANGE);
                }
                if (!$isSuper && $this->bool($row['privileged'])) {
                    throw new RoleAssignmentException(RoleAssignmentException::PRIVILEGED_ROLE);
                }

                $startsOn = (string)$row['starts_on'];
                $endsOn = $row['ends_on'] === null ? null : (string)$row['ends_on'];
                $newEnd = $this->endDateFor($startsOn, $endsOn);
                if ($newEnd === null) {
                    throw new RoleAssignmentException(RoleAssignmentException::ALREADY_ENDED);
                }

                $this->connection()->execute(
                    'UPDATE role_assignments SET ends_on = :ends, modified = NOW() WHERE id = :id',
                    ['ends' => $newEnd, 'id' => $assignmentId],
                    ['ends' => 'string', 'id' => 'integer'],
                );

                $snapshot = [
                    'id' => (int)$row['id'],
                    'user_id' => (int)$row['user_id'],
                    'role_id' => (int)$row['role_id'],
                    'development_id' => (int)$row['development_id'],
                    'starts_on' => $startsOn,
                    'ends_on' => $endsOn,
                    'read_only' => $this->bool($row['read_only']),
                ];
                $this->record(
                    $actorId,
                    $assignmentId,
                    $developmentId,
                    'ended',
                    $snapshot,
                    ['ends_on' => $newEnd] + $snapshot,
                );
            });
        } catch (RoleAssignmentException | RoleAssignmentNotAllowed $e) {
            throw $e;
        } catch (Throwable $e) {
            throw $this->translate($e) ?? $e;
        }
    }

    /**
     * The `ends_on` an end action would write, or null when ending would change nothing.
     *
     * @param string $startsOn Start date.
     * @param string|null $endsOn Current end date.
     * @return string|null
     */
    private function endDateFor(string $startsOn, ?string $endsOn): ?string
    {
        $yesterday = $this->clock()->today()->subDays(1)->format(self::DATE_FORMAT);
        $newEnd = max($yesterday, $startsOn);

        return $endsOn !== null && $endsOn <= $newEnd ? null : $newEnd;
    }

    /**
     * @param bool $isSuper Whether the actor is a super-administrator.
     * @param int $actorId Acting user id.
     * @param int $rowUserId User holding the assignment.
     * @param bool $privileged Whether the assignment's role carries the manage permission.
     * @return bool
     */
    private function canEndRow(bool $isSuper, int $actorId, int $rowUserId, bool $privileged): bool
    {
        return $isSuper || ($rowUserId !== $actorId && !$privileged);
    }

    /**
     * SQL predicate: does this role carry the manage permission? Binds `:permission`.
     *
     * @param string $roleIdColumn Column holding the role id.
     * @return string
     */
    private function privilegedSql(string $roleIdColumn): string
    {
        return 'EXISTS (SELECT 1 FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id
                        WHERE rp.role_id = ' . $roleIdColumn . ' AND p.code = :permission)';
    }

    /**
     * @param string|int $roleId Role id as submitted.
     * @return array{id: int, code: string, privileged: bool}
     */
    private function findRole(int|string $roleId): array
    {
        $id = filter_var($roleId, FILTER_VALIDATE_INT);
        $row = $id === false ? false : $this->connection()->execute(
            'SELECT r.id, r.code, ' . $this->privilegedSql('r.id') . ' AS privileged FROM roles r WHERE r.id = :id',
            ['id' => $id, 'permission' => self::PERMISSION],
            ['id' => 'integer', 'permission' => 'string'],
        )->fetch('assoc');
        if ($row === false) {
            throw new RoleAssignmentException(RoleAssignmentException::ROLE_NOT_FOUND);
        }

        return [
            'id' => (int)$row['id'],
            'code' => (string)$row['code'],
            'privileged' => $this->bool($row['privileged']),
        ];
    }

    /**
     * @param string $username Username as typed.
     * @return int
     */
    private function findActiveUserId(string $username): int
    {
        $id = $this->connection()->execute(
            "SELECT id FROM users WHERE username = :username AND status = 'active'",
            ['username' => trim($username)],
            ['username' => 'string'],
        )->fetch();
        if ($id === false) {
            // Unknown and disabled look the same: the screen must not reveal which accounts exist but are off.
            throw new RoleAssignmentException(RoleAssignmentException::USER_NOT_FOUND);
        }

        return (int)$id[0];
    }

    /**
     * @param int $developmentId Development id.
     * @return void
     */
    private function requireExistingDevelopment(int $developmentId): void
    {
        $found = $this->connection()->execute(
            'SELECT 1 FROM developments WHERE id = :id',
            ['id' => $developmentId],
            ['id' => 'integer'],
        )->fetch();
        if ($found === false) {
            throw new RoleAssignmentNotAllowed('Unknown development');
        }
    }

    /**
     * @param int $actorId Acting user id.
     * @param int $developmentId Development id.
     * @return \App\Authorization\DevelopmentScope The actor's scope, to tell a super-administrator apart.
     */
    private function requireManage(int $actorId, int $developmentId): DevelopmentScope
    {
        $scope = $this->scopeFor($actorId);
        if (!$scope->allows($developmentId)) {
            throw new RoleAssignmentNotAllowed('Not allowed to manage role assignments in this development');
        }

        return $scope;
    }

    /**
     * @param int $actorId Acting user id.
     * @return \App\Authorization\DevelopmentScope
     */
    private function scopeFor(int $actorId): DevelopmentScope
    {
        $access = $this->access ??= new AccessControl($this->connection, $this->clock());

        return $access->allowedFor($actorId, self::PERMISSION);
    }

    /**
     * @param int $actorId Actor.
     * @param int $assignmentId Assignment.
     * @param int $developmentId Development.
     * @param string $action `granted` or `ended`.
     * @param array<string, mixed>|null $before Snapshot before the change.
     * @param array<string, mixed> $after Snapshot after the change.
     * @return void
     */
    private function record(
        int $actorId,
        int $assignmentId,
        int $developmentId,
        string $action,
        ?array $before,
        array $after,
    ): void {
        $this->connection()->execute(
            'INSERT INTO role_assignment_events
                (actor_user_id, role_assignment_id, development_id, action, before_snapshot, after_snapshot, created)
             VALUES (:actor, :assignment, :development, :action, CAST(:before AS jsonb), CAST(:after AS jsonb), NOW())',
            [
                'actor' => $actorId,
                'assignment' => $assignmentId,
                'development' => $developmentId,
                'action' => $action,
                'before' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
                'after' => json_encode($after, JSON_THROW_ON_ERROR),
            ],
            [
                'actor' => 'integer',
                'assignment' => 'integer',
                'development' => 'integer',
                'action' => 'string',
                'before' => 'string',
                'after' => 'string',
            ],
        );
    }

    /**
     * @param \Throwable $error Error raised while writing.
     * @return \App\Authorization\RoleAssignmentException|null Null when the error is not a user-fixable rule.
     */
    private function translate(Throwable $error): ?RoleAssignmentException
    {
        for ($e = $error; $e !== null; $e = $e->getPrevious()) {
            if ($e instanceof PDOException || $e instanceof DatabaseException) {
                $state = $e instanceof PDOException
                    ? ($e->errorInfo[0] ?? (string)$e->getCode())
                    : (string)$e->getCode();
                $found = RoleAssignmentException::fromDatabaseError((string)$state, $e->getMessage());
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * @param string|null $value Date as submitted.
     * @return string|null `Y-m-d`, or null when empty.
     * @throws \App\Authorization\RoleAssignmentException When the text is not a real date in `Y-m-d`.
     */
    private function parseDate(?string $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!' . self::DATE_FORMAT, $value);
        $errors = DateTimeImmutable::getLastErrors();
        if (
            $date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format(self::DATE_FORMAT) !== $value
        ) {
            throw new RoleAssignmentException(RoleAssignmentException::DATE_INVALID);
        }

        return $value;
    }

    /**
     * @return \App\Clock\BusinessClock
     */
    private function clock(): BusinessClock
    {
        return $this->clock ??= new BusinessClock();
    }

    /**
     * @return string Today as `Y-m-d`.
     */
    private function today(): string
    {
        return $this->clock()->today()->format(self::DATE_FORMAT);
    }

    /**
     * @param mixed $value Boolean as returned by the driver (bool, `t`/`f`, 0/1).
     * @return bool
     */
    private function bool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
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
