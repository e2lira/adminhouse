<?php
declare(strict_types=1);

namespace App\Authentication;

use App\Authentication\SecondFactor\PendingLogin;
use App\Authentication\SecondFactor\SecondFactorService;
use Authentication\Authenticator\FormAuthenticator;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Authentication\Identifier\IdentifierInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Username and password form that stops short of an identity for users who must pass a second factor.
 *
 * After a correct password for such a user, the result is a failure with no identity data, so the authentication
 * middleware neither exposes nor persists an identity. The only trace is the pending marker (see PendingLogin),
 * which the sign-in controller turns into a redirect to the second step.
 *
 * The password step is audited here, for every outcome.
 */
class SecondFactorFormAuthenticator extends FormAuthenticator
{
    private ?AuthEventLogger $audit;

    private ?SecondFactorService $secondFactor;

    /**
     * @param \Authentication\Identifier\IdentifierInterface $identifiers Identifier or identifiers collection.
     * @param array<string, mixed> $config Configuration settings.
     * @param \App\Authentication\AuthEventLogger|null $audit Audit writer, for tests.
     * @param \App\Authentication\SecondFactor\SecondFactorService|null $secondFactor Second-factor rules, for tests.
     */
    public function __construct(
        IdentifierInterface $identifiers,
        array $config = [],
        ?AuthEventLogger $audit = null,
        ?SecondFactorService $secondFactor = null,
    ) {
        parent::__construct($identifiers, $config);
        $this->audit = $audit;
        $this->secondFactor = $secondFactor;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface $request): ResultInterface
    {
        $result = parent::authenticate($request);

        if ($result->getStatus() === ResultInterface::FAILURE_IDENTITY_NOT_FOUND) {
            // Credentials were submitted to the login URL and rejected.
            $this->audit()->log(
                AuthEventLogger::PASSWORD_FAILED,
                null,
                $this->submittedUsername($request),
                $request,
            );

            return $result;
        }
        if (!$result->isValid()) {
            return $result;
        }

        $user = $result->getData();
        $userId = (int)$user['id'];
        $this->audit()->log(AuthEventLogger::PASSWORD_OK, $userId, (string)$user['username'], $request);

        if (!$this->secondFactor()->isRequired($userId)) {
            return $result;
        }

        /** @var \Cake\Http\Session $session */
        $session = $request->getAttribute('session');
        PendingLogin::start($session, $userId);

        return new Result(null, ResultInterface::FAILURE_OTHER, ['A second factor is required.']);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request Request.
     * @return string|null
     */
    private function submittedUsername(ServerRequestInterface $request): ?string
    {
        $body = $request->getParsedBody();
        $username = is_array($body) ? ($body[$this->getConfig('fields.username')] ?? null) : null;

        return is_string($username) ? $username : null;
    }

    /**
     * @return \App\Authentication\AuthEventLogger
     */
    private function audit(): AuthEventLogger
    {
        return $this->audit ??= new AuthEventLogger();
    }

    /**
     * @return \App\Authentication\SecondFactor\SecondFactorService
     */
    private function secondFactor(): SecondFactorService
    {
        return $this->secondFactor ??= new SecondFactorService();
    }
}
