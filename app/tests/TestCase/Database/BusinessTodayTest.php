<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database;

use App\Clock\BusinessClock;
use App\Test\Support\DatabaseTestCase;
use Cake\Chronos\Chronos;
use Cake\Core\Configure;

/**
 * The SQL `business_today()` must agree with the PHP BusinessClock: both sides decide what "today" means.
 *
 * Changing `App.businessTimezone` (BUSINESS_TIMEZONE) requires a migration that redefines `business_today()`;
 * these tests fail until it does.
 */
class BusinessTodayTest extends DatabaseTestCase
{
    public function testSqlBusinessTodayEqualsTheClockDate(): void
    {
        $previous = Chronos::getTestNow();
        Chronos::setTestNow(null);
        try {
            $php = (new BusinessClock())->today()->format('Y-m-d');
            $sql = (string)$this->db()->execute('SELECT business_today()')->fetch()[0];
        } finally {
            Chronos::setTestNow($previous);
        }

        $this->assertSame($php, $sql);
    }

    public function testSqlFunctionIsDefinedForTheConfiguredZone(): void
    {
        $definition = (string)$this->db()->execute(
            "SELECT pg_get_functiondef('business_today()'::regprocedure)",
        )->fetch()[0];

        $this->assertStringContainsString(
            "'" . Configure::read('App.businessTimezone') . "'",
            $definition,
            'BUSINESS_TIMEZONE changed: add a migration that redefines business_today() for the new zone.',
        );
    }

    public function testSqlBusinessTodayIsStable(): void
    {
        $volatility = $this->db()->execute(
            "SELECT provolatile FROM pg_proc WHERE proname = 'business_today'",
        )->fetch()[0];

        $this->assertSame('s', $volatility);
    }

    public function testSqlBusinessTodayDoesNotDependOnTheSessionTimeZone(): void
    {
        $this->db()->execute("SET TIME ZONE 'Pacific/Auckland'");
        try {
            $sql = (string)$this->db()->execute('SELECT business_today()')->fetch()[0];
        } finally {
            $this->db()->execute('RESET TIME ZONE');
        }
        $previous = Chronos::getTestNow();
        Chronos::setTestNow(null);
        try {
            $php = (new BusinessClock())->today()->format('Y-m-d');
        } finally {
            Chronos::setTestNow($previous);
        }

        $this->assertSame($php, $sql);
    }
}
