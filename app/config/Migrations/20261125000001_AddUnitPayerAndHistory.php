<?php
declare(strict_types=1);

use App\Database\RowLevelSecurity as Rls;
use Migrations\BaseMigration;

/**
 * The payer role of a unit and its unforgeable history (spec occupancies: Responsible payer, Payer change history,
 * Database enforcement; design AD11, S-2, A6).
 *
 * In this order:
 * 1. `units.payer` (`owner` or `tenant`, default `owner`). The runtime role never writes it: the column grants of
 *    `Rls::managedDomainTable` list the writable columns, `payer` is not among them, so INSERT takes the default and
 *    UPDATE is refused. It reads it (table-level SELECT);
 * 2. `unit_payer_changes`: `development_id, unit_id, payer, changed_at, changed_by_user_id`. `(development_id, unit_id)`
 *    references `units (development_id, id)` ON DELETE RESTRICT, so a unit that has history cannot be deleted (every
 *    unit has one) and a row cannot name a unit of another development. There is no foreign key on the user, as in
 *    `role_assignment_events`: the trail outlives the account. It is append-only for EVERY login, the owner included: a
 *    statement-level trigger rejects any UPDATE or DELETE (insufficient_privilege). Only the owner of the table could
 *    still drop the trigger or the table, which no application login can;
 * 3. the logging trigger function (`unit_payer_changes_log`, SECURITY DEFINER, pinned `search_path`, not callable):
 *    after an INSERT on `units`, and after an UPDATE of `payer` that changes its value, it appends one row with the
 *    database clock and the signed-in user of the request (`session_user_id()`; NULL outside a request, for example
 *    for a migration or a job). The runtime role cannot insert into the history, so the log cannot be forged;
 * 4. backfill: one row per existing unit, with its payer and `created` as the time, before the triggers exist;
 * 5. `unit_set_payer(p_unit_id, p_payer)`, the only way to change a payer: SECURITY DEFINER, pinned `search_path`,
 *    EXECUTE for the runtime role only. It allows the super-administrator and the developments in
 *    `permission_dev_ids('occupancies.manage')` (a writable assignment, unarchived development). A missing, foreign
 *    and not-permitted unit all give the same error, so the call is not an existence oracle; the payer is validated
 *    only after that check, so a wrong payer reveals nothing about a unit either. The permission check is evaluated
 *    before the NULL test, so a missing and a foreign unit do the same work. Setting the current value again is a
 *    no-op;
 * 6. row-level security of the history: only SELECT, for `is_super()` or the developments in
 *    `permission_dev_ids('units.view')` (A6). It is unit data (a role, owner or tenant, no personal data), so it follows
 *    the unit read permission like `units` itself.
 *
 * Accepted limits:
 * - The owner login (`kualitet_owner`) is outside the threat model: it owns the tables, so it can TRUNCATE the history
 *   or DISABLE/DROP its trigger. It must stay out of application reach (it is only the migration login); the test
 *   cleanup relies on TRUNCATE ... CASCADE, so no TRUNCATE trigger is added.
 * - Every unit has history and the key is RESTRICT, so a unit can never be deleted. A mistaken unit is removed by a
 *   controlled migration (see docs/runbooks/units-and-payer-history.md).
 * - `down()` is lossy once payers changed: it drops `units.payer`, and going forward again backfills `owner`. Back up
 *   `units` and `unit_payer_changes` before rolling back.
 *
 * `down()` removes exactly this, in reverse order. The grants of `units` are not touched by either direction.
 */
class AddUnitPayerAndHistory extends BaseMigration
{
    private const ROLES = "('owner', 'tenant')";

