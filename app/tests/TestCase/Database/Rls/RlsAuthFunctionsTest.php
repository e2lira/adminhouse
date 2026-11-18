<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database\Rls;

use Throwable;

/**
 * The only way the runtime role reaches credentials, second-factor material and the sign-in audit trail: definer
 * functions. Sign-in and the pre-auth steps work with no session (there is no identity yet), so each function takes
 * the user explicitly and returns only what its step needs.
 */
class RlsAuthFunctionsTest extends RlsTestCase
{
    private function setHash(int $userId, string $hash): void
    {
        $this->db()->execute(
            'INSERT INTO user_credentials (user_id, password_hash, password_changed_at) VALUES (?, ?, NOW())
             ON CONFLICT (user_id) DO UPDATE SET password_hash = EXCLUDED.password_hash',
            [$userId, $hash],
        );
    }

    private function storedHash(int $userId): string
    {
        return (string)$this->db()->execute('SELECT password_hash FROM user_credentials WHERE user_id = ?', [$userId])->fetch()[0];
    }

    // sign-in -----------------------------------------------------------------------------------------------------

    public function testSignInReturnsTheUserAndHashWithoutAnySession(): void
    {
        $this->setHash($this->adminA, 'hash-a');
        $this->actAs(null);

        $row = $this->appDb()->execute('SELECT * FROM auth_sign_in(?)', ['admin_a'])->fetch('assoc');

        $this->assertSame($this->adminA, (int)$row['id']);
        $this->assertSame('admin_a', $row['username']);
        $this->assertSame('active', $row['status']);
        $this->assertSame('hash-a', $row['password_hash']);
        $this->assertArrayHasKey('person_id', $row);
    }

    public function testSignInOfAnUnknownUserReturnsNothing(): void
    {
        $this->actAs(null);

        $this->assertFalse($this->appDb()->execute('SELECT * FROM auth_sign_in(?)', ['nobody'])->fetch());
    }

    public function testSignInOfAUserWithoutCredentialsReturnsANullHash(): void
    {
        $this->actAs(null);

        $row = $this->appDb()->execute('SELECT * FROM auth_sign_in(?)', ['loner'])->fetch('assoc');

        $this->assertSame($this->loner, (int)$row['id']);
        $this->assertNull($row['password_hash']);
    }

    public function testSignInReportsDisabledUsersSoTheApplicationCanDecideAfterTheHashCheck(): void
    {
        $this->setHash($this->disabledAdmin, 'h');
        $this->actAs(null);

        $row = $this->appDb()->execute('SELECT * FROM auth_sign_in(?)', ['disabled_admin'])->fetch('assoc');

        $this->assertSame('disabled', $row['status']);
    }

    public function testFindActiveUserOnlyReturnsActiveUsers(): void
    {
        $this->actAs(null);

        $row = $this->appDb()->execute('SELECT * FROM auth_find_active_user(?)', [$this->adminA])->fetch('assoc');
        $this->assertSame('admin_a', $row['username']);
        $this->assertArrayNotHasKey('password_hash', $row);
        $this->assertFalse($this->appDb()->execute('SELECT * FROM auth_find_active_user(?)', [$this->disabledAdmin])->fetch());
        $this->assertFalse($this->appDb()->execute('SELECT * FROM auth_find_active_user(?)', [999999])->fetch());
    }

    public function testRehashIsACompareAndSwap(): void
    {
        $this->setHash($this->adminA, 'old');
        $this->actAs(null);

        $this->assertFalse((bool)$this->appDb()->execute('SELECT auth_rehash(?, ?, ?)', [$this->adminA, 'wrong', 'new'])->fetch()[0]);
        $this->assertSame('old', $this->storedHash($this->adminA));
        $this->assertTrue((bool)$this->appDb()->execute('SELECT auth_rehash(?, ?, ?)', [$this->adminA, 'old', 'new'])->fetch()[0]);
        $this->commitSession();
        $this->assertSame('new', $this->storedHash($this->adminA));
    }

    // password set and reset --------------------------------------------------------------------------------------

    public function testAUserSetsOnlyTheirOwnPassword(): void
    {
        $this->setHash($this->adminA, 'old');
        $this->setHash($this->accountantA, 'untouched');
        $this->actAs($this->adminA);

        $this->assertTrue((bool)$this->appDb()->execute('SELECT auth_set_password(?, ?)', [$this->adminA, 'mine'])->fetch()[0]);
        $this->assertRejected('SELECT auth_set_password(?, ?)', [$this->accountantA, 'pwned']);

        $this->commitSession();
        $this->assertSame('mine', $this->storedHash($this->adminA));
        $this->assertSame('untouched', $this->storedHash($this->accountantA));
    }

