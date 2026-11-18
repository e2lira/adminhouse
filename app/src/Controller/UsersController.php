<?php
declare(strict_types=1);

namespace App\Controller;

use App\Authentication\AccountGateway;
use App\Authentication\AuthEventLogger;
use App\Authentication\SecondFactor\PendingLogin;
use App\Authentication\SecondFactor\SecondFactorService;
use Authentication\Authenticator\ResultInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Http\Session;

/**
 * Sign-in (password, then TOTP where required) and sign-out.
 *
 * Sign-in is two steps for a user who must use a second factor. After the password the user is not
 * authenticated: the session only carries a short-lived pending marker (see PendingLogin), and the identity is
 * persisted by completeSignIn() once a code has been accepted.
 */
class UsersController extends AppController
{
    private const GENERIC_CODE_ERROR = 'Código incorrecto.';
    private const LOCKOUT_ERROR = 'Demasiados intentos fallidos. Inicia sesión de nuevo.';

    private ?SecondFactorService $secondFactor = null;

    private ?AuthEventLogger $audit = null;

    /**
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event Event.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // `verify` and `enroll` are reachable without an identity on purpose: they act only on the pending
        // marker left by a correct password, and send everyone else back to the login form.
        $this->Authentication->allowUnauthenticated(['login', 'verify', 'enroll']);

        // Sign-in and sign-out act on the caller's own session and expose no development data, so there is
        // nothing to authorize against a permission.
        $this->Authorization->skipAuthorization();
    }

    /**
     * Show the sign-in form and handle its submission.
     *
     * @return \Cake\Http\Response|null
     */
    public function login(): ?Response
    {
        $result = $this->Authentication->getResult();

        if ($result !== null && $result->isValid()) {
            return $this->redirect($this->Authentication->getLoginRedirect() ?? '/');
        }

        if ($this->request->is('post')) {
            // FAILURE_OTHER is what SecondFactorFormAuthenticator returns after a correct password when a
            // second factor is still due; the marker it left is in the session.
            $pending = $result?->getStatus() === ResultInterface::FAILURE_OTHER
                ? PendingLogin::current($this->session())
                : null;
            if ($pending !== null) {
                PendingLogin::rememberRedirect($this->session(), $this->Authentication->getLoginRedirect());

                return $this->redirect([
                    'action' => $this->secondFactor()->hasConfirmedDevice($pending['user_id']) ? 'verify' : 'enroll',
                ]);
            }

            $this->Flash->error('Usuario o contraseña incorrectos.');
        }

        return null;
    }

    /**
     * Second step: a TOTP code or a backup code.
     *
     * @return \Cake\Http\Response|null
     */
    public function verify(): ?Response
    {
        $this->noStore();
        $pending = PendingLogin::current($this->session());
        if ($pending === null) {
            return $this->redirect(['action' => 'login']);
        }
        $userId = $pending['user_id'];

        if (!$this->secondFactor()->hasConfirmedDevice($userId)) {
            return $this->redirect(['action' => 'enroll']);
        }
        if (!$this->request->is('post')) {
            return null;
        }

        $user = $this->activeUser($userId);
        if ($user === null) {
            return $this->abandon();
        }

        $code = trim((string)$this->request->getData('code'));
        $isTotp = preg_match('/^\d{3}\s?\d{3}$/', $code) === 1;
        $accepted = $isTotp
            ? $this->secondFactor()->verifyTotp($userId, $code)
            : $this->secondFactor()->verifyBackupCode($userId, $code);

        if (!$accepted) {
            return $this->rejectCode($userId);
        }

        $this->audit()->log(
            $isTotp ? AuthEventLogger::TOTP_OK : AuthEventLogger::BACKUP_CODE_USED,
            $userId,
            (string)$user->username,
            $this->request,
        );

        return $this->completeSignIn($user, $pending['redirect']);
    }

