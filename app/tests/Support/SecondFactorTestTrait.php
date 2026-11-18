<?php
declare(strict_types=1);

namespace App\Test\Support;

use App\Authentication\SecondFactor\SecondFactorService;
use App\Authentication\SecondFactor\SecretVault;
use App\Authentication\SecondFactor\TotpService;

/**
 * Helpers to set up super administrators and TOTP enrolment in integration tests that extend DatabaseTestCase
 * and also use SignsInTrait (for the shared PASSWORD constant).
 */
trait SecondFactorTestTrait
{
    /**
     * @param string $username Username.
     * @return int User id.
     */
    protected function createSuperAdministrator(string $username): int
    {
        $userId = $this->createUser($username, self::PASSWORD);
        $this->assignRole($userId, 'super_administrator', null);

        return $userId;
    }

    /**
     * Give a user a confirmed TOTP device, as if enrolment had finished.
     *
     * @param int $userId User id.
     * @return string Base32 secret of the device.
     */
    protected function enrollTotp(int $userId): string
    {
        $secret = (new TotpService())->generateSecret();
        $this->db()->execute(
            'INSERT INTO user_totp (user_id, secret_encrypted, confirmed_at, created)
             VALUES (?, ?, NOW(), NOW())',
            [$userId, (new SecretVault())->encrypt($secret)],
        );

        return $secret;
    }

    /**
     * @param int $userId User id.
     * @return list<string> Plain backup codes.
     */
    protected function issueBackupCodes(int $userId): array
    {
        return (new SecondFactorService())->replaceBackupCodes($userId);
    }

    /**
     * @param string $secret Base32 secret.
     * @param int $stepOffset Steps away from the current one (-1 previous, 1 next).
     * @return string Six digit code.
     */
    protected function totpCode(string $secret, int $stepOffset = 0): string
    {
        return (new TotpService())->codeAt($secret, time() + $stepOffset * TotpService::PERIOD);
    }

    /**
     * @param int $userId User id.
     * @return string Base32 secret stored for the user (decrypted).
     */
    protected function storedSecret(int $userId): string
    {
        $stored = (string)$this->db()->execute(
            'SELECT secret_encrypted FROM user_totp WHERE user_id = ?',
            [$userId],
        )->fetch()[0];

        return (new SecretVault())->decrypt($stored);
    }

    /**
     * @param string $type Event type.
     * @return int
     */
    protected function countAuthEvents(string $type): int
    {
        return (int)$this->db()->execute(
            'SELECT COUNT(*) FROM auth_events WHERE event_type = ?',
            [$type],
        )->fetch()[0];
    }
}