    public function testSettingAPasswordNeedsASession(): void
    {
        $this->setHash($this->adminA, 'old');
        $this->actAs(null);
        $this->assertRejected('SELECT auth_set_password(?, ?)', [$this->adminA, 'x']);
        $this->endSession();

        $this->actAsJob([$this->devA]);
        $this->assertRejected('SELECT auth_set_password(?, ?)', [$this->adminA, 'x']);

        $this->assertSame('old', $this->storedHash($this->adminA));
    }

    public function testAnAdministratorResetsAUserWhoseAssignmentsAreAllInTheirDevelopments(): void
    {
        $this->setHash($this->resident, 'old');
        $this->actAs($this->adminA);

        $this->assertTrue((bool)$this->appDb()->execute('SELECT auth_reset(?, ?)', [$this->resident, 'reset'])->fetch()[0]);

        $this->commitSession();
        $this->assertSame('reset', $this->storedHash($this->resident));
    }

    public function testAnAdministratorCannotResetAUserWithAnAssignmentOutsideTheirDevelopments(): void
    {
        $this->setHash($this->resident, 'old');
        $this->assignRole($this->resident, 'resident', $this->devB);
        $this->actAs($this->adminA);

        $this->assertRejected('SELECT auth_reset(?, ?)', [$this->resident, 'reset']);

        $this->assertSame('old', $this->storedHash($this->resident));
    }

    public function testAnAdministratorCannotResetASuperAdministratorOrAStranger(): void
    {
        $this->setHash($this->superUser, 'old-super');
        $this->setHash($this->loner, 'old-loner');
        $this->setHash($this->adminB, 'old-b');
        $this->actAs($this->adminA);

        $this->assertRejected('SELECT auth_reset(?, ?)', [$this->superUser, 'x']);
        $this->assertRejected('SELECT auth_reset(?, ?)', [$this->loner, 'x']);
        $this->assertRejected('SELECT auth_reset(?, ?)', [$this->adminB, 'x']);

        $this->assertSame('old-super', $this->storedHash($this->superUser));
        $this->assertSame('old-loner', $this->storedHash($this->loner));
        $this->assertSame('old-b', $this->storedHash($this->adminB));
    }

    public function testOnlyAdministratorsAndSuperAdministratorsReset(): void
    {
        $this->setHash($this->resident, 'old');
        foreach (['accountantA', 'guardA', 'disabledAdmin', 'endedAdmin', 'futureAdmin', 'loner'] as $user) {
            $this->actAs($this->{$user});
            $this->assertRejected('SELECT auth_reset(?, ?)', [$this->resident, 'x']);
            $this->endSession();
        }
        $this->actAs(null);
        $this->assertRejected('SELECT auth_reset(?, ?)', [$this->resident, 'x']);
        $this->endSession();
        $this->actAsJob([$this->devA]);
        $this->assertRejected('SELECT auth_reset(?, ?)', [$this->resident, 'x']);

        $this->assertSame('old', $this->storedHash($this->resident));
    }

    public function testSuperAdministratorResetsAnyone(): void
    {
        $this->setHash($this->adminB, 'old');
        $this->actAs($this->superUser);

        $this->assertTrue((bool)$this->appDb()->execute('SELECT auth_reset(?, ?)', [$this->adminB, 'reset'])->fetch()[0]);
    }

    // second factor -----------------------------------------------------------------------------------------------

    private function today(): string
    {
        return $this->businessDay();
    }

    public function testSecondFactorIsRequiredForActiveSuperAdministratorsAndEnrolledDevices(): void
    {
        $this->actAs(null);
        $required = fn(int $user): bool => (bool)$this->appDb()->execute(
            'SELECT auth_second_factor_required(?, ?::date)',
            [$user, $this->today()],
        )->fetch()[0];

        $this->assertTrue($required($this->superUser));
        $this->assertFalse($required($this->adminA));
        $this->assertFalse($required($this->loner));

        $this->db()->execute(
            "INSERT INTO user_totp (user_id, secret_encrypted, confirmed_at, created) VALUES (?, 's', NOW(), NOW())",
            [$this->adminA],
        );
        $this->assertTrue($required($this->adminA));
    }

