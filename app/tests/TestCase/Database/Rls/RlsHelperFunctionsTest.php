<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database\Rls;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The helpers every policy is written with: dev_ids(), admin_dev_ids(), is_super() and person_id() (ADR-0005 s.1).
 *
 * They are computed from role_assignments and users on each call, so what a session may do never comes from a value
 * the application sets other than the user id and the context.
 */
class RlsHelperFunctionsTest extends RlsTestCase
{
    /**
     * @return array<string, array{string, list<string>, list<string>, bool}>
     */
    public static function sessions(): array
    {
        return [
            'super administrator' => ['superUser', [], [], true],
            'administrator of A' => ['adminA', ['A'], ['A'], false],
            'administrator of B' => ['adminB', ['B'], ['B'], false],
            'accountant of A' => ['accountantA', ['A'], [], false],
            'guard of A' => ['guardA', ['A'], [], false],
            'resident of A' => ['resident', ['A'], [], false],
            'disabled administrator' => ['disabledAdmin', [], [], false],
            'ended assignment' => ['endedAdmin', [], [], false],
            'future assignment' => ['futureAdmin', [], [], false],
            'user without assignments' => ['loner', [], [], false],
        ];
    }

    /**
     * @param string $user Property holding the user id.
     * @param list<string> $dev Developments in dev_ids().
     * @param list<string> $admin Developments in admin_dev_ids().
     * @param bool $super Expected is_super().
     * @return void
     */
    #[DataProvider('sessions')]
    public function testHelpersFollowTheAssignmentsOfTheSessionUser(
        string $user,
        array $dev,
        array $admin,
        bool $super,
    ): void {
        $ids = ['A' => $this->devA, 'B' => $this->devB];
        $this->actAs($this->{$user});

        $this->assertSame(
            array_map(static fn(string $k): int => $ids[$k], $dev),
            $this->unnest('dev_ids()'),
        );
        $this->assertSame(
            array_map(static fn(string $k): int => $ids[$k], $admin),
            $this->unnest('admin_dev_ids()'),
        );
        $this->assertSame($super, (bool)$this->appDb()->execute('SELECT is_super()')->fetch()[0]);
    }

    public function testNoSessionMeansNoAccess(): void
    {
        $this->actAs(null);

        $this->assertSame([], $this->unnest('dev_ids()'));
        $this->assertSame([], $this->unnest('admin_dev_ids()'));
        $this->assertFalse((bool)$this->appDb()->execute('SELECT is_super()')->fetch()[0]);
        $this->assertNull($this->appDb()->execute('SELECT person_id()')->fetch()[0]);
    }

    public function testMissingSettingsAreNotAnError(): void
    {
        // Outside any session setup: the settings were never created in this connection.
        $this->appDb()->begin();
        $this->assertSame([], $this->unnest('dev_ids()'));
        $this->assertFalse((bool)$this->appDb()->execute('SELECT is_super()')->fetch()[0]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function malformedUserIds(): array
    {
        return [
            'text' => ['abc'],
            'injection' => ['1 OR 1=1'],
            'negative' => ['-1'],
            'huge' => ['99999999999999999999999'],
            'list' => ['1,2'],
        ];
    }

    /**
     * @param string $value Value of app.user_id.
     * @return void
     */
    #[DataProvider('malformedUserIds')]
    public function testMalformedUserIdMeansNoAccess(string $value): void
    {
        $this->appDb()->begin();
        $this->appDb()->execute("SELECT set_config('app.context', 'request', true), set_config('app.user_id', ?, true)", [$value]);

        $this->assertSame([], $this->unnest('dev_ids()'));
        $this->assertFalse((bool)$this->appDb()->execute('SELECT is_super()')->fetch()[0]);
    }

    public function testPersonIdIsTheLinkedPersonOfTheSessionUser(): void
    {
        $this->actAs($this->resident);

        $this->assertSame($this->personResident, (int)$this->appDb()->execute('SELECT person_id()')->fetch()[0]);
    }

    public function testRequestContextIsRequiredForUserHelpers(): void
    {
        // A user id without the request context (a webhook, or a forgotten context) grants nothing.
        $this->appDb()->begin();
        $this->appDb()->execute(
            "SELECT set_config('app.context', 'webhook', true), set_config('app.user_id', ?, true)",
            [(string)$this->superUser],
        );

        $this->assertFalse((bool)$this->appDb()->execute('SELECT is_super()')->fetch()[0]);
        $this->assertSame([], $this->unnest('dev_ids()'));
    }

    public function testJobContextUsesTheJobDevelopmentsAndNeverGrantsAdminOrSuper(): void
    {
        $this->actAsJob([$this->devB]);

        $this->assertSame([$this->devB], $this->unnest('dev_ids()'));
        $this->assertSame([], $this->unnest('admin_dev_ids()'));
        $this->assertFalse((bool)$this->appDb()->execute('SELECT is_super()')->fetch()[0]);
    }

    public function testJobContextWithSeveralDevelopments(): void
    {
        $this->actAsJob([$this->devA, $this->devB]);

        $this->assertSame([$this->devA, $this->devB], $this->unnest('dev_ids()'));
    }

    public function testJobContextWithoutDevelopmentsSeesNothing(): void
    {
        $this->actAsJob([]);

        $this->assertSame([], $this->unnest('dev_ids()'));
    }

    public function testJobContextIgnoresMalformedDevelopmentLists(): void
    {
        $this->appDb()->begin();
        $this->appDb()->execute(
            "SELECT set_config('app.context', 'job', true), set_config('app.job_dev_ids', ?, true)",
            [$this->devA . ',x;DROP,' . $this->devB],
        );

        // Only well-formed numbers count; the rest is dropped.
        $this->assertSame([$this->devA, $this->devB], $this->unnest('dev_ids()'));
    }

    public function testUserIdIsIgnoredInJobContext(): void
    {
        $this->appDb()->begin();
        $this->appDb()->execute(
            "SELECT set_config('app.context', 'job', true), set_config('app.user_id', ?, true)",
            [(string)$this->superUser],
        );

        $this->assertFalse((bool)$this->appDb()->execute('SELECT is_super()')->fetch()[0]);
    }

    public function testRevokedRoleTakesEffectOnTheNextTransaction(): void
    {
        $this->actAs($this->adminA);
        $this->assertSame([$this->devA], $this->unnest('admin_dev_ids()'));
        $this->endSession();

        $this->db()->execute(
            'UPDATE role_assignments SET ends_on = ? WHERE user_id = ?',
            [$this->businessDay(-1), $this->adminA],
        );

        $this->actAs($this->adminA);
        $this->assertSame([], $this->unnest('admin_dev_ids()'));
    }

    public function testAnApplicationValueCannotClaimSuperAdministrator(): void
    {
        $this->actAs($this->resident);
        foreach (['app.is_super', 'app.role', 'app.dev_ids', 'app.admin_dev_ids', 'app.super'] as $name) {
            $this->appDb()->execute('SELECT set_config(?, ?, true)', [$name, 'true']);
        }

        $this->assertFalse((bool)$this->appDb()->execute('SELECT is_super()')->fetch()[0]);
        $this->assertSame([$this->devA], $this->unnest('dev_ids()'));
        $this->assertSame([], $this->unnest('admin_dev_ids()'));
    }

    /**
     * @param string $function Function call returning bigint[].
     * @return list<int>
     */
    private function unnest(string $function): array
    {
        return $this->ids("SELECT unnest({$function})");
    }
}
