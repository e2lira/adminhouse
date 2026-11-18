<?php
declare(strict_types=1);

namespace App\Authentication;

use Authentication\Authenticator\PrimaryKeySessionAuthenticator;
use Authentication\Authenticator\ResultInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Session authenticator that keeps only the user id in the session and re-checks the user on each request.
 *
 * When the stored user no longer exists or is not active, the session is cleared and rotated, so the request
 * is treated as anonymous and the stale session cannot be reused.
 */
class ActiveSessionAuthenticator extends PrimaryKeySessionAuthenticator
{
    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface $request): ResultInterface
    {
        $result = parent::authenticate($request);

        if (!$result->isValid()) {
            /** @var \Cake\Http\Session $session */
            $session = $request->getAttribute('session');
            $sessionKey = $this->getConfig('sessionKey');
            if ($session->check($sessionKey)) {
                $session->delete($sessionKey);
                $session->renew();
            }
        }

        return $result;
    }
}
