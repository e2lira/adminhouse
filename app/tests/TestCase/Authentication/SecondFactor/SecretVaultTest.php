<?php
declare(strict_types=1);

namespace App\Test\TestCase\Authentication\SecondFactor;

use App\Authentication\SecondFactor\SecretVault;
use Cake\TestSuite\TestCase;
use RuntimeException;

class SecretVaultTest extends TestCase
{
    private const KEY = 'unit-test-key-0123456789-abcdefghijklmnop';

    public function testRoundTripsASecretWithoutStoringItInTheClear(): void
    {
        $vault = new SecretVault(self::KEY);

        $stored = $vault->encrypt('JBSWY3DPEHPK3PXP');

        $this->assertNotSame('JBSWY3DPEHPK3PXP', $stored);
        $this->assertStringNotContainsString('JBSWY3DPEHPK3PXP', $stored);
        $this->assertSame('JBSWY3DPEHPK3PXP', $vault->decrypt($stored));
    }

    public function testEncryptionIsRandomised(): void
    {
        $vault = new SecretVault(self::KEY);

        $this->assertNotSame($vault->encrypt('JBSWY3DPEHPK3PXP'), $vault->encrypt('JBSWY3DPEHPK3PXP'));
    }

    public function testTamperedOrForeignCiphertextIsRejected(): void
    {
        $stored = (new SecretVault(self::KEY))->encrypt('JBSWY3DPEHPK3PXP');

        $this->expectException(RuntimeException::class);
        (new SecretVault(str_repeat('x', 40)))->decrypt($stored);
    }

    public function testRefusesAKeyShorterThan32Bytes(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TOTP_ENCRYPTION_KEY');
        new SecretVault('too short');
    }

    public function testRefusesAMissingKey(): void
    {
        $this->expectException(RuntimeException::class);
        new SecretVault('');
    }

    public function testUsesTheConfiguredKeyByDefault(): void
    {
        $this->assertSame(
            'JBSWY3DPEHPK3PXP',
            (new SecretVault())->decrypt((new SecretVault())->encrypt('JBSWY3DPEHPK3PXP')),
        );
    }

    public function testBackupCodeHashIsKeyedAndStable(): void
    {
        $a = new SecretVault(self::KEY);
        $b = new SecretVault(str_repeat('y', 40));

        $this->assertSame($a->hashBackupCode('ABCDEFGHJK'), $a->hashBackupCode('ABCDEFGHJK'));
        $this->assertNotSame($a->hashBackupCode('ABCDEFGHJK'), $a->hashBackupCode('ABCDEFGHJL'));
        $this->assertNotSame($a->hashBackupCode('ABCDEFGHJK'), $b->hashBackupCode('ABCDEFGHJK'));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $a->hashBackupCode('ABCDEFGHJK'));
    }
}
