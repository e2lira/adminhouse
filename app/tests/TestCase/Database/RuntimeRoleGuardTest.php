<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database;

use App\Database\DevelopmentContext;
use App\Database\RuntimeRoleGuard;
use App\Middleware\RequestTransactionMiddleware;
use App\Test\Support\DatabaseTestCase;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Row-level security protects nothing if the application connects as the table owner or as a role that bypasses
 * it, so the runtime connection is checked before a request or a job starts (RLS-SEC-001).
 */
class RuntimeRoleGuardTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RuntimeRoleGuard::reset();
    }

    protected function tearDown(): void
    {
        RuntimeRoleGuard::reset();
        parent::tearDown();
    }

    public function testTheRuntimeRoleIsAccepted(): void
    {
        RuntimeRoleGuard::assertSafe($this->appDb());

        $this->addToAssertionCount(1);
    }

    public function testTheOwnerLoginIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('kualitet_owner');

        RuntimeRoleGuard::assertSafe($this->db());
    }

    public function testAJobCannotStartOnTheOwnerLogin(): void
    {
        $context = new DevelopmentContext($this->db());

        try {
            $context->beginJob([]);
            $this->fail('A job must not run as the owner.');
        } catch (RuntimeException) {
            $this->assertFalse($this->db()->inTransaction());
        }
    }

    public function testARequestCannotStartWhenDefaultIsTheOwnerLogin(): void
    {
        ConnectionManager::dropAlias('default');
        ConnectionManager::alias('test_migrations', 'default');
        try {
            $handler = new class implements RequestHandlerInterface {
                public bool $ran = false;

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    $this->ran = true;

                    return new Response();
                }
            };

            try {
                (new RequestTransactionMiddleware())->process(new ServerRequest(), $handler);
                $this->fail('The request must be refused.');
            } catch (RuntimeException) {
                $this->assertFalse($handler->ran);
            }
        } finally {
            ConnectionManager::dropAlias('default');
            ConnectionManager::alias('test', 'default');
        }
    }

    public function testASuccessfulCheckIsRememberedForTheProcess(): void
    {
        RuntimeRoleGuard::assertSafe($this->appDb());

        $this->assertTrue(RuntimeRoleGuard::isVerified($this->appDb()));
    }
}
