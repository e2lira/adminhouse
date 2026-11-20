# feat(db): add occupancies with exclusion constraints, btree_gist provisioning and occupancies.manage

Base branch: `develop`. Depends on PR 4b-ii (`feat/du-4b-ii-people-ui`, tip `b0389f9`, stacked on 4b-i): this branch is cut from that tip, so retarget to `develop` once the earlier PRs of the chain merge. Branch: `feat/du-5a-occupancies-schema`.

## Summary

PR 5a of `developments-units` (tasks 12.1 to 12.8; spec occupancies: Occupancy records, Person linked to the development, No overlapping occupancies of one person, One primary owner and one primary tenant at a time, History is kept, Database enforcement; design AD6, AD9, AD12, W-D, D7). It adds the `occupancies` table (owner and tenant periods of a person on a unit), the `occupancies.manage` permission for administrator and data_entry, and the `btree_gist` extension the exclusion constraints need. No model, controller or screen yet (PR 5c); `unit_set_payer` and the payer history are PR 5b.

**Decision for the user (design amendment A5): occupancy reads follow `people.view`, not `dev_ids()`.** Task 12.6 said SELECT through `dev_ids()`. An occupancy says who lives in which unit, which is personal data, and the user decided twice that database reads follow the view permission (A1 `units.view`, A2 `people.view`). So a resident or guard reads no occupancy at the database level, not even the one of their own person (D7 stays deferred). Confirm or override: the change is one argument in the migration and one pair in `AccessControlTest::managedWritePairs`.

## What is included

| Area | Commits | Summary |
|---|---|---|
| Provisioning | `feat(db): provision btree_gist and add the guard migrations use to require it` | `bin/provision_db_roles.php` runs `CREATE EXTENSION IF NOT EXISTS btree_gist WITH SCHEMA public` in every provisioned database; `App\Database\ExtensionGuard::assertInstalled()` throws a `RuntimeException` with the provisioning command when `pg_extension` lacks it; the CI step name now says it provisions the extension (CI already ran the script before the tests). |
| Migration `20261124000001_CreateOccupancies` | `feat(db): add occupancies with exclusion constraints, end-once trigger and occupancies.manage` | Guard, `occupancies.manage` seed (S-4: first), table with constraints, triggers, RLS. `down()` drops the table, the trigger function and its own permission rows only; the extension stays. |
| Tests | same commit | `OccupanciesSchemaTest` (50), `RlsOccupanciesTest` (32), `ExtensionGuardTest` (6), role matrix and write-implies-read pair in `AccessControlTest`, rollback test extended with exact object lists. |
| Docs | `docs: record developments-units PR 5a` | Design A5, openspec tasks 12.1 to 12.8 with an implementation note, change-log, tasks, progress, test-results, this description. |

## How to review

1. Design A5 and the class docblock of the migration.
2. `app/config/Migrations/20261124000001_CreateOccupancies.php`: the table constraints, `occupancies_guard_update`, the `managedDomainTable` call.
3. `app/bin/provision_db_roles.php` (one statement) and `app/src/Database/ExtensionGuard.php`.
4. `OccupanciesSchemaTest` (constraints, boundary days, trigger) then `RlsOccupanciesTest` (per actor and command).

## Verification

- `vendor/bin/phpunit` (run alone, real PostgreSQL): `OK (1911 tests, 11383 assertions)` (1808 and 10503 on the 4b-ii review tip).
- `vendor/bin/phpcs`: clean on every touched file.
- Migrations: `bin/cake migrations rollback -c test_migrations` and `migrate` run by hand on the test database (table, trigger function and permission row go and come back, `btree_gist` stays), and `PermissionMigrationsRollbackTest` compares the exact policy, trigger and constraint lists before `down()` and after the second `up()`. `bin/provision_db_roles.php --database=kualitet_app_test` was run once against the test database. The developer database was not provisioned or migrated.
- Hand mutations, each seen red and restored (details in `docs/test-results.md`): SELECT on `dev_ids()`, SELECT with an own-row clause, INSERT on `people.view`, DELETE opened to staff, primary exclusion without `WHERE is_primary`, closed-range overlap, overlap without role, a plain FK on `person_id`, period CHECK `>=`, person FK without RESTRICT, trigger allowing date to date, extra column grants, EXECUTE to PUBLIC, provisioning line removed.
- Security review: requested. RLS and a new trigger; the policy predicates and the FK-oracle behavior (below) deserve a fresh-context look.
- Manual checks: none in a browser (no screen).
- Not verified: GitHub Actions (not pushed, so the CI provisioning of `btree_gist` on the `postgres:18` service is untested there; the script runs as the `postgres` superuser, which can create the extension); PostgreSQL versions other than the local 18.4; the migration failure path against a real database without the extension (covered by `ExtensionGuardTest` with a missing name, not by running the migration).

## Database and row-level security

Database change: new table `occupancies`, new trigger function `occupancies_guard_update()` and two triggers, one permission seed (`occupancies.manage` for administrator and data_entry), one extension (`btree_gist`, by provisioning, not by the migration).

Table `occupancies`: `id`, `development_id` NOT NULL, `unit_id`, `person_id` NOT NULL (W-D: a NULL would skip the exclusion constraints), `role` CHECK owner or tenant, `is_primary` NOT NULL default false, `starts_on` NOT NULL, `ends_on` NULL, `created`, `modified`.

