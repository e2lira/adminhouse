# feat(db): add unit payer role with append-only history and unit_set_payer

Base branch: `develop`. Depends on PR 5a (`feat/du-5a-occupancies-schema`, tip `64c9b55`, stacked on 4b-ii and 4b-i): this branch is cut from that tip, so retarget to `develop` once the earlier PRs of the chain merge. Branch: `feat/du-5b-payer-history`.

## Summary

PR 5b of `developments-units` (tasks 13.1 to 13.6; spec occupancies: Responsible payer, Payer change history, Who may read and write, Database enforcement; design AD11, S-2, A1, A6). Each unit records its payer as a role (`owner` by default, or `tenant`). The role changes only through the definer function `unit_set_payer`, and every change (and the creation of the unit) appends a row to the append-only `unit_payer_changes`, so the history cannot be forged from the application. No model, controller or screen yet (PR 5c).

**Decision for the user (design amendment A6): the payer history is read under `units.view`.** The plan did not say who reads it. The spec requires `units.view`, and the payer is a role with no personal data, so it follows the unit read permission like `units` itself (A1), not `people.view`. A resident or guard reads nothing; a job reads nothing. Confirm or override: one argument in the migration and one pair in `AccessControlTest::managedWritePairs`.

**Behavior change to know about:** every unit now has at least one history row and the foreign key is `ON DELETE RESTRICT` (S-2), so a unit can no longer be deleted, not even by the super-administrator (SQLSTATE 23001). Units are deactivated, never deleted, so nothing in the application loses a capability; two earlier tests that deleted a unit were updated.

## What is included

| Area | Commits | Summary |
|---|---|---|
| Migration `20261125000001_AddUnitPayerAndHistory` | `feat(db): add unit payer role with append-only history and unit_set_payer definer function` | `units.payer`, `unit_payer_changes`, append-only trigger, definer logging trigger, `unit_set_payer`, RLS, backfill. `down()` drops all of it in reverse order. |
| Tests | same commit | `UnitPayerSchemaTest` (10), `RlsUnitPayerTest` (14), exact-snapshot rollback test, `UnitsTableTest` payer cases (2), write-implies-read pair in `AccessControlTest`, two adjusted delete tests. |
| Docs | `docs: record developments-units PR 5b` | Design A6, openspec tasks 13.1 to 13.6 with an implementation note, change-log, tasks, progress, test-results, this description. |

## How to review

1. Design A6 and the class docblock of the migration.
2. `app/config/Migrations/20261125000001_AddUnitPayerAndHistory.php`, in particular the body of `unit_set_payer` and the logging function.
3. `UnitPayerSchemaTest` (column, grants, constraints, triggers) then `RlsUnitPayerTest` (per actor: who may call the function, what a missing and a foreign unit answer, who reads the history, nobody writes it).
4. `PermissionMigrationsRollbackTest::testRollingBackThePayerMigration...` (exact snapshots and backfill).

## Verification

- `vendor/bin/phpunit` (run alone, real PostgreSQL): `OK (1946 tests, 11676 assertions)` (1911 and 11383 on the 5a review tip; 1939 and 11652 before review round 1).
- `vendor/bin/phpcs`: clean on every touched file.
- Migrations: `bin/cake migrations rollback -c test_migrations` and `migrate` run by hand on the test database; `PermissionMigrationsRollbackTest` rolls back to before this migration and forward again and compares function definitions, owners, `proconfig`, `proacl`, policies, triggers, and table and column privileges of `units` and the history, and checks the backfill. The developer database was not migrated.
- Hand mutations, each seen red and restored (details in `docs/test-results.md`): function authorized by `dev_ids()`, EXECUTE to PUBLIC, `search_path` unpinned, column grants of `payer` to the runtime role (UPDATE and INSERT), append-only trigger skipping the owner, history SELECT on `dev_ids()`, a missing unit answering differently, payer validated before the unit, FK cascade, logging trigger without its WHEN, update of the same value, NULL acting user, super-administrator branch removed.
- Security review: **requested**. A new `SECURITY DEFINER` function callable by the runtime role, a definer trigger function, and RLS. Please look at the authorization order and error uniformity in `unit_set_payer`, the pinned `search_path`, and the append-only trigger.
- Manual checks: none in a browser (no screen).
- Not verified: GitHub Actions (not pushed); PostgreSQL versions other than the local 18.4; `RlsUnitPayerTest` had no separate RED run before the migration (the mutations are its evidence; the schema test had one).

## Database and row-level security

Database change: new column `units.payer`, new table `unit_payer_changes`, three functions (`unit_set_payer`, `unit_payer_changes_log`, `unit_payer_changes_reject_change`), three triggers (`unit_payer_changes_append_only`, `units_log_payer_insert`, `units_log_payer_update`), one SELECT policy, one backfill. No new permission seed (`occupancies.manage` came with 5a) and no provisioning step.

