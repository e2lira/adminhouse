<?php
declare(strict_types=1);

namespace App\Test\TestCase\Authentication\SecondFactor;

use App\Authentication\SecondFactor\SecondFactorService;
use App\Clock\BusinessClock;
use App\Test\Support\DatabaseTestCase;
use Cake\Chronos\Chronos;

/**
 * Whether a super-administrator assignment is "in force today" follows the business day (RB-R5).
 */
class SecondFactorRequirementTest extends DatabaseTestCase
{
    private function serviceAt(string $utcInstant): SecondFactorService
    {
        return new SecondFactorService(clock: new BusinessClock(null, new Chronos($utcInstant, 'UTC')));
    }

    public function testAssignmentEndingTodayStillRequiresTheSecondFactorInTheEveningInMexico(): void
    {
        $userId = $this->createUser('root');
        $this->assignRoleOn($userId, 'super_administrator', null, '2026-11-01', '2026-11-17');

        $this->assertTrue($this->serviceAt('2026-11-18T02:00:00Z')->isRequired($userId));
        $this->assertFalse($this->serviceAt('2026-11-18T06:30:00Z')->isRequired($userId));
    }

    public function testAssignmentStartingOnTheBusinessDayDoesNotRequireItEarlier(): void
    {
        $userId = $this->createUser('root');
        $this->assignRoleOn($userId, 'super_administrator', null, '2026-11-18');

        $this->assertFalse($this->serviceAt('2026-11-18T02:00:00Z')->isRequired($userId));
        $this->assertTrue($this->serviceAt('2026-11-18T06:30:00Z')->isRequired($userId));
    }
}
