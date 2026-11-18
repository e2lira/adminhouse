<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database;

use App\Database\DevelopmentContext;
use App\Test\Support\DatabaseTestCase;
use LogicException;
use RuntimeException;
use Throwable;

/**
 * The only way the application tells PostgreSQL who is acting (RLS-SEC-001): a transaction plus transaction-local
 * settings, `app.user_id`, `app.context` and, for jobs, `app.job_dev_ids`. Nothing else.
 */
class DevelopmentContextTest extends DatabaseTestCase
{
    private function setting(string $name): ?string
    {
        $value = $this->appDb()->execute('SELECT current_setting(?, true)', [$name])->fetch()[0];

        return $value === null ? null : (string)$value;
    }

    public function testBeginRequestOpensATransactionWithTheRequestValues(): void
    {
        $context = new DevelopmentContext($this->appDb());

        $context->beginRequest(42);

        $this->assertTrue($this->appDb()->inTransaction());
        $this->assertSame('request', $this->setting('app.context'));
        $this->assertSame('42', $this->setting('app.user_id'));
        $this->assertContains($this->setting('app.job_dev_ids'), [null, '']);
    }

    public function testAnAnonymousRequestHasNoUser(): void
    {
        (new DevelopmentContext($this->appDb()))->beginRequest(null);

        $this->assertSame('request', $this->setting('app.context'));
        $this->assertContains($this->setting('app.user_id'), [null, '']);
    }

    public function testTheUserCanBeSetAfterAuthentication(): void
    {
        $context = new DevelopmentContext($this->appDb());
        $context->beginRequest(null);

        $context->setUser(7);
        $this->assertSame('7', $this->setting('app.user_id'));

        $context->setUser(null);
        $this->assertContains($this->setting('app.user_id'), [null, '']);
    }

    public function testBeginJobSetsTheContextAndTheDevelopments(): void
    {
        (new DevelopmentContext($this->appDb()))->beginJob([3, 5]);

        $this->assertSame('job', $this->setting('app.context'));
        $this->assertSame('3,5', $this->setting('app.job_dev_ids'));
        $this->assertContains($this->setting('app.user_id'), [null, '']);
    }

    public function testValuesDieWithTheTransaction(): void
    {
        $context = new DevelopmentContext($this->appDb());
        $context->beginRequest(42);
        $context->commit();

        $this->assertFalse($this->appDb()->inTransaction());
        $this->assertContains($this->setting('app.user_id'), [null, '']);
        $this->assertContains($this->setting('app.context'), [null, '']);

        $context->beginJob([1]);
        $context->rollback();
        $this->assertContains($this->setting('app.job_dev_ids'), [null, '']);
    }

    public function testASecondBeginIsRefused(): void
    {
        $context = new DevelopmentContext($this->appDb());
        $context->beginRequest(1);

        $this->expectException(LogicException::class);
        $context->beginRequest(2);
    }

    public function testSavepointsAreEnabledSoNestedUnitsOfWorkCanFail(): void
    {
        (new DevelopmentContext($this->appDb()))->beginRequest(1);

        $this->assertTrue($this->appDb()->isSavePointsEnabled());
    }

    public function testRunAsJobCommitsWhatTheJobDid(): void
    {
        $context = new DevelopmentContext($this->appDb());

        $result = $context->runAsJob([], function (): string {
            $this->appDb()->execute('SELECT auth_event_log(NULL, ?, ?, NULL, NULL)', ['job', 'password_ok']);

            return 'done';
        });

        $this->assertSame('done', $result);
        $this->assertFalse($this->appDb()->inTransaction());
        $this->assertSame(1, (int)$this->db()->execute("SELECT COUNT(*) FROM auth_events WHERE username = 'job'")->fetch()[0]);
    }

    public function testRunAsJobRollsBackWhenTheJobFails(): void
    {
        $context = new DevelopmentContext($this->appDb());

        try {
            $context->runAsJob([], function (): void {
                $this->appDb()->execute('SELECT auth_event_log(NULL, ?, ?, NULL, NULL)', ['job', 'password_ok']);

                throw new RuntimeException('boom');
            });
            $this->fail('The failure must propagate.');
        } catch (RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertFalse($this->appDb()->inTransaction());
        $this->assertSame(0, (int)$this->db()->execute("SELECT COUNT(*) FROM auth_events WHERE username = 'job'")->fetch()[0]);
    }

    public function testCommitFailsLoudlyWhenTheTransactionWasAborted(): void
    {
        $context = new DevelopmentContext($this->appDb());
        $context->beginRequest(null);
        $this->appDb()->execute('SELECT auth_event_log(NULL, ?, ?, NULL, NULL)', ['aborted', 'password_ok']);
        try {
            $this->appDb()->execute('SELECT 1 / 0');
            $this->fail('The statement must fail.');
        } catch (Throwable) {
            // The PostgreSQL transaction is now aborted; a plain COMMIT would answer "ROLLBACK" without an error.
        }

        try {
            $context->commit();
            $this->fail('Committing an aborted transaction must not look like success.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('aborted', $e->getMessage());
        }

        $this->assertFalse($this->appDb()->inTransaction(), 'The failed commit leaves no transaction open.');
        $this->assertSame(0, (int)$this->db()->execute("SELECT COUNT(*) FROM auth_events WHERE username = 'aborted'")->fetch()[0]);
    }

    public function testCommitStillWorksAfterARecoveredSavepointFailure(): void
    {
        $context = new DevelopmentContext($this->appDb());
        $context->beginRequest(null);
        try {
            $this->appDb()->transactional(function (): void {
                $this->appDb()->execute('SELECT 1 / 0');
            });
        } catch (Throwable) {
            // Rolled back to its own savepoint: the request transaction is healthy.
        }
        $this->appDb()->execute('SELECT auth_event_log(NULL, ?, ?, NULL, NULL)', ['healthy', 'password_ok']);

        $context->commit();

        $this->assertSame(1, (int)$this->db()->execute("SELECT COUNT(*) FROM auth_events WHERE username = 'healthy'")->fetch()[0]);
    }
}
