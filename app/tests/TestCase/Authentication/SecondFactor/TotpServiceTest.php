<?php
declare(strict_types=1);

namespace App\Test\TestCase\Authentication\SecondFactor;

use App\Authentication\SecondFactor\TotpService;
use Cake\TestSuite\TestCase;

/**
 * RFC 6238 behaviour: SHA1, 6 digits, 30 second steps, one step of tolerance either way.
 */
class TotpServiceTest extends TestCase
{
    /**
     * Base32 of the RFC 6238 test secret "12345678901234567890".
     */
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    private TotpService $totp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = new TotpService();
    }

    public function testMatchesTheRfc6238VectorsTruncatedToSixDigits(): void
    {
        $this->assertSame('287082', $this->totp->codeAt(self::RFC_SECRET, 59));
        $this->assertSame('081804', $this->totp->codeAt(self::RFC_SECRET, 1111111109));
        $this->assertSame('050471', $this->totp->codeAt(self::RFC_SECRET, 1111111111));
    }

    public function testGeneratedSecretsAreRandomBase32WithAtLeast128Bits(): void
    {
        $first = $this->totp->generateSecret();

        $this->assertMatchesRegularExpression('/^[A-Z2-7]{26,}$/', $first);
        $this->assertNotSame($first, $this->totp->generateSecret());
    }

    public function testAcceptsTheCurrentAndTheAdjacentSteps(): void
    {
        $now = 1700000010;
        $step = intdiv($now, 30);

        $this->assertSame($step, $this->totp->matchStep(self::RFC_SECRET, $this->totp->codeAt(self::RFC_SECRET, $now), null, $now));
        $this->assertSame($step - 1, $this->totp->matchStep(self::RFC_SECRET, $this->totp->codeAt(self::RFC_SECRET, $now - 30), null, $now));
        $this->assertSame($step + 1, $this->totp->matchStep(self::RFC_SECRET, $this->totp->codeAt(self::RFC_SECRET, $now + 30), null, $now));
    }

    public function testRejectsCodesTwoStepsAway(): void
    {
        $now = 1700000010;

        $this->assertNull($this->totp->matchStep(self::RFC_SECRET, $this->totp->codeAt(self::RFC_SECRET, $now - 60), null, $now));
        $this->assertNull($this->totp->matchStep(self::RFC_SECRET, $this->totp->codeAt(self::RFC_SECRET, $now + 60), null, $now));
    }

    public function testRejectsAStepThatIsNotNewerThanTheLastUsedOne(): void
    {
        $now = 1700000010;
        $step = intdiv($now, 30);
        $code = $this->totp->codeAt(self::RFC_SECRET, $now);

        $this->assertNull($this->totp->matchStep(self::RFC_SECRET, $code, $step, $now));
        $this->assertNull($this->totp->matchStep(self::RFC_SECRET, $code, $step + 1, $now));
        $this->assertSame($step, $this->totp->matchStep(self::RFC_SECRET, $code, $step - 1, $now));
    }

    public function testRejectsMalformedCodes(): void
    {
        $now = 1700000010;

        foreach (['', '12345', '1234567', 'abcdef', '12 345'] as $bad) {
            $this->assertNull($this->totp->matchStep(self::RFC_SECRET, $bad, null, $now), "'$bad'");
        }
    }

    public function testToleratesSpacesInsideAHumanTypedCode(): void
    {
        $now = 1700000010;
        $code = $this->totp->codeAt(self::RFC_SECRET, $now);
        $spaced = substr($code, 0, 3) . ' ' . substr($code, 3);

        $this->assertSame(intdiv($now, 30), $this->totp->matchStep(self::RFC_SECRET, $spaced, null, $now));
    }

    public function testProvisioningUriDescribesAnRfcCompatibleToken(): void
    {
        $uri = $this->totp->provisioningUri(self::RFC_SECRET, 'alice');

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=' . self::RFC_SECRET, $uri);
        $this->assertStringContainsString('issuer=Kualitet', $uri);
        $this->assertStringContainsString('alice', $uri);
    }

    public function testQrCodeIsAnInlineSvgWithoutExternalReferences(): void
    {
        $svg = $this->totp->qrSvg($this->totp->provisioningUri(self::RFC_SECRET, 'alice'));

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringNotContainsString('<?xml', $svg);
        $this->assertStringNotContainsString('href=', $svg);
        $this->assertStringNotContainsString('<image', $svg);
        $this->assertStringNotContainsString('<script', $svg);
    }
}
