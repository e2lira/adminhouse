<?php
declare(strict_types=1);

namespace App\Authentication\SecondFactor;

use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Date;
use LogicException;

/**
 * Second-factor state of a user: who needs it, the TOTP device and the one-time backup codes.
 *
 * Every state change that must happen exactly once (accepting a time step, spending a backup code) is a single
 * conditional UPDATE whose row count is checked, so two concurrent submissions of the same code cannot both win.
 */
final class SecondFactorService
{
    public const BACKUP_CODE_COUNT = 10;

    /**
     * Characters without look-alikes (no 0, 1, I or O): 32 symbols, so 5 bits each.
     */
    private const BACKUP_CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    private const BACKUP_CODE_HALF = 5;

    private ?SecretVault $vault;

    /**
     * @param \Cake\Database\Connection|null $connection Connection; defaults to `default`.
     * @param \App\Authentication\SecondFactor\TotpService|null $totp TOTP calculator.
     * @param \App\Authentication\SecondFactor\SecretVault|null $vault Secret vault; created on first use so
     *  that asking who needs a second factor does not require the encryption key.
     */
    public function __construct(
        private ?Connection $connection = null,
        private ?TotpService $totp = null,
        ?SecretVault $vault = null,
    ) {
        $this->vault = $vault;
    }

    /**
     * A user needs a second factor when they hold a super_administrator assignment that is in force today
     * (RB-R5), or when they already confirmed a TOTP device (a device, once enrolled, is never optional).
     *
     * @param int $userId User id.
     * @return bool
     */
    public function isRequired(int $userId): bool
    {
        if ($this->hasConfirmedDevice($userId)) {
            return true;
        }

        $today = Date::today()->format('Y-m-d');

        return $this->connection()->execute(
            'SELECT 1
             FROM role_assignments ra
             JOIN roles r ON r.id = ra.role_id AND r.code = \'super_administrator\'
             WHERE ra.user_id = :user_id
               AND ra.starts_on <= :today
               AND (ra.ends_on IS NULL OR ra.ends_on >= :today_end)
             LIMIT 1',
            ['user_id' => $userId, 'today' => $today, 'today_end' => $today],
            ['user_id' => 'integer', 'today' => 'string', 'today_end' => 'string'],
        )->fetch() !== false;
    }

    /**
     * @param int $userId User id.
     * @return bool
     */
    public function hasConfirmedDevice(int $userId): bool
    {
        return $this->connection()->execute(
            'SELECT 1 FROM user_totp WHERE user_id = :user_id AND confirmed_at IS NOT NULL',
            ['user_id' => $userId],
            ['user_id' => 'integer'],
        )->fetch() !== false;
    }

    /**
     * Return the enrolment material for a user without a confirmed device, creating the (unconfirmed) secret on
     * first use. Reloading the page keeps the same secret, so a code already scanned into an app stays valid.
     *
     * @param int $userId User id.
     * @param string $username Label for the authenticator app.
     * @return array{secret: string, svg: string}
     * @throws \LogicException When the user already has a confirmed device.
     */
    public function beginEnrolment(int $userId, string $username): array
    {
        if ($this->hasConfirmedDevice($userId)) {
            throw new LogicException('The user already has a confirmed TOTP device.');
        }

        $this->connection()->execute(
            'INSERT INTO user_totp (user_id, secret_encrypted, created)
             VALUES (:user_id, :secret, NOW())
             ON CONFLICT (user_id) DO NOTHING',
            ['user_id' => $userId, 'secret' => $this->vault()->encrypt($this->totp()->generateSecret())],
            ['user_id' => 'integer', 'secret' => 'string'],
        );

        $row = $this->connection()->execute(
            'SELECT secret_encrypted FROM user_totp WHERE user_id = :user_id AND confirmed_at IS NULL',
            ['user_id' => $userId],
            ['user_id' => 'integer'],
        )->fetch();
        if ($row === false) {
            throw new LogicException('The user already has a confirmed TOTP device.');
        }

        $secret = $this->vault()->decrypt((string)$row[0]);

        return [
            'secret' => $secret,
            'svg' => $this->totp()->qrSvg($this->totp()->provisioningUri($secret, $username)),
        ];
    }

    /**
     * Confirm the unconfirmed device with a code from it, and issue the first set of backup codes.
     *
     * @param int $userId User id.
     * @param string $code Code typed by the user.
     * @return list<string>|null The plain backup codes (shown once), or null when the code is wrong.
     */
    public function confirmEnrolment(int $userId, string $code): ?array
    {
        return $this->connection()->transactional(function () use ($userId, $code): ?array {
            $stored = $this->connection()->execute(
                'SELECT secret_encrypted FROM user_totp
                 WHERE user_id = :user_id AND confirmed_at IS NULL FOR UPDATE',
                ['user_id' => $userId],
                ['user_id' => 'integer'],
            )->fetch();
            if ($stored === false) {
                return null;
            }

            $step = $this->totp()->matchStep($this->vault()->decrypt((string)$stored[0]), $code);
            if ($step === null) {
                return null;
            }

            $this->connection()->execute(
                'UPDATE user_totp SET confirmed_at = NOW(), last_used_step = :step WHERE user_id = :user_id',
                ['step' => $step, 'user_id' => $userId],
                ['step' => 'integer', 'user_id' => 'integer'],
            );

            return $this->replaceBackupCodes($userId);
        });
    }