- `CHECK (ends_on IS NULL OR ends_on > starts_on)`; the period is the half-open `[starts_on, ends_on)`, so the end day is free and the start day is taken (boundary-day tests for both).
- FK `(development_id, unit_id)` to `units (development_id, id)`.
- FK `(person_id, development_id)` to `person_developments`, ON DELETE RESTRICT, and no plain FK on `person_id` (AD9). A nonexistent person and a person linked only to another development fail the same constraint (`occupancies_person_development_fkey`, SQLSTATE 23503), compared in the schema test and under RLS as a data_entry user. The same holds for units.
- `occupancies_no_overlap_per_person`: `EXCLUDE USING gist (development_id WITH =, unit_id WITH =, person_id WITH =, role WITH =, daterange(starts_on, ends_on, '[)') WITH &&)`.
- `occupancies_one_primary_per_role`: `EXCLUDE USING gist (development_id WITH =, unit_id WITH =, role WITH =, daterange(starts_on, ends_on, '[)') WITH &&) WHERE (is_primary)`. Co-owners and roommates (non-primary) are not limited by it; a primary owner and a primary tenant coexist.
- Trigger `occupancies_guard_update` (every login, the owner included): only `ends_on` and `modified` may change, and `ends_on` only from NULL to a date (the CHECK keeps it after the start). The generic `forbid_development_id_change` trigger is also attached. Corrections: the super-administrator deletes, staff recapture.
- RLS through `Rls::managedDomainTable('occupancies', 'people.view', 'occupancies.manage', ['ends_on', 'modified'], ['unit_id', 'person_id', 'role', 'is_primary', 'starts_on', 'created'])`: SELECT `is_super() OR development_id = ANY(permission_dev_ids('people.view'))` (A5, no own-row clause, D7); INSERT and UPDATE on `occupancies.manage`; DELETE super only; UPDATE granted on `ends_on` and `modified` only; INSERT granted on an explicit column list without `id`. A job reads and writes nothing (`permission_dev_ids` is request-only). Writes into an archived development are refused to staff at the database level.

- [x] New tables use `App\Database\RowLevelSecurity` helpers (`managedDomainTable`, `forbidDevelopmentChange`, `giveToDefiner`); it fit as is because its read and write codes are independent.
- [x] Policy tests added under `app/tests/TestCase/Database/Rls` (`RlsOccupanciesTest`: administrator, data_entry, accountant, supervisor, guard, resident (whose own person has an occupancy), read-only, disabled, ended and future assignments, no assignment, no session, a job, the administrator of another development, the super-administrator).
- [x] The one new function is a trigger function owned by `kualitet_definer`, `search_path` pinned, no `PUBLIC` or runtime-role execute (asserted from `pg_proc`). It is not `SECURITY DEFINER`, like `forbid_development_id_change`.

Details worth a reviewer's attention:

- Under RLS PostgreSQL hides the DETAIL of 23505, 23503 and 23P01 for the app role, but the message still names the constraint. The later ORM mapping (PR 5c) must key on SQLSTATE plus constraint name. `ON DELETE RESTRICT` reports 23001 (restrict_violation), not 23503.
- Review round 1 (W1): exclusion checks run before the composite foreign key, so a key on the global `unit_id` alone let a writer of B tell from 23P01 versus 23503 whether a unit of A had a conflicting occupancy. Both constraints now lead with `development_id WITH =`; a unit of another development gives the same `occupancies_unit_fkey` answer as a missing one (tested for administrator and data_entry of B, with a conflicting primary and a conflicting same-person overlap).
- Foreign-key and exclusion checks bypass RLS. Safety rests on the INSERT policy forcing `development_id` into the developments the caller writes in (so the checks only see rows of developments the caller can read), and on the keys being per unit, and units belong to one development.
- No role holds `units.view` without `people.view`, so the spec line "reading requires `units.view`" loses nothing; layer 1 in PR 5c can still check both.

## Deployment and setup notes

**Provisioning step required before migrating**: re-run `php bin/provision_db_roles.php` as a PostgreSQL superuser (it is idempotent) so `btree_gist` exists in the database; then `bin/cake migrations migrate -c migrations`. Without the extension the migration stops with a message that names this command. CI already runs the script before the tests. The extension is created in schema `public` and stays after a rollback.

## Known limits and follow-ups

- A5 awaits the user's confirmation (see Summary).
- Resident sub-scope (own occupancies) stays deferred (D7): it must narrow with a RESTRICTIVE policy (design A1).
- Size: about 150 lines in the migration, 40 in `ExtensionGuard`, 7 in the provisioning script. About 900 test lines. Within budget, no split needed.
- Accepted as designed (review S1, S2): no bound on `ends_on` beyond being after `starts_on` (retroactive end allowed); a mistaken same-day row is deleted by the super-administrator and recaptured; setting `ends_on` to its current value again is accepted and changes nothing.
- `occupancies.manage` also covers `unit_set_payer` in PR 5b.

## Checklist

- [x] Tests written first and passing
- [x] Coding standard clean
- [x] `docs/change-log.md`, `docs/tasks.md`, `docs/progress.md`, `docs/test-results.md` updated (AGENTS.md)
- [x] No secrets committed (`app/config/app_local.php` stays ignored)
- [x] Legacy application at the repository root unchanged
- [ ] GitHub Actions green (not pushed)
