# feat(db): permission_dev_ids, managed-domain RLS builder, units.manage seed and resident revoke

Base branch: `develop`. Depends on PR 2b (`feat/du-2b-development-management`, PR #12): this branch is cut from its tip `6184e61`, so retarget to `develop` once that PR merges. Branch: `feat/du-3a-i-permission-dev-ids`.

## Summary

PR 3a-i of `developments-units` (tasks 6.1 to 6.10; AD6, AD7, AD8, AD13, W-E). It adds the database layer that makes reads and writes follow the permission catalogue: a `permission_dev_ids(code)` definer function and a `RowLevelSecurity::managedDomainTable()` builder that the sections and units tables (PR 3a-ii) will use. It also seeds `units.manage` and revokes `units.view` from the resident role. No table uses the builder yet.

Design amendment A1 (user decision 2026-11-19, "Si, por permiso"): reads on a managed table follow the read permission at the database layer too, not `dev_ids()`. See `openspec/changes/developments-units/design.md` and the ADR-0005 notes.

## What is included

| Area | Commits | Summary |
|---|---|---|
| Definer function | `feat(db): add permission_dev_ids so database write rights follow the permission catalogue` | `permission_dev_ids(p_code text) bigint[]`: request context only, active assignment today on an active user whose role holds the code; a code that does not end in `.view` also needs a writable assignment and an unarchived development. `dataEntryA` actor in `RlsTestCase`. |
| RLS builder | `feat(db): add managedDomainTable and permissionDevIds RLS builders`, then `feat(db): make managed-domain reads follow a read permission and tighten the column contract` | `RowLevelSecurity::managedDomainTable($table, $readCode, $writeCode, $updatableColumns, $insertOnlyColumns = [])`. The read code must end in `.view` and the write code must not (it throws otherwise). SELECT is `is_super() OR development_id = ANY(permission_dev_ids(readCode))`; INSERT and UPDATE use the write code; DELETE is super-administrator only. INSERT is granted on an explicit column list (`development_id`, the updatable and insert-only columns, never `id`); UPDATE on the listed columns only (never `id`, `development_id` or `created`); duplicates are rejected; every identifier is matched with the end-of-string flag. |
| Seed and revoke | `feat(rbac): seed units.manage and revoke units.view from the resident role` | `units.manage` for administrator and data_entry; resident loses `units.view`; role matrix test; seed comment in 20261118000001. |
| Tests (round 1) | `test(db): widen the layer parity matrix and pin the business-day clock`, `test(db): roll the permission migrations back and forward and compare the matrix` | Parity over every catalogue code with archived and user-status dimensions and computed divergences; far-time-zone window test; fixture dates from `business_today()`; policy test with full scoped reads and read-only holders; automated rollback and forward test. |
| Docs | `docs: record developments-units PR 3a-i` and a review-round docs commit | ADR-0005 notes (AD7, AD8, W-E, AD13, D7 warning), design amendment A1, change-log, tasks, progress, test-results, this description, openspec tasks 6.1 to 6.10. |

## How to review

1. `app/config/Migrations/20261121000001_CreatePermissionDevIds.php` (the function) and the ADR-0005 section "developments-units notes".
2. `app/src/Database/RowLevelSecurity.php` (`permissionDevIds`, `managedDomainTable`, class and method docs describe the table contract).
3. `app/config/Migrations/20261121000002_SeedUnitsManagePermission.php` and `20261121000003_RevokeResidentUnitsView.php`.
4. Tests: `RlsPermissionDevIdsTest`, `RlsPermissionDevIdsParityTest`, `RlsManagedDomainTableTest`, `PermissionMigrationsRollbackTest`, `AccessControlTest` (matrix and resident cases), `DevelopmentArchivedWritesPolicyTest` (real holders). Two existing RLS tests only had their expected counts derived from the fixture instead of being magic numbers.

## Verification

- `vendor/bin/phpunit` (run alone, real PostgreSQL): `OK (1111 tests, 6931 assertions)`; baseline 736 tests, 2510 assertions; 894 and 3427 before review round 1. The new tests were seen failing first where behaviour changed (builder signature: 73 errors).
- `vendor/bin/phpcs`: clean (exit 0)
- Migrations: first round, `migrate`, `rollback -t 20261120000001` and `migrate` on the `migrations` connection (database `kualitet_app`): the role/permission digest was identical before the migrations and after the rollback (17 rows) and identical after the second migrate (18 rows). Round 1 changed no migration, so that manual run was not repeated; it is now an automated test (`PermissionMigrationsRollbackTest`) that rolls the three migrations back and forward on the test database through the migrations API, in a `finally` so a failure cannot leave later tests on a half-migrated schema, and compares the role/permission rows and the function. Not parallel-safe.
- Security review: self-review plus mutation checks, each seen red and then restored. Function: archived check removed, read-only check removed, `ends_on` made exclusive, `starts_on` check removed, role-permission check removed, `.view` suffix broken, `CURRENT_DATE` instead of `business_today()`. Builder: SELECT put back on `dev_ids()`, SELECT on the write code, read-code and write-code `.view` checks removed, DELETE widened, INSERT on the read predicate, INSERT and UPDATE granted on all columns, `created` updatable, duplicate check removed, identifier regex without the end-of-string flag. Seed and revoke: `data_entry` dropped, `accountant` added, revoke a no-op, revoke `down()` a no-op. Policy: archived check off, permission ignored. Survivors: the explicit request-context check inside `permission_dev_ids` (`session_user_id()` already requires it, so it is a second guard); the UPDATE policy `USING` on a different predicate than `WITH CHECK` (both use the write predicate now, and `development_id` is not updatable); the seed `down()` row delete (the permission delete cascades to its rows). A fresh-context review of the first round found no critical issue; the second review is still due.
- Manual checks: none in a browser (no UI in this PR).
- Not verified: behaviour of `managedDomainTable` on the real units and sections tables (they land in PR 3a-ii; only a throwaway probe table was used); behaviour under concurrent role changes; GitHub Actions (not pushed).

## Database and row-level security

- New definer function `public.permission_dev_ids(text) bigint[]`: `STABLE`, `SECURITY DEFINER`, owner `kualitet_definer`, `search_path = pg_catalog, pg_temp`, `EXECUTE` for `kualitet_app` only (revoked from PUBLIC). It reads `role_assignments`, `roles`, `developments`, `role_permissions` and `permissions` through the definer role's existing privileges and judges the assignment window on `business_today()`, never on the session time zone. `down()` drops it.
- Seed: permission `units.manage`, plus `role_permissions` rows for `administrator` and `data_entry`. `super_administrator` needs no rows. `down()` deletes only those rows and the permission.
- Revoke: the `role_permissions` row of `resident` and `units.view` is deleted. `down()` re-grants only that row (idempotent).
- Builder (no table uses it yet): SELECT follows the read permission, INSERT and UPDATE the write permission, DELETE is super-administrator only. Consequences to know about: a resident or guard that holds an assignment reads nothing from a managed table; a read-only assignment reads but does not write; a job reads nothing from a managed table (the function is request-only), so a future job that needs such rows needs its own mechanism. A table that uses it needs a NOT NULL `development_id` and a database-generated key; PR 3a-ii must list `modified` among the updatable columns and `created` among the insert-only ones. INSERT is granted on an explicit column list (chosen over a plain INSERT grant plus `GENERATED ALWAYS` ids: it also stops any later column from being set by accident, and works with `BY DEFAULT` identity columns).
- Security note: the resident loses `units.view`. This was confirmed by the user on 2026-11-18 (AD13). Until the resident sub-scope ships in the deferred lifecycle change, a resident-only user is refused every unit permission, and now also reads no row of a managed table at the database level. That change must narrow with a RESTRICTIVE policy, not add a PERMISSIVE one (permissive policies are OR-ed and would reopen the table).
- Layer difference to know about (W-E): for write codes `permission_dev_ids` leaves archived developments out (the database refusal for everyone but the super-administrator), while `AccessControl::allowedFor` keeps them and `DevelopmentPolicy::canManage*` refuses them, the super-administrator included. A super-administrator write to an archived development passes RLS (`is_super()` is not narrowed; tested) and is stopped only by layer 1. The super-administrator is not listed by the function (no development rows); policies add `is_super()`.

## Deployment and setup notes

Run `bin/cake migrations migrate -c migrations` with the migration (owner) login, after `bin/provision_db_roles.php` (the definer role must exist). No new environment variable. Deploying the revoke removes the resident's `units.view` immediately.

## Known limits and follow-ups

- `managedDomainTable` needs the list of updatable columns and the optional insert-only columns (the task named only table and code); column grants cannot be written without them.
- `people.manage` and `occupancies.manage` are not seeded here (their slices seed them, AD6).
- Size: 269 added lines of non-test code across the round (3 in a seed comment), about 1235 test lines. Under the 400-line budget for non-test code, so the planned 3a-i-a / 3a-i-b split was not needed.

## Checklist

- [x] Tests written first and passing
- [x] Coding standard clean
- [x] `docs/change-log.md`, `docs/tasks.md`, `docs/progress.md`, `docs/test-results.md` updated (AGENTS.md)
- [x] No secrets committed (`app/config/app_local.php` stays ignored)
- [x] Legacy application at the repository root unchanged
- [ ] GitHub Actions green (not pushed)

## Chain context

Start: PR 2b tip (development management and the W-E policy methods). End: the permission-driven database helper, the `units.manage` seed and the resident revoke. Prior dependency: PR 2b. Follow-up: PR 3a-ii (sections and units schema and RLS with `managedDomainTable`). Out of scope: the units and sections tables, `people.manage` and `occupancies.manage` seeds, the resident sub-scope.

```
feat/units-sdd -> ... -> 2a -> 2b -> [3a-i (here)] -> 3a-ii -> ... -> 5c
```