- `units.payer text NOT NULL DEFAULT 'owner'` with `CHECK (payer IN ('owner', 'tenant'))`. The grants did not change: `Rls::managedDomainTable` grants INSERT and UPDATE on explicit column lists that never named `payer`, so the runtime role can read it but neither insert nor update it (S-2). Tests assert this per column and by running the statements as super-administrator, administrator and data_entry.
- `unit_payer_changes`: `id` identity ALWAYS, `development_id`, `unit_id`, `payer` (same CHECK), `changed_at timestamp` (database clock), `changed_by_user_id` NULL (no FK, so the trail outlives the account, as in `role_assignment_events`). Composite FK `(development_id, unit_id)` to `units (development_id, id)` ON DELETE RESTRICT: a row cannot name a unit of another development (23503) and a unit with history cannot be deleted (23001).
- Append-only for every login: a statement-level trigger raises `insufficient_privilege` (42501) for any UPDATE or DELETE, the owner login included. The runtime role holds SELECT only. TRUNCATE is not trigger-covered (no application role holds it; the test cleanup uses it as the owner).
- Logging: `unit_payer_changes_log` is SECURITY DEFINER (owner `kualitet_definer`, pinned `search_path`, no EXECUTE for PUBLIC or the runtime role), fired AFTER INSERT on `units` and AFTER UPDATE OF `payer` WHEN the value changes. It records the signed-in user from `session_user_id()` (NULL outside a request) and the clock of the database. The runtime role cannot insert history, so it cannot forge it.
- `unit_set_payer(p_unit_id bigint, p_payer text) RETURNS void`: SECURITY DEFINER, owner `kualitet_definer`, `search_path = pg_catalog, pg_temp`, EXECUTE for `kualitet_app` only (asserted from `pg_proc.proacl`). Allowed for `is_super()` or a unit whose development is in `permission_dev_ids('occupancies.manage')` (administrator and data_entry with a writable assignment, unarchived development). Missing, foreign and not-permitted units raise the same 42501 with the same message (no id in it, compared in tests, including a bad payer and a NULL id); an invalid payer is rejected afterwards with 22023; setting the current value is a no-op. Accountant, supervisor, resident, guard, read-only, disabled, ended, future, no assignment, anonymous and job sessions are refused.
- RLS of the history: ENABLE and FORCE, one SELECT policy `is_super() OR development_id = ANY(permission_dev_ids('units.view'))` (A6). Policy text is pinned in a test.
- Backfill: one row per existing unit (its payer, `created` as the time, no user), run before the triggers exist.

- [x] New tables use `App\Database\RowLevelSecurity` helpers (`enable`, `policy`, `grant`, `definerFunction`, `giveToDefiner`)
- [x] Policy tests added under `app/tests/TestCase/Database/Rls` (`RlsUnitPayerTest`: super-administrator, administrator, data_entry, accountant, supervisor, guard, resident, read-only, disabled, ended and future assignments, no assignment, anonymous, job, the administrator of another development)
- [x] New `SECURITY DEFINER` functions owned by `kualitet_definer`, `search_path` pinned, no `PUBLIC` execute

Details worth a reviewer's attention:

- Oracle check (lesson from the 5a review): the only global-key checks are the FK from the history to units, which runs inside a definer trigger and is never reachable with a caller-chosen unit id, and the lookup inside `unit_set_payer`, which answers identically for missing and foreign. The payer is validated after the unit check on purpose.
- The super-administrator is not narrowed by the archive state at the database level (same as every managed write, design A3); layer 1 in PR 5c must refuse payer changes in an archived development.
- `changed_by_user_id` comes from the request-scoped setting that every policy already trusts; it is NULL for the owner login, migrations and jobs.
- A rollback of this migration drops `units.payer` (stored payers are lost); going forward again backfills `owner`. Roll back only before anyone depends on the payer.

## Review round 1 (no criticals)

- Archived development: staff (administrator, data_entry) are refused with the same 42501 as anyone without rights and nothing changes; the super-administrator is allowed at the database level (pinned by a test; same as every managed write, design A3). **PR 5c must refuse it in layer 1**: binding note in `openspec/changes/developments-units/tasks.md` (`UnitsController::payer` runs the `DevelopmentPolicy` archived check before calling `unit_set_payer`, with an integration test).
- `occupancies.manage` is pinned: a probe role holding only that code is allowed, a probe role holding every neighboring code (`units.*`, `people.*`) is refused; swapping the code in the function turns it red.
- `unit_set_payer` evaluates the permission before the NULL test, so a missing and a foreign unit do the same work; the answers stay identical.
- Test hardening: deletion refusals asserted by constraint name (`unit_payer_changes_unit_fkey`; the occupancy case accepts either foreign key by name because RI trigger order is not a contract); rollback compared with independent literals from the units migration; `changed_at` proven to be the clock at the change; no FK on the user; history readable in an archived development; `modified` touched by a real change.
- Accepted and documented (migration docblock, `docs/runbooks/units-and-payer-history.md`): units can never be deleted (a controlled owner-login procedure removes a mistaken unit); the owner login can TRUNCATE or disable the trigger and must stay out of application reach (outside the threat model; test cleanup relies on TRUNCATE CASCADE); rollback is lossy once payers changed, back up `units` and `unit_payer_changes` first.

## Deployment and setup notes

`bin/cake migrations migrate -c migrations`. No provisioning step and no new environment variable. The migration adds a column with a default to `units` and a trigger; on a large `units` table the `ALTER TABLE` takes a short exclusive lock.

## Known limits and follow-ups

- A6 awaits the user's confirmation (see Summary); A5 (occupancy reads on `people.view`) still awaits it too.
- Units cannot be deleted any more (RESTRICT on history); if hard deletion is ever needed it needs its own, audited mechanism.
- PR 5c: `UnitPayerChangesTable` (read only), `UnitsController::payer` calling `unit_set_payer`, the no-payer flag, and the archived-development refusal in layer 1.
- Size: about 175 lines in the migration (about 35 of them docblock) and one line in `Unit`; about 620 lines of new tests plus adjustments. Within budget, no split needed.

## Checklist

- [x] Tests written first and passing
- [x] Coding standard clean
- [x] `docs/change-log.md`, `docs/tasks.md`, `docs/progress.md`, `docs/test-results.md` updated (AGENTS.md)
- [x] No secrets committed (`app/config/app_local.php` stays ignored)
- [x] Legacy application at the repository root unchanged
- [ ] GitHub Actions green (not pushed)
