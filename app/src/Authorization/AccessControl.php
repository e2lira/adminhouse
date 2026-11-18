<?php
declare(strict_types=1);

namespace App\Authorization;

use App\Clock\BusinessClock;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;

/**
 * Answers "in which developments may this user do this?" (RB-R3, RB-R4, ADR-0005).
 *
 * Rules, all evaluated by a single query so a revoked role or a disabled user takes effect on the next call:
 * - the user must be `active`;
 * - an assignment counts only today (the business day, see BusinessClock), between `starts_on` and `ends_on` inclusive (`ends_on` NULL is open);
 * - an active `super_administrator` assignment grants every permission in every development (RB-R4);
 * - any other assignment grants the permissions of its role, for its development only;
 * - a read-only assignment keeps only read permissions, those whose code ends in `.view`.
 */
class AccessControl
{
    private const READ_SUFFIX = '.view';

    /**
     * @param \Cake\Database\Connection|null $connection Connection to use; defaults to `default`.
     * @param \App\Clock\BusinessClock|null $clock Source of "today"; defaults to the business clock.
     */
    public function __construct(private ?Connection $connection = null, private ?BusinessClock $clock = null)
    {
    }

    /**
     * @param int $userId User id.
     * @param string $permission Permission code, for example `payments.create`.
     * @return \App\Authorization\DevelopmentScope
     */
    public function allowedFor(int $userId, string $permission): DevelopmentScope
    {
        $needsWrite = !str_ends_with($permission, self::READ_SUFFIX);
        $today = ($this->clock ??= new BusinessClock())->today()->format('Y-m-d');

        $rows = $this->connection()->execute(
            'SELECT ra.development_id, (r.code = \'super_administrator\') AS is_super
             FROM role_assignments ra
             JOIN users u ON u.id = ra.user_id AND u.status = \'active\'
             JOIN roles r ON r.id = ra.role_id
             WHERE ra.user_id = :user_id
               AND ra.starts_on <= :today
               AND (ra.ends_on IS NULL OR ra.ends_on >= :today_end)
               ' . ($needsWrite ? 'AND NOT ra.read_only' : '') . '
               AND (
                   r.code = \'super_administrator\'
                   OR EXISTS (
                       SELECT 1 FROM role_permissions rp
                       JOIN permissions p ON p.id = rp.permission_id
                       WHERE rp.role_id = r.id AND p.code = :permission
                   )
               )',
            [
                'user_id' => $userId,
                'today' => $today,
                'today_end' => $today,
                'permission' => $permission,
            ],
            [
                'user_id' => 'integer',
                'today' => 'string',
                'today_end' => 'string',
                'permission' => 'string',
            ],
        )->fetchAll('assoc');

        $ids = [];
        foreach ($rows as $row) {
            if (filter_var($row['is_super'], FILTER_VALIDATE_BOOLEAN)) {
                return DevelopmentScope::all();
            }
            $ids[] = (int)$row['development_id'];
        }

        return DevelopmentScope::only($ids);
    }

    /**
     * @return \Cake\Database\Connection
     */
    private function connection(): Connection
    {
        /** @var \Cake\Database\Connection $connection */
        $connection = $this->connection ?? ConnectionManager::get('default');

        return $connection;
    }
}
