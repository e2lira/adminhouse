<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\Support\DatabaseTestCase;
use App\Test\Support\SecondFactorTestTrait;
use App\Test\Support\SignsInTrait;
use Cake\TestSuite\IntegrationTestTrait;

/**
 * TOTP second factor (RB-R5, AC-RB-4): password alone never yields an identity for a super administrator or
 * for anyone with a confirmed TOTP device.
 */
class TwoFactorSignInTest extends DatabaseTestCase
{
    use IntegrationTestTrait;
    use SecondFactorTestTrait;
    use SignsInTrait;

    private const BACKUP_CODE_PATTERN = '[A-HJ-NP-Z2-9]{5}-[A-HJ-NP-Z2-9]{5}';

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
    }

    /**
     * Submit the password step and keep the session for the next request.
     */
    private function passwordStep(string $username, string $password = self::PASSWORD): void
    {
        $this->post('/login', ['username' => $username, 'password' => $password]);
        $this->carrySession();
    }

    private function submitCode(string $code): void
    {
        $this->post('/login/verify', ['code' => $code]);
        $this->carrySession();
    }

    private function assertSignedIn(): void
    {
        $this->get('/');
        $this->assertResponseOk();
        $this->carrySession();
    }

    private function assertAnonymous(): void
    {
        $this->get('/');
        $this->assertRedirectContains('/login');
        $this->carrySession();
    }

    private function logOut(): void
    {
        $this->post('/logout');
        $this->carrySession();
    }

    // --- who must use a second factor -----------------------------------------------------------------------

    public function testSuperAdministratorWithTotpIsSentToVerifyInsteadOfSignedIn(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $this->enrollTotp($userId);

        $this->post('/login', ['username' => 'root', 'password' => self::PASSWORD]);

        $this->assertRedirect('/login/verify');
        $this->assertArrayNotHasKey('Auth', $_SESSION ?? [], 'no identity may exist before the code is checked');
        $this->assertNull($this->_controller->getRequest()->getAttribute('identity'));
    }

    public function testPendingStateCannotReachHomeOrDevelopments(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $this->enrollTotp($userId);
        $this->passwordStep('root');

        $this->get('/');
        $this->assertRedirectContains('/login');

        $this->get('/developments');
        $this->assertRedirectContains('/login');
    }

    public function testUserWithConfirmedTotpButNoSuperRoleAlsoNeedsTheCode(): void
    {
        $userId = $this->createUser('carol', self::PASSWORD);
        $this->enrollTotp($userId);

        $this->post('/login', ['username' => 'carol', 'password' => self::PASSWORD]);

        $this->assertRedirect('/login/verify');
    }

    public function testUserWithoutTotpAndWithoutSuperRoleSignsInWithPasswordOnly(): void
    {
        $userId = $this->createUser('dave', self::PASSWORD);
        $this->assignRole($userId, 'administrator', $this->createDevelopment());

        $this->passwordStep('dave');

        $this->assertRedirect('/');
        $this->assertSignedIn();
    }

    public function testEndedSuperAdministratorAssignmentDoesNotForceTotp(): void
    {
        $userId = $this->createUser('erin', self::PASSWORD);
        $this->assignRole($userId, 'super_administrator', null, -30, -1);

        $this->post('/login', ['username' => 'erin', 'password' => self::PASSWORD]);

        $this->assertRedirect('/');
    }

    public function testSuperAdministratorWithoutTotpIsSentToEnrolment(): void
    {
        $this->createSuperAdministrator('root');

        $this->post('/login', ['username' => 'root', 'password' => self::PASSWORD]);

        $this->assertRedirect('/login/enroll');
        $this->assertArrayNotHasKey('Auth', $_SESSION ?? []);
        $this->carrySession();
        $this->assertAnonymous();
    }

    public function testWrongPasswordNeverStartsAPendingState(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $this->enrollTotp($userId);

        $this->post('/login', ['username' => 'root', 'password' => 'nope']);

        $this->assertResponseOk();
        $this->assertResponseContains('Usuario o contraseña incorrectos');
        $this->assertArrayNotHasKey('TotpPending', $_SESSION ?? []);
    }

    public function testRedirectTargetSurvivesTheSecondFactorStep(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $secret = $this->enrollTotp($userId);

        $this->post('/login?redirect=%2Fdevelopments', ['username' => 'root', 'password' => self::PASSWORD]);
        $this->carrySession();
        $this->submitCode($this->totpCode($secret));

        $this->assertRedirect('/developments');
    }

    // --- verifying the code ---------------------------------------------------------------------------------

    public function testValidCodeCompletesSignIn(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $secret = $this->enrollTotp($userId);
        $this->passwordStep('root');

        $this->post('/login/verify', ['code' => $this->totpCode($secret)]);

        $this->assertRedirect('/');
        $this->assertSame($userId, (int)($_SESSION['Auth'] ?? 0));
        $this->assertArrayNotHasKey('TotpPending', $_SESSION ?? []);
        $this->carrySession();
        $this->assertSignedIn();
        $this->assertSame(1, $this->countAuthEvents('totp_ok'));
    }

    public function testAdjacentStepsAreAccepted(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $secret = $this->enrollTotp($userId);
        $this->passwordStep('root');

        $this->submitCode($this->totpCode($secret, -1));

        $this->assertRedirect('/');
    }

    public function testCodeTwoStepsAwayIsRejected(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $secret = $this->enrollTotp($userId);
        $this->passwordStep('root');

        $this->post('/login/verify', ['code' => $this->totpCode($secret, -2)]);

        $this->assertResponseOk();
        $this->assertResponseContains('Código incorrecto');
        $this->carrySession();
        $this->assertAnonymous();
    }

    public function testWrongCodeFailsAndKeepsTheUserPending(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $this->enrollTotp($userId);
        $this->passwordStep('root');

        $this->post('/login/verify', ['code' => '000000']);

        $this->assertResponseOk();
        $this->assertResponseContains('Código incorrecto');
        $this->assertArrayNotHasKey('Auth', $_SESSION ?? []);
        $this->assertArrayHasKey('TotpPending', $_SESSION ?? []);
        $this->assertSame(1, $this->countAuthEvents('totp_failed'));
    }

    public function testReplayedCodeIsRejected(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $secret = $this->enrollTotp($userId);
        $code = $this->totpCode($secret);
        $this->passwordStep('root');
        $this->submitCode($code);
        $this->assertRedirect('/');
        $this->logOut();

        $this->passwordStep('root');
        $this->post('/login/verify', ['code' => $code]);

        $this->assertResponseOk();
        $this->assertResponseContains('Código incorrecto');
        $this->carrySession();
        $this->assertAnonymous();
    }

    public function testAnOlderStepThanTheLastUsedOneIsRejectedButALaterOneIsAccepted(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $secret = $this->enrollTotp($userId);
        $this->passwordStep('root');
        $this->submitCode($this->totpCode($secret));
        $this->logOut();

        $this->passwordStep('root');
        $this->submitCode($this->totpCode($secret, -1));
        $this->assertResponseContains('Código incorrecto');

        $this->submitCode($this->totpCode($secret, 1));
        $this->assertRedirect('/');
    }

    public function testExpiredPendingStateIsRejectedEvenWithAValidCode(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $secret = $this->enrollTotp($userId);
        $this->passwordStep('root');
        $_SESSION['TotpPending']['expires'] = time() - 1;
        $this->carrySession();

        $this->post('/login/verify', ['code' => $this->totpCode($secret)]);

        $this->assertRedirect('/login');
        $this->carrySession();
        $this->assertAnonymous();
        $this->assertSame(0, $this->countAuthEvents('totp_ok'));
    }

    public function testPendingStateExpiresAfterAboutFiveMinutes(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $this->enrollTotp($userId);
        $this->passwordStep('root');

        $remaining = $_SESSION['TotpPending']['expires'] - time();

        $this->assertGreaterThan(240, $remaining);
        $this->assertLessThanOrEqual(300, $remaining);
    }

    public function testFiveFailuresDiscardThePendingStateAndRequireThePasswordAgain(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $secret = $this->enrollTotp($userId);
        $this->passwordStep('root');

        for ($i = 1; $i <= 4; $i++) {
            $this->submitCode('000000');
            $this->assertResponseOk();
        }
        $this->post('/login/verify', ['code' => '000000']);

        $this->assertRedirect('/login');
        $this->assertArrayNotHasKey('TotpPending', $_SESSION ?? []);
        $this->assertSame(5, $this->countAuthEvents('totp_failed'));
        $this->assertSame(1, $this->countAuthEvents('totp_lockout'));

        // Even the right code no longer works without a fresh password step.
        $this->carrySession();
        $this->post('/login/verify', ['code' => $this->totpCode($secret)]);
        $this->assertRedirect('/login');
        $this->carrySession();
        $this->assertAnonymous();
    }

    public function testFailuresDoNotCarryOverToANewPasswordStep(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $secret = $this->enrollTotp($userId);
        $this->passwordStep('root');
        for ($i = 1; $i <= 4; $i++) {
            $this->submitCode('000000');
        }

        $this->passwordStep('root');
        $this->submitCode('000000');
        $this->assertResponseOk();

        $this->submitCode($this->totpCode($secret));
        $this->assertRedirect('/');
    }

    public function testVerifyWithoutAPendingStateGoesToLogin(): void
    {
        $this->get('/login/verify');
        $this->assertRedirect('/login');

        $this->post('/login/verify', ['code' => '123456']);
        $this->assertRedirect('/login');
    }

    public function testVerifyFormIsShownWhilePending(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $this->enrollTotp($userId);
        $this->passwordStep('root');

        $this->get('/login/verify');

        $this->assertResponseOk();
        $this->assertResponseContains('name="code"');
        $this->assertResponseContains('autocomplete="one-time-code"');
        $this->assertResponseContains('Verificación en dos pasos');
    }

    public function testUserDisabledWhilePendingCannotFinishSigningIn(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $secret = $this->enrollTotp($userId);
        $this->passwordStep('root');
        $this->db()->execute("UPDATE users SET status = 'disabled' WHERE id = ?", [$userId]);

        $this->submitCode($this->totpCode($secret));

        $this->assertRedirectContains('/login');
        $this->assertAnonymous();
    }

    public function testVerifyPostRequiresACsrfToken(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $secret = $this->enrollTotp($userId);
        $this->passwordStep('root');
        $this->_csrfToken = false;
        $this->_cookie = [];

        $this->post('/login/verify', ['code' => $this->totpCode($secret)]);

        $this->assertResponseCode(403);
    }

    public function testSecretIsStoredEncrypted(): void
    {
        $userId = $this->createUser('root', self::PASSWORD);
        $secret = $this->enrollTotp($userId);

        $raw = (string)$this->db()->execute(
            'SELECT secret_encrypted FROM user_totp WHERE user_id = ?',
            [$userId],
        )->fetch()[0];

        $this->assertNotSame($secret, $raw);
        $this->assertStringNotContainsString($secret, $raw);
        $this->assertSame($secret, $this->storedSecret($userId));
    }

    // --- backup codes ---------------------------------------------------------------------------------------

    public function testBackupCodeCompletesSignInAndIsAudited(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $this->enrollTotp($userId);
        $codes = $this->issueBackupCodes($userId);
        $this->passwordStep('root');

        $this->submitCode($codes[3]);

        $this->assertRedirect('/');
        $this->assertSignedIn();
        $this->assertSame(1, $this->countAuthEvents('backup_code_used'));
    }

    public function testBackupCodeIsAcceptedInLowerCaseAndWithoutTheHyphen(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $this->enrollTotp($userId);
        $codes = $this->issueBackupCodes($userId);
        $this->passwordStep('root');

        $this->submitCode(strtolower(str_replace('-', ' ', $codes[0])));

        $this->assertRedirect('/');
    }

    public function testBackupCodeWorksOnlyOnce(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $this->enrollTotp($userId);
        $codes = $this->issueBackupCodes($userId);
        $this->passwordStep('root');
        $this->submitCode($codes[0]);
        $this->assertRedirect('/');
        $this->logOut();

        $this->passwordStep('root');
        $this->post('/login/verify', ['code' => $codes[0]]);

        $this->assertResponseOk();
        $this->assertResponseContains('Código incorrecto');
        $this->carrySession();
        $this->assertAnonymous();
        $this->assertSame(1, $this->countAuthEvents('backup_code_used'));
    }

    public function testOtherBackupCodesRemainUsableAfterOneIsSpent(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $this->enrollTotp($userId);
        $codes = $this->issueBackupCodes($userId);
        $this->passwordStep('root');
        $this->submitCode($codes[0]);
        $this->logOut();

        $this->passwordStep('root');
        $this->submitCode($codes[1]);

        $this->assertRedirect('/');
    }

    public function testBackupCodesAreStoredHashedAndTenIssued(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $codes = $this->issueBackupCodes($userId);

        $this->assertCount(10, $codes);
        $this->assertCount(10, array_unique($codes));
        $rows = $this->db()->execute('SELECT code_hash FROM user_backup_codes WHERE user_id = ?', [$userId])
            ->fetchAll('assoc');
        $hashes = array_column($rows, 'code_hash');
        $this->assertCount(10, $hashes);
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^' . self::BACKUP_CODE_PATTERN . '$/', $code);
            $this->assertNotContains($code, $hashes);
            $this->assertNotContains(str_replace('-', '', $code), $hashes);
        }
    }

    public function testIssuingNewBackupCodesInvalidatesTheOldOnes(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $this->enrollTotp($userId);
        $old = $this->issueBackupCodes($userId);
        $this->issueBackupCodes($userId);
        $this->passwordStep('root');

        $this->post('/login/verify', ['code' => $old[0]]);

        $this->assertResponseContains('Código incorrecto');
    }

    // --- enrolment ------------------------------------------------------------------------------------------

    public function testEnrolmentPageShowsQrAndManualSecret(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $this->passwordStep('root');

        $this->get('/login/enroll');

        $this->assertResponseOk();
        $this->assertResponseContains('<svg');
        $this->assertResponseContains('name="code"');
        $this->assertResponseContains($this->storedSecret($userId));
        $this->assertResponseNotContains('otpauth://');
    }

    public function testEnrolmentSecretIsStoredEncryptedAndUnconfirmed(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $this->passwordStep('root');
        $this->get('/login/enroll');

        $row = $this->db()->execute(
            'SELECT secret_encrypted, confirmed_at, last_used_step FROM user_totp WHERE user_id = ?',
            [$userId],
        )->fetch('assoc');

        $this->assertNull($row['confirmed_at']);
        $this->assertNull($row['last_used_step']);
        $this->assertStringNotContainsString($this->storedSecret($userId), (string)$row['secret_encrypted']);
    }

    public function testReloadingTheEnrolmentPageKeepsTheSameSecret(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $this->passwordStep('root');
        $this->get('/login/enroll');
        $first = $this->storedSecret($userId);

        $this->get('/login/enroll');

        $this->assertSame($first, $this->storedSecret($userId));
    }

    public function testEnrolmentConfirmsWithAValidCodeSignsInAndShowsTenBackupCodesOnce(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $this->passwordStep('root');
        $this->get('/login/enroll');
        $secret = $this->storedSecret($userId);

        $this->post('/login/enroll', ['code' => $this->totpCode($secret)]);

        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertSame(10, preg_match_all('/\b' . self::BACKUP_CODE_PATTERN . '\b/', $body, $matches));
        $this->assertCount(10, array_unique($matches[0]));
        $this->assertSame($userId, (int)($_SESSION['Auth'] ?? 0));
        $this->assertNotNull(
            $this->db()->execute('SELECT confirmed_at FROM user_totp WHERE user_id = ?', [$userId])->fetch()[0],
        );
        $this->assertSame(1, $this->countAuthEvents('totp_enrolled'));
        $this->assertSame(10, (int)$this->db()->execute(
            'SELECT COUNT(*) FROM user_backup_codes WHERE user_id = ? AND used_at IS NULL',
            [$userId],
        )->fetch()[0]);

        // The codes are shown once: the page cannot be requested again.
        $this->carrySession();
        $this->get('/login/enroll');
        $this->assertRedirect('/login');
        $this->assertResponseNotContains($matches[0][0]);
    }

    public function testEnrolmentWithAWrongCodeDoesNotConfirmOrSignIn(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $this->passwordStep('root');
        $this->get('/login/enroll');

        $this->post('/login/enroll', ['code' => '000000']);

        $this->assertResponseOk();
        $this->assertResponseContains('Código incorrecto');
        $this->assertArrayNotHasKey('Auth', $_SESSION ?? []);
        $this->assertNull(
            $this->db()->execute('SELECT confirmed_at FROM user_totp WHERE user_id = ?', [$userId])->fetch()[0],
        );
        $this->assertSame(0, (int)$this->db()->execute('SELECT COUNT(*) FROM user_backup_codes')->fetch()[0]);
    }

    public function testEnrolmentAlsoHonoursTheFiveFailureLimit(): void
    {
        $this->createSuperAdministrator('root');
        $this->passwordStep('root');
        $this->get('/login/enroll');

        for ($i = 1; $i <= 5; $i++) {
            $this->post('/login/enroll', ['code' => '000000']);
            $this->carrySession();
        }

        $this->assertRedirect('/login');
        $this->assertArrayNotHasKey('TotpPending', $_SESSION ?? []);
        $this->assertSame(1, $this->countAuthEvents('totp_lockout'));
    }

    public function testEnrolmentIsRefusedWhenATotpDeviceIsAlreadyConfirmed(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $secret = $this->enrollTotp($userId);
        $this->passwordStep('root');

        $this->get('/login/enroll');
        $this->assertRedirect('/login/verify');

        $this->post('/login/enroll', ['code' => $this->totpCode($secret)]);
        $this->assertRedirect('/login/verify');
        $this->assertSame($secret, $this->storedSecret($userId), 'the device must not be replaceable by a password');
        $this->assertArrayNotHasKey('Auth', $_SESSION ?? []);
    }

    public function testEnrolmentRequiresAPendingState(): void
    {
        $this->get('/login/enroll');
        $this->assertRedirect('/login');

        $this->post('/login/enroll', ['code' => '123456']);
        $this->assertRedirect('/login');
    }

    public function testVerifyRedirectsToEnrolmentWhenNoDeviceIsConfirmedYet(): void
    {
        $this->createSuperAdministrator('root');
        $this->passwordStep('root');

        $this->get('/login/verify');

        $this->assertRedirect('/login/enroll');
    }

    public function testEnrolmentIsNotOfferedToUsersWhoDoNotNeedASecondFactor(): void
    {
        $userId = $this->createUser('dave', self::PASSWORD);
        $this->assignRole($userId, 'administrator', $this->createDevelopment());
        $this->passwordStep('dave');

        $this->get('/login/enroll');

        $this->assertRedirect('/login');
        $this->assertSame(0, (int)$this->db()->execute('SELECT COUNT(*) FROM user_totp')->fetch()[0]);
    }

    // --- audit ----------------------------------------------------------------------------------------------

    public function testPasswordStepsAreAudited(): void
    {
        $userId = $this->createUser('dave', self::PASSWORD);
        $this->configRequest([
            'environment' => ['REMOTE_ADDR' => '203.0.113.7', 'HTTP_USER_AGENT' => 'PHPUnit browser'],
        ]);

        $this->post('/login', ['username' => 'dave', 'password' => 'wrong']);
        $this->post('/login', ['username' => 'nobody', 'password' => 'wrong']);
        $this->post('/login', ['username' => 'dave', 'password' => self::PASSWORD]);

        $this->assertSame(2, $this->countAuthEvents('password_failed'));
        $this->assertSame(1, $this->countAuthEvents('password_ok'));
        $row = $this->db()->execute(
            "SELECT user_id, username, ip, user_agent FROM auth_events WHERE event_type = 'password_ok'",
        )->fetch('assoc');
        $this->assertSame($userId, (int)$row['user_id']);
        $this->assertSame('dave', $row['username']);
        $this->assertSame('203.0.113.7', $row['ip']);
        $this->assertSame('PHPUnit browser', $row['user_agent']);
    }

    public function testSignOutIsAudited(): void
    {
        $userId = $this->signInAs('dave');

        $this->post('/logout');

        $this->assertSame(1, $this->countAuthEvents('signed_out'));
        $this->assertSame(
            $userId,
            (int)$this->db()->execute("SELECT user_id FROM auth_events WHERE event_type = 'signed_out'")->fetch()[0],
        );
    }

    public function testAuditRowsNeverContainCodesOrPasswords(): void
    {
        $userId = $this->createSuperAdministrator('root');
        $secret = $this->enrollTotp($userId);
        $this->passwordStep('root');
        $code = $this->totpCode($secret);
        $this->submitCode($code);

        $dump = (string)json_encode($this->db()->execute('SELECT * FROM auth_events')->fetchAll('assoc'));

        $this->assertStringNotContainsString(self::PASSWORD, $dump);
        $this->assertStringNotContainsString($secret, $dump);
    }
}