    /**
     * First sign-in of a user who must use TOTP but has no device yet: show the QR code and confirm it with a
     * code, then show the backup codes once.
     *
     * Enrolment is refused once a device is confirmed, so a password alone can never replace an existing one.
     *
     * @return \Cake\Http\Response|null
     */
    public function enroll(): ?Response
    {
        $this->noStore();
        $pending = PendingLogin::current($this->session());
        if ($pending === null) {
            return $this->redirect(['action' => 'login']);
        }
        $userId = $pending['user_id'];

        if ($this->secondFactor()->hasConfirmedDevice($userId)) {
            return $this->redirect(['action' => 'verify']);
        }
        if (!$this->secondFactor()->isRequired($userId)) {
            return $this->abandon();
        }

        $user = $this->activeUser($userId);
        if ($user === null) {
            return $this->abandon();
        }

        if ($this->request->is('post')) {
            $codes = $this->secondFactor()->confirmEnrolment($userId, trim((string)$this->request->getData('code')));
            if ($codes !== null) {
                $this->audit()->log(AuthEventLogger::TOTP_ENROLLED, $userId, (string)$user->username, $this->request);
                $continueUrl = $pending['redirect'] ?? '/';
                PendingLogin::finish($this->session());
                $this->Authentication->setIdentity($user);
                $this->set(compact('codes', 'continueUrl'));

                return $this->render('backup_codes');
            }

            $lockedOut = $this->rejectCode($userId);
            if ($lockedOut !== null) {
                return $lockedOut;
            }
        }

        $material = $this->secondFactor()->beginEnrolment($userId, (string)$user->username);
        $this->set(['qrSvg' => $material['svg'], 'secret' => $material['secret']]);

        return null;
    }

    /**
     * End the session. Only POST is accepted so a link or image cannot sign the user out.
     *
     * @return \Cake\Http\Response
     */
    public function logout(): Response
    {
        $this->request->allowMethod(['post']);

        $identity = $this->Authentication->getIdentity();
        $this->audit()->log(
            AuthEventLogger::SIGNED_OUT,
            $identity === null ? null : (int)$identity->getIdentifier(),
            $identity === null ? null : (string)$identity->get('username'),
            $this->request,
        );
        $this->Authentication->logout();

        return $this->redirect(['action' => 'login']);
    }

    /**
     * Count a wrong code. After the fifth the pending state is discarded and the password is required again.
     *
     * @param int $userId Pending user.
     * @return \Cake\Http\Response|null A redirect after a lockout, otherwise null (the form is shown again).
     */
    private function rejectCode(int $userId): ?Response
    {
        $this->audit()->log(AuthEventLogger::TOTP_FAILED, $userId, null, $this->request);

        if (PendingLogin::recordFailure($this->session()) >= PendingLogin::MAX_FAILURES) {
            $this->audit()->log(AuthEventLogger::TOTP_LOCKOUT, $userId, null, $this->request);
            PendingLogin::abandon($this->session());
            $this->Flash->error(self::LOCKOUT_ERROR);

            return $this->redirect(['action' => 'login']);
        }

        $this->Flash->error(self::GENERIC_CODE_ERROR);

        return null;
    }

    /**
     * Persist the identity (which renews the session id) and leave the pending state.
     *
     * @param \Cake\Datasource\EntityInterface $user Active user.
     * @param string|null $redirect Internal URL the user originally asked for.
     * @return \Cake\Http\Response
     */
    private function completeSignIn(EntityInterface $user, ?string $redirect): Response
    {
        PendingLogin::finish($this->session());
        $this->Authentication->setIdentity($user);

        return $this->redirect($redirect ?? '/');
    }

    /**
     * Drop the pending state and go back to the password form.
     *
     * @return \Cake\Http\Response
     */
    private function abandon(): Response
    {
        PendingLogin::abandon($this->session());

        return $this->redirect(['action' => 'login']);
    }

    /**
     * @param int $userId User id.
     * @return \Cake\Datasource\EntityInterface|null The user, only while the account is active.
     */
    private function activeUser(int $userId): ?EntityInterface
    {
        // Read through a definer function: the second step runs before an identity exists (RLS-SEC-001).
        return (new AccountGateway())->findActive($userId);
    }

    /**
     * Pages that show secrets or codes must not be kept by the browser or a proxy.
     *
     * @return void
     */
    private function noStore(): void
    {
        $this->setResponse($this->response->withHeader('Cache-Control', 'no-store'));
    }

    /**
     * @return \Cake\Http\Session
     */
    private function session(): Session
    {
        return $this->request->getSession();
    }

    /**
     * @return \App\Authentication\SecondFactor\SecondFactorService
     */
    private function secondFactor(): SecondFactorService
    {
        return $this->secondFactor ??= new SecondFactorService();
    }

    /**
     * @return \App\Authentication\AuthEventLogger
     */
    private function audit(): AuthEventLogger
    {
        return $this->audit ??= new AuthEventLogger();
    }
}
