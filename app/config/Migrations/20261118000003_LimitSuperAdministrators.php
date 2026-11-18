<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class LimitSuperAdministrators extends BaseMigration
{
    public function up(): void
    {
        // RB-R5 / AC-RB-9: at most two accounts hold the super_administrator role. A CHECK constraint cannot
        // count rows, so a trigger does. Every assignment of the role counts, even an ended one, so an expired
        // row cannot be used to park a slot: free it by deleting the assignment.
        //
        // The advisory lock serialises concurrent grants; without it two transactions could each count one
        // existing holder and both insert.
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
        $this->execute(<<<'SQL'
            CREATE TRIGGER role_assignments_limit_super_administrators
            BEFORE INSERT OR UPDATE ON role_assignments
            FOR EACH ROW EXECUTE FUNCTION role_assignments_limit_super_administrators()
        SQL);
    }

    public function down(): void
    {
        $this->execute('DROP TRIGGER role_assignments_limit_super_administrators ON role_assignments');
        $this->execute('DROP FUNCTION role_assignments_limit_super_administrators()');
    }
}
