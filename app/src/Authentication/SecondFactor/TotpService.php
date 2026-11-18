<?php
declare(strict_types=1);

namespace App\Authentication\SecondFactor;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use OTPHP\TOTP;

/**
 * RFC 6238 time-based one-time passwords: SHA-1, 6 digits, 30 second steps.
 *
 * Pure computation, no I/O. Replay protection needs state, so matchStep() returns the time step that matched and
 * leaves it to the caller to remember it.
 */
final class TotpService
{
    public const PERIOD = 30;
    public const DIGITS = 6;

    /**
     * Steps accepted on either side of the current one, to absorb clock drift between server and phone.
     */
    public const WINDOW = 1;

    private const SECRET_BYTES = 20;
    private const ISSUER = 'Kualitet';

    /**
     * @return string New random secret, Base32 (160 bits, the RFC 4226 recommendation).
     */
    public function generateSecret(): string
    {
        return TOTP::generate(null, self::SECRET_BYTES)->getSecret();
    }

    /**
     * @param string $secret Base32 secret.
     * @param string $username Account label shown in the authenticator app.
     * @return string `otpauth://` URI for the QR code.
     */
    public function provisioningUri(string $secret, string $username): string
    {
        $totp = $this->totp($secret);
        $totp->setLabel($username);
        $totp->setIssuer(self::ISSUER);

        return $totp->getProvisioningUri();
    }

    /**
     * Render a QR code as an inline SVG element. Nothing is fetched from, or sent to, a third party.
     *
     * @param string $content Text to encode.
     * @return string SVG markup without an XML prolog.
     */
    public function qrSvg(string $content): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(220, 2), new SvgImageBackEnd()));
        $svg = $writer->writeString($content);

        return trim((string)preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg));
    }

    /**
     * @param string $secret Base32 secret.
     * @param int $timestamp Unix time.
     * @return string The code valid at that moment.
     */
    public function codeAt(string $secret, int $timestamp): string
    {
        return $this->totp($secret)->at($timestamp);
    }

    /**
     * Find the time step a submitted code belongs to.
     *
     * @param string $secret Base32 secret.
     * @param string $code Code typed by the user; spaces are ignored.
     * @param int|null $afterStep Last step already accepted; only a strictly later step matches (replay protection).
     * @param int|null $now Unix time, for tests; defaults to the clock.
     * @return int|null Matching step, or null when the code is malformed, outside the window, or already used.
     */
    public function matchStep(string $secret, string $code, ?int $afterStep = null, ?int $now = null): ?int
    {
        $code = str_replace(' ', '', $code);
        if (preg_match('/^\d{' . self::DIGITS . '}$/', $code) !== 1) {
            return null;
        }

        $current = intdiv($now ?? time(), self::PERIOD);
        $totp = $this->totp($secret);

        // Every candidate is compared, so the time taken does not reveal which step (if any) was close.
        $matched = null;
        for ($step = $current - self::WINDOW; $step <= $current + self::WINDOW; $step++) {
            $equal = hash_equals($totp->at($step * self::PERIOD), $code);
            if ($equal && ($afterStep === null || $step > $afterStep)) {
                $matched = $step;
            }
        }

        return $matched;
    }

    /**
     * @param string $secret Base32 secret.
     * @return \OTPHP\TOTP
     */
    private function totp(string $secret): TOTP
    {
        return TOTP::create($secret, self::PERIOD, 'sha1', self::DIGITS);
    }
}
