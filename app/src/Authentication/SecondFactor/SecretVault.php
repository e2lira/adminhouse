<?php
declare(strict_types=1);

namespace App\Authentication\SecondFactor;

use Cake\Core\Configure;
use Cake\Utility\Security;
use RuntimeException;
use SensitiveParameter;

/**
 * Protects second-factor material at rest with the key from the TOTP_ENCRYPTION_KEY environment variable.
 *
 * - TOTP secrets are encrypted (AES-256-CBC with an HMAC, via Cake's Security) so a database dump alone is not
 *   enough to clone an authenticator.
 * - Backup codes are stored as HMAC-SHA256 values keyed with a key derived from the same secret, so a dump alone
 *   cannot be used to brute-force the 50-bit codes offline, and a lookup by hash stays exact.
 *
 * The vault fails closed: without a key of at least 32 bytes nothing is encrypted or decrypted.
 */
final class SecretVault
{
    private const MIN_KEY_BYTES = 32;
    private const HMAC_SALT = 'kualitet.totp-secret.v1';
    private const BACKUP_CODE_CONTEXT = 'kualitet.backup-code.v1';

    private string $key;

    /**
     * @param string|null $key Raw key; defaults to the `Totp.encryptionKey` configuration value.
     * @throws \RuntimeException When the key is missing or shorter than 32 bytes.
     */
    public function __construct(#[SensitiveParameter] ?string $key = null)
    {
        $key ??= (string)Configure::read('Totp.encryptionKey');
        if (strlen($key) < self::MIN_KEY_BYTES) {
            throw new RuntimeException(
                'TOTP_ENCRYPTION_KEY must be set to a random value of at least 32 bytes.',
            );
        }
        $this->key = $key;
    }

    /**
     * @param string $plain Plain secret.
     * @return string Base64 text safe for a `text` column.
     */
    public function encrypt(#[SensitiveParameter] string $plain): string
    {
        return base64_encode(Security::encrypt($plain, $this->key, self::HMAC_SALT));
    }

    /**
     * @param string $stored Value produced by encrypt().
     * @return string Plain secret.
     * @throws \RuntimeException When the value was tampered with or encrypted with another key.
     */
    public function decrypt(string $stored): string
    {
        $raw = base64_decode($stored, true);
        $plain = $raw === false || $raw === '' ? null : Security::decrypt($raw, $this->key, self::HMAC_SALT);
        if ($plain === null) {
            throw new RuntimeException('The stored second-factor secret cannot be decrypted with the current key.');
        }

        return $plain;
    }

    /**
     * @param string $normalizedCode Backup code in canonical form (upper case, no separators).
     * @return string Lower-case hex HMAC-SHA256.
     */
    public function hashBackupCode(#[SensitiveParameter] string $normalizedCode): string
    {
        return hash_hmac('sha256', $normalizedCode, hash_hmac('sha256', self::BACKUP_CODE_CONTEXT, $this->key));
    }
}
