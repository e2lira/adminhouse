<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database\Rls;

use App\Test\Support\ActingAccessControl;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * W-E: the two enforcement layers answer "where may this user do this?" the same way.
 *
 * Layer 1 is `AccessControl::allowedFor` (PHP) and layer 2 is `permission_dev_ids` (SQL). They must agree for every
 * role, read-only flag and assignment window, with two documented divergences that these tests pin down:
 * - an archived development stays in layer 1 for write codes (the refusal comes from `DevelopmentPolicy::canManage*`)
 *   and leaves layer 2 (the RLS refusal);
 * - the super-administrator is `isAll()` in layer 1 and has no development rows in layer 2, where `is_super()`
 *   carries it.
 */
class RlsPermissionDevIdsParityTest extends RlsTestCase
{
    private const CODES = [
        'developments.view', 'units.view', 'payments.create', 'reports.view', 'gate.log', 'role_assignments.manage',
    ];

    /**
     * @return array<string, array{string, bool, int, int|null}>
     */
    public static function assignments(): array
    {
        $cases = [];
        $windows = [
            'open' => [-30, null],
            'ends today' => [-30, 0],
            'starts today' => [0, null],
            'ended yesterday' => [-30, -1],
            'starts tomorrow' => [1, null],
        ];
        foreach (['administrator', 'accountant', 'data_entry', 'supervisor', 'guard', 'resident'] as $role) {
            foreach ([false, true] as $readOnly) {
                foreach ($windows as $label => [$starts, $ends]) {
                    $name = sprintf('%s, %s, %s', $role, $readOnly ? 'read-only' : 'writable', $label);
                    $cases[$name] = [$role, $readOnly, $starts, $ends];
                }
            }
        }

        return $cases;
    }

    /**
     * @param string $role Role code.
     * @param bool $readOnly Whether the assignment is read-only.
     * @param int $starts Offset of `starts_on`.
     * @param int|null $ends Offset of `ends_on`.
     * @return void
     */
    #[DataProvider('assignments')]
    public function testTheLayersAgreeForEveryCode(string $role, bool $readOnly, int $starts, ?int $ends): void
    {
        $user = $this->createUser('subject', null);
        $this->assignRole($user, $role, $this->devA, $starts, $ends, $readOnly);

        $this->actAs($user);
        $layer2 = [];
        foreach (self::CODES as $code) {
            $layer2[$code] = $this->ids('SELECT unnest(permission_dev_ids(?))', [$code]);
        }
        $this->endSession();

        $access = new ActingAccessControl();
        foreach (self::CODES as $code) {
            $layer1 = $access->allowedFor($user, $code);
            $this->assertFalse($layer1->isAll(), $code);
            $this->assertSame($layer1->ids(), $layer2[$code], sprintf('%s as %s', $code, $role));
        }
    }

    public function testTheParityIsNotVacuous(): void
    {
        // A writable accountant holds payments.create today in A: both layers must list A, not both be empty.
        $user = $this->createUser('subject', null);
        $this->assignRole($user, 'accountant', $this->devA);

        $this->actAs($user);
        $layer2 = $this->ids('SELECT unnest(permission_dev_ids(?))', ['payments.create']);
        $this->endSession();

        $this->assertSame([$this->devA], $layer2);
        $this->assertSame([$this->devA], (new ActingAccessControl())->allowedFor($user, 'payments.create')->ids());
    }

    public function testAnArchivedDevelopmentDivergesOnlyForWriteCodes(): void
    {
        $user = $this->createUser('subject', null);
        $this->assignRole($user, 'accountant', $this->devA);
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$this->devA]);

        $this->actAs($user);
        $write2 = $this->ids('SELECT unnest(permission_dev_ids(?))', ['payments.create']);
        $read2 = $this->ids('SELECT unnest(permission_dev_ids(?))', ['reports.view']);
        $this->endSession();

        $access = new ActingAccessControl();
        // Layer 1 keeps the archived development for a write code; DevelopmentPolicy refuses it there.
        $this->assertSame([$this->devA], $access->allowedFor($user, 'payments.create')->ids());
        $this->assertSame([], $write2);
        // A read code agrees in both layers.
        $this->assertSame([$this->devA], $access->allowedFor($user, 'reports.view')->ids());
        $this->assertSame([$this->devA], $read2);
    }

    public function testTheSuperAdministratorIsEverywhereInLayerOneAndInIsSuperInLayerTwo(): void
    {
        $this->actAs($this->superUser);
        $layer2 = $this->ids('SELECT unnest(permission_dev_ids(?))', ['payments.create']);
        $isSuper = (bool)$this->appDb()->execute('SELECT is_super()')->fetch()[0];
        $this->endSession();

        $this->assertTrue((new ActingAccessControl())->allowedFor($this->superUser, 'payments.create')->isAll());
        $this->assertSame([], $layer2);
        $this->assertTrue($isSuper);
    }
}
