<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Database\DevelopmentContext;
use App\Database\RuntimeRoleGuard;
use Cake\Datasource\ConnectionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Runs the whole request inside one database transaction with `app.context = 'request'` (RLS-SEC-001).
 *
 * It sits before authentication: sign-in itself reads the database (through definer functions) and writes the audit
 * trail, so the transaction and the context must already exist. The user id is set later by SessionUserMiddleware,
 * once the request is identified. A request that ends in an exception is rolled back, including its audit rows;
 * a request that answers normally (even with an error status) commits.
 */
class RequestTransactionMiddleware implements MiddlewareInterface
{
    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Row-level security only protects a role it applies to: refuse to serve as the owner or a bypassing role.
        RuntimeRoleGuard::assertSafe(ConnectionManager::get('default'));

        $context = new DevelopmentContext();
        $context->beginRequest();

        try {
            $response = $handler->handle($request);
        } catch (Throwable $e) {
            $context->rollback();

            throw $e;
        }

        $context->commit();

        return $response;
    }
}
