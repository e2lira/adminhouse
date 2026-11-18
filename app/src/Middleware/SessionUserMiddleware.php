<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Database\DevelopmentContext;
use Authentication\IdentityInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Tells PostgreSQL which user the request belongs to, once authentication has identified them (RLS-SEC-001).
 *
 * It sets `app.user_id` and nothing else: the database derives roles, developments and super-administrator status
 * itself, and ignores the id of a user that is not active. An anonymous request keeps an empty user id, which the
 * policies treat as "no access".
 */
class SessionUserMiddleware implements MiddlewareInterface
{
    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identity = $request->getAttribute('identity');
        if ($identity instanceof IdentityInterface) {
            $id = $identity->getIdentifier();
            if (is_int($id) || (is_string($id) && ctype_digit($id))) {
                (new DevelopmentContext())->setUser((int)$id);
            }
        }

        return $handler->handle($request);
    }
}