    /**
     * Check a TOTP code against the confirmed device and burn its time step.
     *
     * @param int $userId User id.
     * @param string $code Six digit code.
     * @return bool
     */
    public function verifyTotp(int $userId, string $code): bool
    {
        $row = $this->connection()->execute(
            'SELECT secret_encrypted, last_used_step FROM user_totp
             WHERE user_id = :user_id AND confirmed_at IS NOT NULL',
            ['user_id' => $userId],
            ['user_id' => 'integer'],
        )->fetch();
        if ($row === false) {
            return false;
        }

        $last = $row[1] === null ? null : (int)$row[1];
        $step = $this->totp()->matchStep($this->vault()->decrypt((string)$row[0]), $code, $last);
        if ($step === null) {
            return false;
        }

        // Only the request that moves last_used_step forward wins; a concurrent replay updates zero rows.
        $statement = $this->connection()->execute(
            'UPDATE user_totp SET last_used_step = :step
             WHERE user_id = :user_id AND (last_used_step IS NULL OR last_used_step < :step_again)',
            ['step' => $step, 'user_id' => $userId, 'step_again' => $step],
            ['step' => 'integer', 'user_id' => 'integer', 'step_again' => 'integer'],
        );

        return $statement->rowCount() === 1;
    }

    /**
     * Spend a backup code. Exactly one request can spend a given code.
     *
     * @param int $userId User id.
     * @param string $code Code as typed (any case, with or without the hyphen or spaces).
     * @return bool
     */
    public function verifyBackupCode(int $userId, string $code): bool
    {
        $normalized = strtoupper((string)preg_replace('/[\s-]+/', '', $code));
        $pattern = sprintf('/^[%s]{%d}$/', self::BACKUP_CODE_ALPHABET, self::BACKUP_CODE_HALF * 2);
        if (preg_match($pattern, $normalized) !== 1) {
            return false;
        }

        $statement = $this->connection()->execute(
            'UPDATE user_backup_codes SET used_at = NOW()
             WHERE user_id = :user_id AND code_hash = :hash AND used_at IS NULL',
            ['user_id' => $userId, 'hash' => $this->vault()->hashBackupCode($normalized)],
            ['user_id' => 'integer', 'hash' => 'string'],
        );

        return $statement->rowCount() === 1;
    }

    /**
     * Replace every backup code of the user with a fresh set.
     *
     * @param int $userId User id.
     * @return list<string> The new codes in display form (XXXXX-XXXXX). They are not recoverable later.
     */
    public function replaceBackupCodes(int $userId): array
    {
        return $this->connection()->transactional(function () use ($userId): array {
            $this->connection()->execute(
                'DELETE FROM user_backup_codes WHERE user_id = :user_id',
                ['user_id' => $userId],
                ['user_id' => 'integer'],
            );

            // Keyed by the raw code so a (vanishingly unlikely) duplicate is drawn again instead of kept.
            $codes = [];
            for ($issued = 0; $issued < self::BACKUP_CODE_COUNT;) {
                $raw = $this->randomCode();
                if (isset($codes[$raw])) {
                    continue;
                }
                $codes[$raw] = substr($raw, 0, self::BACKUP_CODE_HALF) . '-' . substr($raw, self::BACKUP_CODE_HALF);
                $issued++;
            }

            foreach (array_keys($codes) as $raw) {
                $this->connection()->execute(
                    'INSERT INTO user_backup_codes (user_id, code_hash, created) VALUES (:user_id, :hash, NOW())',
                    ['user_id' => $userId, 'hash' => $this->vault()->hashBackupCode((string)$raw)],
                    ['user_id' => 'integer', 'hash' => 'string'],
                );
            }

            return array_values($codes);
        });
    }

    /**
     * @return string Ten random characters from the backup code alphabet.
     */
    private function randomCode(): string
    {
        $code = '';
        $last = strlen(self::BACKUP_CODE_ALPHABET) - 1;
        for ($i = 0; $i < self::BACKUP_CODE_HALF * 2; $i++) {
            $code .= self::BACKUP_CODE_ALPHABET[random_int(0, $last)];
        }

        return $code;
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

    /**
     * @return \App\Authentication\SecondFactor\TotpService
     */
    private function totp(): TotpService
    {
        return $this->totp ??= new TotpService();
    }

    /**
     * @return \App\Authentication\SecondFactor\SecretVault
     */
    private function vault(): SecretVault
    {
        return $this->vault ??= new SecretVault();
    }
}
