<?php
declare(strict_types=1);

namespace App\Test\TestCase\Clock;

use App\Clock\BusinessClock;
use Cake\Chronos\Chronos;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * "Today" is one business day for the whole app: the calendar day in `App.businessTimezone`, not in UTC.
 */
class BusinessClockTest extends TestCase
{
    private function at(string $utcInstant): BusinessClock
    {
        return new BusinessClock(null, new Chronos($utcInstant, 'UTC'));
    }

    public function testEveningInMexicoIsStillTheSameBusinessDay(): void
    {
        // 2026-11-18 02:00 UTC is 2026-11-17 20:00 in Mexico City (UTC-6, no daylight saving time).
        $this->assertSame('2026-11-17', $this->at('2026-11-18T02:00:00Z')->today()->format('Y-m-d'));
    }

    public function testBusinessDayRollsOverAtLocalMidnight(): void
    {
        $this->assertSame('2026-11-17', $this->at('2026-11-18T05:59:59Z')->today()->format('Y-m-d'));
        $this->assertSame('2026-11-18', $this->at('2026-11-18T06:00:00Z')->today()->format('Y-m-d'));
        $this->assertSame('2026-11-18', $this->at('2026-11-18T06:30:00Z')->today()->format('Y-m-d'));
    }

    public function testTodayIsADateWithoutTimeOfDay(): void
    {
        $this->assertInstanceOf(Date::class, $this->at('2026-11-18T02:00:00Z')->today());
    }

    public function testNowIsExpressedInTheBusinessZone(): void
    {
        $now = $this->at('2026-11-18T02:00:00Z')->now();

        $this->assertSame('2026-11-17 20:00:00', $now->format('Y-m-d H:i:s'));
        $this->assertSame('America/Mexico_City', $now->getTimezone()->getName());
    }

    public function testDefaultsToTheConfiguredBusinessTimezone(): void
    {
        $this->assertSame('America/Mexico_City', Configure::read('App.businessTimezone'));
        $this->assertSame('America/Mexico_City', (new BusinessClock())->timezone());
    }

    public function testExplicitTimezoneOverridesTheConfiguration(): void
    {
        $clock = new BusinessClock('Pacific/Auckland', new Chronos('2026-11-18T02:00:00Z', 'UTC'));

        $this->assertSame('2026-11-18', $clock->today()->format('Y-m-d'));
    }

    public function testFollowsTheTestNowWhenNoInstantIsFixed(): void
    {
        $previous = Chronos::getTestNow();
        Chronos::setTestNow(new Chronos('2026-11-18T02:00:00Z', 'UTC'));
        try {
            $this->assertSame('2026-11-17', (new BusinessClock())->today()->format('Y-m-d'));
        } finally {
            Chronos::setTestNow($previous);
        }
    }

    public function testRejectsAnUnknownTimezone(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BusinessClock('Mars/Olympus_Mons');
    }

    public function testStorageTimezoneStaysUtc(): void
    {
        $this->assertSame('UTC', Configure::read('App.defaultTimezone'));
        $this->assertSame('UTC', date_default_timezone_get());
    }
}
