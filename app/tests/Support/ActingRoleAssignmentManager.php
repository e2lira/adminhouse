<?php
declare(strict_types=1);

namespace App\Test\Support;

use App\Authorization\RoleAssignmentManager;

/**
 * RoleAssignmentManager that runs each call inside a session of its actor (see ActsAsSessionUserTrait), so the
 * row-level security policies apply to it as they do in a request.
 */
class ActingRoleAssignmentManager extends RoleAssignmentManager
{
    use ActsAsSessionUserTrait;

    /**
     * @inheritDoc
     */
    public function canManage(int $actorId, int $developmentId): bool
    {
        return $this->asSessionUser($actorId, fn(): bool => parent::canManage($actorId, $developmentId));
    }

    /**
     * @inheritDoc
     */
    public function listFor(int $actorId, int $developmentId): array
    {
        return $this->asSessionUser($actorId, fn(): array => parent::listFor($actorId, $developmentId));
    }

    /**
     * @inheritDoc
     */
    public function assignableRoles(int $actorId, int $developmentId): array
    {
        return $this->asSessionUser($actorId, fn(): array => parent::assignableRoles($actorId, $developmentId));
    }

    /**
     * @inheritDoc
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
        return $this->asSessionUser(
            $actorId,
            fn(): int => parent::grant($actorId, $developmentId, $username, $roleId, $startsOn, $endsOn, $readOnly),
        );
    }

    /**
     * @inheritDoc
     */
    public function end(int $actorId, int $developmentId, int $assignmentId): void
    {
        $this->asSessionUser($actorId, fn() => parent::end($actorId, $developmentId, $assignmentId));
    }
}
