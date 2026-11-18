<?php
declare(strict_types=1);

namespace App\Clock;

use Cake\Chronos\Chronos;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;

/**
 * The one definition of "today" for business rules (role-assignment windows, second-factor requirement).
 *
 * All developments run on one business time zone (`App.businessTimezone`, default America/Mexico_City), while
 * timestamps stay stored in UTC (`App.defaultTimezone`). A day therefore ends at local midnight, not at 18:00.
 *
 * The database has the same notion in `business_today()` (used by the super-administrator limit trigger). The two
 * must name the same zone: changing BUSINESS_TIMEZONE requires a migration that redefines `business_today()`.
 *
 * Without a fixed instant the clock follows `Chronos::setTestNow()`, so tests can freeze it.
 */
final class BusinessClock
{
    private DateTimeZone $zone;

    /**
     * @param string|null $timezone IANA zone name; defaults to `App.businessTimezone`.
     * @param \DateTimeInterface|null $fixedNow Instant to report instead of the current one.
     * @throws \InvalidArgumentException When the time zone is unknown.
     */
    public function __construct(?string $timezone = null, private ?DateTimeInterface $fixedNow = null)
    {
        $name = $timezone ?? (string)Configure::read('App.businessTimezone', 'America/Mexico_City');
        try {
            $this->zone = new DateTimeZone($name);
        } catch (Exception) {
            throw new InvalidArgumentException(sprintf('Unknown business time zone `%s`.', $name));
        }
    }

    /**
     * @return string IANA name of the business time zone.
     */
    public function timezone(): string
    {
        return $this->zone->getName();
    }

    /**
     * The current instant, expressed in the business time zone.
     *
     * @return \Cake\I18n\DateTime
     */
    public function now(): DateTime
    {
        $instant = $this->fixedNow ?? Chronos::now();

        return DateTime::createFromTimestamp($instant->getTimestamp(), $this->zone);
    }

    /**
     * The calendar date of the current instant in the business time zone.
     *
     * @return \Cake\I18n\Date
     */
    public function today(): Date
    {
        return new Date($this->now()->format('Y-m-d'));
    }
}
