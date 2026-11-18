<?php
declare(strict_types=1);

namespace App\Test\Support;

use App\Database\DevelopmentContext;
use Cake\Datasource\ConnectionManager;
use Throwable;

/**
 * Runs a service call the way a request would: in one transaction on the runtime connection, with the acting user in
 * the session values, so row-level security applies to it (RLS-SEC-001).
 *
 * The services under test take the acting user as a parameter; in production the middleware already holds that user
 * in the database session. Test doubles use this trait to open the same session around each call, and commit it when
 * the call ends (also when it throws, as a request that renders an error page does) so the owner connection can
 * check what was stored.
 */
trait ActsAsSessionUserTrait
{
    /**
     * @template T
     * @param int $userId Acting user.
     * @param callable(): T $work The call.
     * @return T
     */
    private function asSessionUser(int $userId, callable $work): mixed
    {
        $context = new DevelopmentContext(ConnectionManager::get('default'));
        $context->beginRequest($userId);
        try {
            $result = $work();
        } catch (Throwable $e) {
            $context->commit();

            throw $e;
        }
        $context->commit();

        return $result;
    }
}
