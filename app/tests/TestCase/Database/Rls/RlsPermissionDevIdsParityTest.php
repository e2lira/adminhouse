<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database\Rls;

use App\Test\Support\ActingAccessControl;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * W-E: the two enforcement layers answer "where may this user do this?" the same way.
 *
 * Layer 1 is `AccessControl::allowedFor` (PHP) and layer 2 is `permission_dev_ids` (SQL). They must agree for every
 * permission code in the catalogue, role, read-only flag, assignment window, user status and archive state, with two
 * documented divergences that are computed here rather than listed by hand:
 * - for a write code (one that does not end in `.view`) layer 2 is layer 1 minus the archived developments: layer 1
 *   keeps them (the refusal comes from `DevelopmentPolicy::canManage*`) and layer 2 leaves them out (the RLS refusal);
 * - the super-administrator is `isAll()` in layer 1 and has no development rows in layer 2, where `is_super()`
 *   carries it.
 */
class RlsPermissionDevIdsParityTest extends RlsTestCase
{
    /**
     * @return array<string, array{string, bool, int, int|null, bool, string}>
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
                    foreach ([false, true] as $archived) {
                        foreach (['active', 'disabled'] as $status) {
                            $name = sprintf(
                                '%s, %s, %s, %s development, %s user',
                                $role,
                                $readOnly ? 'read-only' : 'writable',
                                $label,
                                $archived ? 'archived' : 'unarchived',
                                $status,
                            );
                            $cases[$name] = [$role, $readOnly, $starts, $ends, $archived, $status];
                        }
                    }
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
     * @param bool $archived Whether the development is archived.
     * @param string $status User status.
     * @return void
     */
    #[DataProvider('assignments')]
    public function testTheLayersAgreeForEveryCodeUpToTheArchivedDivergence(
        string $role,
        bool $readOnly,
        int $starts,
        ?int $ends,
        bool $archived,
        string $status,
    ): void {
        $user = $this->createUser('subject', null, $status);
        $this->assignRole($user, $role, $this->devA, $starts, $ends, $readOnly);
        if ($archived) {
            $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$this->devA]);
        }

        $this->assertParity($user, [$this->devA => $archived], $role);
    }

    public function testTheLayersAgreeAcrossSeveralAssignmentsInSeveralDevelopments(): void
    {
        $devC = $this->createDevelopment('Development C');
        $user = $this->createUser('subject', null);
        $this->assignRole($user, 'administrator', $this->devA);
        $this->assignRole($user, 'accountant', $this->devB, -30, null, true);
        $this->assignRole($user, 'data_entry', $devC);
        // An ended assignment and a second role in A change nothing.
        $this->assignRole($user, 'supervisor', $this->devA);
        $this->assignRole($user, 'administrator', $this->devB, -30, -1);
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$devC]);

        $codes = $this->assertParity($user, [$this->devA => false, $this->devB => false, $devC => true], 'several');

        // Hand-written control values, so the computed expectation cannot hide a shared mistake.
        $this->assertContains('units.manage', $codes);
        $this->actAs($user);
        $layer2 = fn(string $code): array => $this->ids('SELECT unnest(permission_dev_ids(?))', [$code]);
        $this->assertSame([$this->devA], $layer2('units.manage'));
        $this->assertSame([$this->devA], $layer2('payments.create'));
        $this->assertSame([$this->devA, $this->devB, $devC], $layer2('units.view'));
        $this->assertSame([$this->devA, $this->devB], $layer2('reports.view'));
        $this->endSession();
        $access = new ActingAccessControl();
        $this->assertSame([$this->devA, $devC], $access->allowedFor($user, 'units.manage')->ids());
        $this->assertSame([$this->devA, $devC], $access->allowedFor($user, 'payments.create')->ids());
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

    public function testTheCatalogueUnderTestIncludesReadAndWriteCodes(): void
    {
        $codes = $this->codes();

        $this->assertContains('units.manage', $codes);
        $this->assertContains('units.view', $codes);
        $this->assertNotSame([], array_filter($codes, static fn(string $c): bool => str_ends_with($c, '.view')));
        $this->assertNotSame([], array_filter($codes, static fn(string $c): bool => !str_ends_with($c, '.view')));
    }

    public function testTheSuperAdministratorIsEverywhereInLayerOneAndInIsSuperInLayerTwo(): void
    {
        $this->actAs($this->superUser);
        $layer2 = [];
        foreach ($this->codes() as $code) {
            $layer2[$code] = $this->ids('SELECT unnest(permission_dev_ids(?))', [$code]);
        }
        $isSuper = (bool)$this->appDb()->execute('SELECT is_super()')->fetch()[0];
        $this->endSession();

        $access = new ActingAccessControl();
        foreach ($layer2 as $code => $ids) {
            $this->assertTrue($access->allowedFor($this->superUser, $code)->isAll(), $code);
            $this->assertSame([], $ids, $code);
        }
        $this->assertNotSame([], $layer2);
        $this->assertTrue($isSuper);
    }

    /**
     * Compare both layers for every code in the catalogue.
     *
     * @param int $user User id.
     * @param array<int, bool> $archivedByDevelopment Archive state of each development the user may be assigned to.
     * @param string $label Failure label.
     * @return list<string> Codes compared.
     */
    private function assertParity(int $user, array $archivedByDevelopment, string $label): array
    {
        $codes = $this->codes();

        $this->actAs($user);
        $layer2 = [];
        foreach ($codes as $code) {
            $layer2[$code] = $this->ids('SELECT unnest(permission_dev_ids(?))', [$code]);
        }
        $this->endSession();

        $access = new ActingAccessControl();
        foreach ($codes as $code) {
            $layer1 = $access->allowedFor($user, $code);
            $this->assertFalse($layer1->isAll(), $code);
            $expected = $layer1->ids();
            if (!str_ends_with($code, '.view')) {
                $expected = array_values(array_filter(
                    $expected,
                    static fn(int $id): bool => !($archivedByDevelopment[$id] ?? false),
                ));
            }
            $this->assertSame($expected, $layer2[$code], sprintf('%s as %s', $code, $label));
        }

        return $codes;
    }

    /**
     * @return list<string> Every permission code in the catalogue.
     */
    private function codes(): array
    {
        $codes = array_map(
            static fn(array $row): string => $row[0],
            $this->db()->execute('SELECT code FROM permissions ORDER BY code')->fetchAll(),
        );
        $this->assertNotSame([], $codes);

        return $codes;
    }
}
