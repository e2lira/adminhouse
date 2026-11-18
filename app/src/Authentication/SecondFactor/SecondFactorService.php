<?php
declare(strict_types=1);

namespace App\Authentication\SecondFactor;

use App\Clock\BusinessClock;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use LogicException;

/**
 * Second-factor state of a user: who needs it, the TOTP device and the one-time backup codes.
 *
 * Every state change that must happen exactly once (accepting a time step, spending a backup code) is a single
 * conditional UPDATE inside a definer function that reports whether it changed a row, so two concurrent
 * submissions of the same code cannot both win.
 *
 * The runtime database role has no privilege on the second-factor tables: every read and write goes through the
 * `auth_*` definer functions (RLS-SEC-001). They take the user explicitly because the pre-auth steps run before an
 * identity exists.
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
     * @param \Cake\Database\Connection|null $sideConnection Autocommit connection for spending a step or a code;
     *  defaults to `auth_side`.
     * @param \App\Authentication\SecondFactor\TotpService|null $totp TOTP calculator.
     * @param \App\Authentication\SecondFactor\SecretVault|null $vault Secret vault; created on first use so
     *  that asking who needs a second factor does not require the encryption key.
     * @param \App\Clock\BusinessClock|null $clock Source of "today"; defaults to the business clock.
     */
    public function __construct(
        private ?Connection $connection = null,
        private ?Connection $sideConnection = null,
        private ?TotpService $totp = null,
        ?SecretVault $vault = null,
        private ?BusinessClock $clock = null,
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
        $today = ($this->clock ??= new BusinessClock())->today()->format('Y-m-d');

        return (bool)$this->connection()->execute(
            'SELECT auth_second_factor_required(:user_id, CAST(:today AS date))',
            ['user_id' => $userId, 'today' => $today],
            ['user_id' => 'integer', 'today' => 'string'],
        )->fetch()[0];
    }

    /**
     * @param int $userId User id.
     * @return bool
     */
    public function hasConfirmedDevice(int $userId): bool
    {
        return (bool)$this->connection()->execute(
            'SELECT auth_totp_confirmed(:user_id)',
            ['user_id' => $userId],
            ['user_id' => 'integer'],
        )->fetch()[0];
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

        // The database keeps the first secret until it is confirmed; null means there is nothing to enrol.
        $row = $this->connection()->execute(
            'SELECT auth_totp_begin(:user_id, :secret)',
            ['user_id' => $userId, 'secret' => $this->vault()->encrypt($this->totp()->generateSecret())],
            ['user_id' => 'integer', 'secret' => 'string'],
        )->fetch();
        if ($row === false || $row[0] === null) {
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
            // The function locks the pending row until the transaction ends.
            $stored = $this->connection()->execute(
                'SELECT auth_totp_pending_secret(:user_id)',
                ['user_id' => $userId],
                ['user_id' => 'integer'],
            )->fetch();
            if ($stored === false || $stored[0] === null) {
                return null;
            }

            $step = $this->totp()->matchStep($this->vault()->decrypt((string)$stored[0]), $code);
            if ($step === null) {
                return null;
            }

            $confirmed = (bool)$this->connection()->execute(
                'SELECT auth_totp_confirm(:user_id, :step)',
                ['user_id' => $userId, 'step' => $step],
                ['user_id' => 'integer', 'step' => 'integer'],
            )->fetch()[0];
            if (!$confirmed) {
                return null;
            }

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
            'SELECT secret_encrypted, last_used_step FROM auth_totp_device(:user_id)',
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

        // Only the request that moves last_used_step forward wins; a concurrent replay changes no row. It is spent on
        // the autocommit connection: if the request later fails and rolls back, the code must stay spent.
        return (bool)$this->sideConnection()->execute(
            'SELECT auth_totp_use_step(:user_id, :step)',
            ['user_id' => $userId, 'step' => $step],
            ['user_id' => 'integer', 'step' => 'integer'],
        )->fetch()[0];
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

        // Spent on the autocommit connection, like a TOTP step: a rolled-back request must not give the code back.
        return (bool)$this->sideConnection()->execute(
            'SELECT auth_backup_code_consume(:user_id, :hash)',
            ['user_id' => $userId, 'hash' => $this->vault()->hashBackupCode($normalized)],
            ['user_id' => 'integer', 'hash' => 'string'],
        )->fetch()[0];
    }

    /**
     * Replace every backup code of the user with a fresh set.
     *
     * @param int $userId User id.
     * @return list<string> The new codes in display form (XXXXX-XXXXX). They are not recoverable later.
     */
    public function replaceBackupCodes(int $userId): array
    {
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

        $hashes = [];
        foreach (array_keys($codes) as $raw) {
            $hashes[] = $this->vault()->hashBackupCode((string)$raw);
        }

        // One call deletes the old set and stores the new one, atomically.
        $this->connection()->execute(
            'SELECT auth_backup_codes_replace(:user_id, CAST(:hashes AS text[]))',
            ['user_id' => $userId, 'hashes' => $this->pgArray($hashes)],
            ['user_id' => 'integer', 'hashes' => 'string'],
        );

        return array_values($codes);
    }

    /**
     * A PostgreSQL array literal of text values, passed as ONE bound parameter.
     *
     * @param list<string> $values Values.
     * @return string
     */
    private function pgArray(array $values): string
    {
        $quoted = array_map(
            static fn(string $value): string => '"' . addcslashes($value, '"\\') . '"',
            $values,
        );

        return '{' . implode(',', $quoted) . '}';
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
     * @return \Cake\Database\Connection
     */
    private function sideConnection(): Connection
    {
        /** @var \Cake\Database\Connection $connection */
        $connection = $this->sideConnection ??= ConnectionManager::get('auth_side');

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
