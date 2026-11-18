<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddBusinessToday extends BaseMigration
{
    public function up(): void
    {
        // REBUILD-003-F4 (user decision 2026-11-18): "today" is the calendar day in ONE business time zone,
        // America/Mexico_City, not the day in UTC or in the database session. This function is the SQL twin of
        // App\Clock\BusinessClock and must name the same zone as `App.businessTimezone` (BUSINESS_TIMEZONE):
        // changing that setting requires a migration that redefines this function (BusinessTodayTest checks it).
        // STABLE: constant within one statement; it does not depend on the session time zone.
        $this->execute(<<<'SQL'
            CREATE FUNCTION business_today() RETURNS date
            LANGUAGE sql STABLE AS $$
                SELECT (now() AT TIME ZONE 'America/Mexico_City')::date
            $$
        SQL);

        $this->execute($this->limitFunction('business_today()'));
    }

    public function down(): void
    {
        // Restore the version of 20261118000004 (CURRENT_DATE), then drop the function it no longer uses.
        $this->execute($this->limitFunction('CURRENT_DATE'));
        $this->execute('DROP FUNCTION business_today()');
    }

    /**
     * The super-administrator limit trigger function, counting non-ended assignments against $today.
     *
     * @param string $today SQL expression for today's date.
     * @return string
     */
    private function limitFunction(string $today): string
    {
        return <<<SQL
            CREATE OR REPLACE FUNCTION role_assignments_limit_super_administrators() RETURNS trigger
            LANGUAGE plpgsql AS \$\$
            DECLARE
                super_id bigint;
                holders integer;
            BEGIN
                SELECT id INTO super_id FROM roles WHERE code = 'super_administrator';
                IF NEW.role_id IS DISTINCT FROM super_id THEN
                    RETURN NEW;
                END IF;
                IF NEW.ends_on IS NOT NULL AND NEW.ends_on < {$today} THEN
                    RETURN NEW;
                END IF;

                PERFORM pg_advisory_xact_lock(hashtext('role_assignments.super_administrator'));

                SELECT COUNT(DISTINCT user_id) INTO holders
                FROM role_assignments
                WHERE role_id = super_id
                  AND user_id <> NEW.user_id
                  AND (ends_on IS NULL OR ends_on >= {$today});

                IF holders >= 2 THEN
                    RAISE EXCEPTION 'At most 2 accounts may hold the super_administrator role'
                        USING ERRCODE = 'check_violation';
                END IF;
                RETURN NEW;
            END;
            \$\$
            SQL;
    }
}