    public function testTotpEnrolmentKeepsTheSameSecretUntilConfirmed(): void
    {
        $this->actAs(null);

        $first = $this->appDb()->execute('SELECT auth_totp_begin(?, ?)', [$this->adminA, 'cipher-1'])->fetch()[0];
        $second = $this->appDb()->execute('SELECT auth_totp_begin(?, ?)', [$this->adminA, 'cipher-2'])->fetch()[0];

        $this->assertSame('cipher-1', $first);
        $this->assertSame('cipher-1', $second);
        $this->assertSame('cipher-1', $this->appDb()->execute('SELECT auth_totp_pending_secret(?)', [$this->adminA])->fetch()[0]);
        $this->assertFalse((bool)$this->appDb()->execute('SELECT auth_totp_confirmed(?)', [$this->adminA])->fetch()[0]);
    }

    public function testTotpConfirmationBurnsTheStepAndClosesEnrolment(): void
    {
        $this->actAs(null);
        $this->appDb()->execute('SELECT auth_totp_begin(?, ?)', [$this->adminA, 'cipher'])->fetch();

        $this->assertTrue((bool)$this->appDb()->execute('SELECT auth_totp_confirm(?, ?)', [$this->adminA, 100])->fetch()[0]);
        $this->assertFalse((bool)$this->appDb()->execute('SELECT auth_totp_confirm(?, ?)', [$this->adminA, 101])->fetch()[0]);
        $this->assertTrue((bool)$this->appDb()->execute('SELECT auth_totp_confirmed(?)', [$this->adminA])->fetch()[0]);
        $this->assertNull($this->appDb()->execute('SELECT auth_totp_pending_secret(?)', [$this->adminA])->fetch()[0]);
        $this->assertNull($this->appDb()->execute('SELECT auth_totp_begin(?, ?)', [$this->adminA, 'other'])->fetch()[0]);

        $device = $this->appDb()->execute('SELECT * FROM auth_totp_device(?)', [$this->adminA])->fetch('assoc');
        $this->assertSame('cipher', $device['secret_encrypted']);
        $this->assertSame(100, (int)$device['last_used_step']);
    }

    public function testTotpStepsCannotBeReplayed(): void
    {
        $this->actAs(null);
        $this->appDb()->execute('SELECT auth_totp_begin(?, ?)', [$this->adminA, 'cipher'])->fetch();
        $this->appDb()->execute('SELECT auth_totp_confirm(?, ?)', [$this->adminA, 100])->fetch();

        $use = fn(int $step): bool => (bool)$this->appDb()->execute('SELECT auth_totp_use_step(?, ?)', [$this->adminA, $step])->fetch()[0];

        $this->assertFalse($use(100));
        $this->assertFalse($use(99));
        $this->assertTrue($use(101));
        $this->assertFalse($use(101));
    }

    public function testNoDeviceSecretIsReturnedForADisabledUser(): void
    {
        $this->db()->execute(
            "INSERT INTO user_totp (user_id, secret_encrypted, confirmed_at, created) VALUES (?, 's', NOW(), NOW())",
            [$this->disabledAdmin],
        );
        $this->actAs(null);

        $this->assertFalse($this->appDb()->execute('SELECT * FROM auth_totp_device(?)', [$this->disabledAdmin])->fetch());
    }

    public function testBackupCodesAreSpentOnce(): void
    {
        $this->actAs(null);
        $this->appDb()->execute('SELECT auth_backup_codes_replace(?, ARRAY[?, ?]::text[])', [$this->adminA, 'h1', 'h2']);

        $spend = fn(string $hash): bool => (bool)$this->appDb()->execute('SELECT auth_backup_code_consume(?, ?)', [$this->adminA, $hash])->fetch()[0];

        $this->assertTrue($spend('h1'));
        $this->assertFalse($spend('h1'));
        $this->assertFalse($spend('unknown'));
        $this->assertTrue($spend('h2'));

        $this->appDb()->execute('SELECT auth_backup_codes_replace(?, ARRAY[?]::text[])', [$this->adminA, 'h3']);
        $this->assertFalse($spend('h2'));
        $this->assertTrue($spend('h3'));
    }

    // audit trail -------------------------------------------------------------------------------------------------