    /**
     * @return void
     */
    public function up(): void
    {
        $roles = self::ROLES;
        $this->execute(
            "ALTER TABLE units ADD COLUMN payer text NOT NULL DEFAULT 'owner' "
            . "CONSTRAINT units_payer_check CHECK (payer IN {$roles})",
        );

        $this->execute(<<<SQL
            CREATE TABLE unit_payer_changes (
                id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                development_id bigint NOT NULL,
                unit_id bigint NOT NULL,
                payer text NOT NULL CONSTRAINT unit_payer_changes_payer_check CHECK (payer IN {$roles}),
                changed_at timestamp NOT NULL,
                changed_by_user_id bigint NULL,
                CONSTRAINT unit_payer_changes_unit_fkey FOREIGN KEY (development_id, unit_id)
                    REFERENCES units (development_id, id) ON DELETE RESTRICT
            )
        SQL);
        $this->execute(
            'CREATE INDEX unit_payer_changes_development_id_unit_id_idx '
            . 'ON unit_payer_changes (development_id, unit_id, changed_at, id)',
        );

        $this->execute(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.unit_payer_changes_reject_change() RETURNS trigger
            LANGUAGE plpgsql SET search_path = pg_catalog, pg_temp AS $$
            BEGIN
                RAISE EXCEPTION 'unit_payer_changes is append-only'
                    USING ERRCODE = 'insufficient_privilege';
            END;
            $$
        SQL);
        foreach (Rls::giveToDefiner('public.unit_payer_changes_reject_change()') as $sql) {
            $this->execute($sql);
        }
        $this->execute('REVOKE ALL ON FUNCTION public.unit_payer_changes_reject_change() FROM PUBLIC');
        $this->execute(
            'CREATE TRIGGER unit_payer_changes_append_only BEFORE UPDATE OR DELETE ON unit_payer_changes '
            . 'FOR EACH STATEMENT EXECUTE FUNCTION public.unit_payer_changes_reject_change()',
        );

        foreach (
            Rls::definerFunction('unit_payer_changes_log', [], 'trigger', 'plpgsql', 'VOLATILE', <<<'SQL'
                BEGIN
                    INSERT INTO public.unit_payer_changes
                        (development_id, unit_id, payer, changed_at, changed_by_user_id)
                    VALUES (
                        NEW.development_id, NEW.id, NEW.payer, pg_catalog.clock_timestamp(),
                        public.session_user_id()
                    );
                    RETURN NULL;
                END;
            SQL, false) as $sql
        ) {
            $this->execute($sql);
        }
        $this->execute(Rls::grantDefiner('unit_payer_changes', 'INSERT'));

        $this->execute(
            'INSERT INTO unit_payer_changes (development_id, unit_id, payer, changed_at) '
            . 'SELECT development_id, id, payer, created FROM units ORDER BY id',
        );
        $this->execute(
            'CREATE TRIGGER units_log_payer_insert AFTER INSERT ON units FOR EACH ROW '
            . 'EXECUTE FUNCTION public.unit_payer_changes_log()',
        );
        $this->execute(
            'CREATE TRIGGER units_log_payer_update AFTER UPDATE OF payer ON units FOR EACH ROW '
            . 'WHEN (OLD.payer IS DISTINCT FROM NEW.payer) EXECUTE FUNCTION public.unit_payer_changes_log()',
        );

        foreach (
            Rls::definerFunction(
                'unit_set_payer',
                ['p_unit_id' => 'bigint', 'p_payer' => 'text'],
                'void',
                'plpgsql',
                'VOLATILE',
                <<<SQL
                    DECLARE
                        v_development_id bigint;
                        v_allowed boolean;
                    BEGIN
                        SELECT u.development_id INTO v_development_id FROM public.units u WHERE u.id = p_unit_id;
                        v_allowed := public.is_super()
                            OR COALESCE(
                                v_development_id = ANY (public.permission_dev_ids('occupancies.manage')), false
                            );
                        IF v_development_id IS NULL OR NOT v_allowed THEN
                            RAISE EXCEPTION 'Not allowed to change the payer of this unit'
                                USING ERRCODE = 'insufficient_privilege';
                        END IF;
                        IF p_payer IS NULL OR p_payer NOT IN {$roles} THEN
                            RAISE EXCEPTION 'The payer must be owner or tenant'
                                USING ERRCODE = 'invalid_parameter_value';
                        END IF;
                        UPDATE public.units
                        SET payer = p_payer, modified = pg_catalog.now()
                        WHERE id = p_unit_id AND payer IS DISTINCT FROM p_payer;
                    END;
                SQL,
            ) as $sql
        ) {
            $this->execute($sql);
        }

        foreach (Rls::enable('unit_payer_changes') as $sql) {
            $this->execute($sql);
        }
        $this->execute(Rls::policy(
            'unit_payer_changes',
            'SELECT',
            Rls::IS_SUPER . ' OR development_id = ANY (' . Rls::permissionDevIds('units.view') . ')',
        ));
        $this->execute(Rls::grant('unit_payer_changes', 'SELECT'));
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->execute(Rls::dropFunction('unit_set_payer', ['bigint', 'text']));
        $this->execute('DROP TRIGGER units_log_payer_update ON units');
        $this->execute('DROP TRIGGER units_log_payer_insert ON units');
        $this->execute(Rls::dropFunction('unit_payer_changes_log'));
        $this->execute('DROP TABLE unit_payer_changes');
        $this->execute(Rls::dropFunction('unit_payer_changes_reject_change'));
        $this->execute('ALTER TABLE units DROP COLUMN payer');
    }
}
