<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database;

use App\Test\Support\DatabaseTestCase;
use Throwable;

/**
 * The role-assignment audit trail is append-only, like auth_events.
 */
class RoleAssignmentEventsTest extends DatabaseTestCase
{
    private function insertEvent(): int
    {
        $this->db()->execute(
            "INSERT INTO role_assignment_events
                (actor_user_id, role_assignment_id, development_id, action, before_snapshot, after_snapshot, created)
             VALUES (1, 10, 5, 'granted', NULL, '{\"a\":1}'::jsonb, NOW())",
        );

        return (int)$this->db()->execute('SELECT MAX(id) FROM role_assignment_events')->fetch()[0];
    }

    public function testEventsCanBeInsertedAndRead(): void
    {
        $id = $this->insertEvent();

        $row = $this->db()->execute('SELECT * FROM role_assignment_events WHERE id = ?', [$id])->fetch('assoc');
        $this->assertSame('granted', $row['action']);
    }

    public function testEventsCannotBeUpdated(): void
    {
        $id = $this->insertEvent();

        try {
            $this->db()->execute("UPDATE role_assignment_events SET action = 'ended' WHERE id = ?", [$id]);
            $this->fail('UPDATE must be rejected');
        } catch (Throwable $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }
        $this->assertSame(
            'granted',
            $this->db()->execute('SELECT action FROM role_assignment_events WHERE id = ?', [$id])->fetch()[0],
        );
    }

    public function testEventsCannotBeDeleted(): void
    {
        $id = $this->insertEvent();

        try {
            $this->db()->execute('DELETE FROM role_assignment_events WHERE id = ?', [$id]);
            $this->fail('DELETE must be rejected');
        } catch (Throwable $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }
        $this->assertSame(1, (int)$this->db()->execute('SELECT COUNT(*) FROM role_assignment_events')->fetch()[0]);
    }

    public function testUnknownActionsAreRejected(): void
    {
        $this->expectException(Throwable::class);
        $this->db()->execute(
            "INSERT INTO role_assignment_events (actor_user_id, role_assignment_id, development_id, action, created)
             VALUES (1, 10, 5, 'deleted', NOW())",
        );
    }

    public function testEventsOutliveTheirAssignmentAndActor(): void
    {
        $dev = $this->createDevelopment();
        $actor = $this->createUser('ann');
        $this->db()->execute(
            "INSERT INTO role_assignment_events (actor_user_id, role_assignment_id, development_id, action, created)
             VALUES (?, 99, ?, 'granted', NOW())",
            [$actor, $dev],
        );

        $this->db()->execute('DELETE FROM users WHERE id = ?', [$actor]);
        $this->db()->execute('DELETE FROM developments WHERE id = ?', [$dev]);

        $this->assertSame(1, (int)$this->db()->execute('SELECT COUNT(*) FROM role_assignment_events')->fetch()[0]);
    }
}
