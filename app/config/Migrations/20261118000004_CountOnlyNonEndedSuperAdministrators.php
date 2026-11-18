<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CountOnlyNonEndedSuperAdministrators extends BaseMigration
{
    public function up(): void
    {
        // REBUILD-003-F6 (user decision 2026-11-18): the two-account limit counts only super_administrator
        // assignments that have not ended, so an ended assignment frees its slot without deleting history.
        // Future-dated assignments still count, so a slot cannot be reserved ahead of time to bypass the limit.
        // Recording an assignment that has already ended is never limited; reopening one (UPDATE of ends_on)
        // is checked like a new grant because the trigger also fires on UPDATE.
        $this->execute(<<<'SQL'
            CREATE OR REPLACE FUNCTION role_assignments_limit_super_administrators() RETURNS trigger
            LANGUAGE plpgsql AS $$
            DECLARE
                super_id bigint;
                holders integer;
            BEGIN
                SELECT id INTO super_id FROM roles WHERE code = 'super_administrator';
                IF NEW.role_id IS DISTINCT FROM super_id THEN
                    RETURN NEW;
                END IF;
                IF NEW.ends_on IS NOT NULL AND NEW.ends_on < CURRENT_DATE THEN
                    RETURN NEW;
                END IF;

                PERFORM pg_advisory_xact_lock(hashtext('role_assignments.super_administrator'));

                SELECT COUNT(DISTINCT user_id) INTO holders
                FROM role_assignments
                WHERE role_id = super_id
                  AND user_id <> NEW.user_id
                  AND (ends_on IS NULL OR ends_on >= CURRENT_DATE);

                IF holders >= 2 THEN
                    RAISE EXCEPTION 'At most 2 accounts may hold the super_administrator role'
                        USING ERRCODE = 'check_violation';
                END IF;
                RETURN NEW;
            END;
            $$
        SQL);
    }

    public function down(): void
    {
        $this->execute(<<<'SQL'
            CREATE OR REPLACE FUNCTION role_assignments_limit_super_administrators() RETURNS trigger
            LANGUAGE plpgsql AS $$
            DECLARE
                super_id bigint;
                holders integer;
            BEGIN
                SELECT id INTO super_id FROM roles WHERE code = 'super_administrator';
                IF NEW.role_id IS DISTINCT FROM super_id THEN
                    RETURN NEW;
                END IF;

                PERFORM pg_advisory_xact_lock(hashtext('role_assignments.super_administrator'));

                SELECT COUNT(DISTINCT user_id) INTO holders
                FROM role_assignments
                WHERE role_id = super_id AND user_id <> NEW.user_id;

                IF holders >= 2 THEN
                    RAISE EXCEPTION 'At most 2 accounts may hold the super_administrator role'
                        USING ERRCODE = 'check_violation';
                END IF;
                RETURN NEW;
            END;
            $$
        SQL);
    }
}