    public function testSignInEventsCanBeLoggedWithoutASession(): void
    {
        $this->actAs(null);

        $this->appDb()->execute(
            'SELECT auth_event_log(?, ?, ?, ?, ?)',
            [null, 'ghost', 'password_failed', '203.0.113.9', 'agent'],
        );
        $this->appDb()->execute('SELECT auth_event_log(?, ?, ?, ?, ?)', [$this->adminA, 'admin_a', 'password_ok', null, null]);

        $this->commitSession();
        $rows = $this->db()->execute('SELECT user_id, username, event_type, ip, created FROM auth_events ORDER BY id')->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertNull($rows[0]['user_id']);
        $this->assertSame('ghost', $rows[0]['username']);
        $this->assertSame('203.0.113.9', $rows[0]['ip']);
        $this->assertSame($this->adminA, (int)$rows[1]['user_id']);
        $this->assertNotNull($rows[1]['created']);
    }

    public function testOnlyKnownEventTypesCanBeLogged(): void
    {
        $this->actAs(null);

        $this->assertRejected('SELECT auth_event_log(?, ?, ?, ?, ?)', [null, 'x', 'made_up', null, null]);
    }

    // user and grantee lookup -------------------------------------------------------------------------------------

    public function testTheRuntimeRoleCannotExecuteUserCreateEvenInTheJobContext(): void
    {
        // The job context is a setting the runtime role can set itself, so it is no protection: the privilege is.
        foreach ([[], [$this->devA]] as $developments) {
            $this->actAsJob($developments);
            $this->assertSame(
                '42501',
                $this->sqlStateOf('SELECT user_create(?, ?, true, ?::date)', ['rogue', 'h', $this->today()]),
            );
            $this->endSession();
        }
        $this->actAs(null);
        $this->appDb()->execute("SELECT set_config('app.context', 'job', true)");
        $this->assertSame(
            '42501',
            $this->sqlStateOf('SELECT user_create(?, ?, false, ?::date)', ['rogue', 'h', $this->today()]),
        );
        $this->endSession();

        $this->assertSame(0, (int)$this->db()->execute("SELECT COUNT(*) FROM users WHERE username = 'rogue'")->fetch()[0]);
    }

    public function testUserCreateRunsThroughTheOwnerLogin(): void
    {
        $id = (int)$this->db()->execute(
            'SELECT user_create(?, ?, false, ?::date)',
            ['new_user', 'new-hash', $this->today()],
        )->fetch()[0];

        $this->assertSame('new_user', $this->db()->execute('SELECT username FROM users WHERE id = ?', [$id])->fetch()[0]);
        $this->assertSame('new-hash', $this->storedHash($id));
        $this->assertSame(0, (int)$this->db()->execute('SELECT COUNT(*) FROM role_assignments WHERE user_id = ?', [$id])->fetch()[0]);
    }

    public function testUserCreateCanGrantTheGlobalRole(): void
    {
        $this->db()->execute('DELETE FROM role_assignments WHERE user_id = ?', [$this->superUser]);

        $id = (int)$this->db()->execute('SELECT user_create(?, ?, true, ?::date)', ['root', 'h', $this->today()])->fetch()[0];

        $row = $this->db()->execute(
            'SELECT r.code, ra.development_id, ra.starts_on FROM role_assignments ra JOIN roles r ON r.id = ra.role_id WHERE ra.user_id = ?',
            [$id],
        )->fetch('assoc');
        $this->assertSame('super_administrator', $row['code']);
        $this->assertNull($row['development_id']);
        $this->assertSame($this->today(), (string)$row['starts_on']);
    }

    public function testUserCreateRejectsADuplicateUsername(): void
    {
        $this->expectException(Throwable::class);

        $this->db()->execute('SELECT user_create(?, ?, false, ?::date)', ['admin_a', 'h', $this->today()]);
    }

    public function testGranteeLookupIsForAdministratorsAndSuperAdministrators(): void
    {
        $this->actAs($this->adminA);
        $this->assertSame($this->loner, (int)$this->appDb()->execute('SELECT role_assignment_grantee_id(?)', ['loner'])->fetch()[0]);
        $this->assertNull($this->appDb()->execute('SELECT role_assignment_grantee_id(?)', ['nobody'])->fetch()[0]);
        $this->assertNull($this->appDb()->execute('SELECT role_assignment_grantee_id(?)', ['disabled_admin'])->fetch()[0]);
        $this->endSession();

        $this->actAs($this->superUser);
        $this->assertSame($this->loner, (int)$this->appDb()->execute('SELECT role_assignment_grantee_id(?)', ['loner'])->fetch()[0]);
        $this->endSession();

        foreach ([null, $this->accountantA, $this->resident, $this->loner] as $user) {
            $this->actAs($user);
            $this->assertNull($this->appDb()->execute('SELECT role_assignment_grantee_id(?)', ['loner'])->fetch()[0]);
            $this->endSession();
        }
    }
}
