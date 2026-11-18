<?php
declare(strict_types=1);

namespace App\Test\TestCase\Authentication;

use App\Authentication\AuthEventLogger;
use App\Authentication\SecondFactor\SecondFactorService;
use App\Test\Support\DatabaseTestCase;
use App\Test\Support\SecondFactorTestTrait;

/**
 * What a sign-in must not lose when the request around it fails (RLS-SEC-001): the request transaction rolls back on
 * an exception, but a spent TOTP step, a spent backup code and the audit trail are written on the separate
 * `auth_side` connection, in autocommit, so a forced error cannot replay a code or erase a failed attempt.
 */
class AuditAndReplayOutliveTheRequestTest extends DatabaseTestCase
{
    use SecondFactorTestTrait;

    public function testASpentTotpStepStaysSpentWhenTheRequestRollsBack(): void
    {
        $userId = $this->createUser('totp_user', null);
        $secret = $this->enrollTotp($userId);
        $code = $this->totpCode($secret);

        $this->actAs($userId);
        $this->assertTrue((new SecondFactorService())->verifyTotp($userId, $code));
        $this->endSession();

        $this->assertNotNull(
            $this->db()->execute('SELECT last_used_step FROM user_totp WHERE user_id = ?', [$userId])->fetch()[0],
        );
        $this->assertFalse((new SecondFactorService())->verifyTotp($userId, $code), 'The same code must not work twice.');
    }

    public function testASpentBackupCodeStaysSpentWhenTheRequestRollsBack(): void
    {
        $userId = $this->createUser('backup_user', null);
        $codes = $this->issueBackupCodes($userId);

        $this->actAs($userId);
        $this->assertTrue((new SecondFactorService())->verifyBackupCode($userId, $codes[0]));
        $this->endSession();

        $this->assertSame(
            1,
            (int)$this->db()->execute('SELECT COUNT(*) FROM user_backup_codes WHERE user_id = ? AND used_at IS NOT NULL', [$userId])->fetch()[0],
        );
        $this->assertFalse((new SecondFactorService())->verifyBackupCode($userId, $codes[0]));
    }

    public function testTheAuditRowStaysWhenTheRequestRollsBack(): void
    {
        $userId = $this->createUser('audited', null);

        $this->actAs(null);
        (new AuthEventLogger())->log(AuthEventLogger::PASSWORD_FAILED, $userId, 'audited');
        $this->endSession();

        $this->assertSame(
            1,
            (int)$this->db()->execute("SELECT COUNT(*) FROM auth_events WHERE username = 'audited'")->fetch()[0],
        );
    }
}
