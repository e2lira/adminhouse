# feat(db): permission_dev_ids, managed-domain RLS builder, units.manage seed and resident revoke

Base branch: `develop`. Depends on PR 2b (`feat/du-2b-development-management`, PR #12): this branch is cut from its tip `6184e61`, so retarget to `develop` once that PR merges. Branch: `feat/du-3a-i-permission-dev-ids`.

## Summary

PR 3a-i of `developments-units` (tasks 6.1 to 6.10; AD6, AD7, AD8, AD13, W-E). It adds the database layer that makes write rights follow the permission catalogue: a `permission_dev_ids(code)` definer function and a `RowLevelSecurity::managedDomainTable()` builder that the sections and units tables (PR 3a-ii) will use. It also seeds `units.manage` and revokes `units.view` from the resident role. No table uses the builder yet.

## What is included

| Area | Commits | Summary |
|---|---|---|
| Definer function | `feat(db): add permission_dev_ids so database write rights follow the permission catalogue` | `permission_dev_ids(p_code text) bigint[]`: request context only, active assignment today on an active user whose role holds the code; a code that does not end in `.view` also needs a writable assignment and an unarchived development. Parity test against `AccessControl::allowedFor` across role, read-only flag and window (50 combinations), with the two divergences pinned. `dataEntryA` actor in `RlsTestCase`. |
| RLS builder | `feat(db): add managedDomainTable and permissionDevIds RLS builders` | `RowLevelSecurity::managedDomainTable($table, $code, $updatableColumns)`: SELECT by `dev_ids()`, INSERT and UPDATE by `permission_dev_ids(code)`, DELETE super-administrator only, UPDATE granted on the listed columns only (never `id` or `development_id`). The code is validated against `^[a-z_]+\.[a-z_]+$`. Tested on a throwaway table guarded by `payments.create`. |
| Seed and revoke | `feat(rbac): seed units.manage and revoke units.view from the resident role` | `units.manage` for administrator and data_entry; resident loses `units.view`; role matrix test; real-database policy test for administrator and data_entry holders (allowed on an unarchived development, refused on an archived one); seed comment in 20261118000001. |
| Docs | `docs: record developments-units PR 3a-i` | ADR-0005 notes (AD7, AD8, W-E, AD13), change-log, tasks, progress, test-results, this description, openspec tasks 6.1 to 6.10. |

## How to review

1. `app/config/Migrations/20261121000001_CreatePermissionDevIds.php` (the function) and the ADR-0005 section "developments-units notes".
2. `app/src/Database/RowLevelSecurity.php` (`permissionDevIds`, `managedDomainTable`).
3. `app/config/Migrations/20261121000002_SeedUnitsManagePermission.php` and `20261121000003_RevokeResidentUnitsView.php`.
4. Tests: `RlsPermissionDevIdsTest`, `RlsPermissionDevIdsParityTest`, `RlsManagedDomainTableTest`, `AccessControlTest` (matrix and resident cases), `DevelopmentArchivedWritesPolicyTest` (real holders). Four existing RLS tests only had their expected row counts raised by one for the new `dataEntryA` user.

## Verification

- `vendor/bin/phpunit` (run alone, real PostgreSQL): `OK (894 tests, 3427 assertions)`; baseline 736 tests, 2510 assertions. The new tests were seen failing first (function and builder missing, matrix and resident cases red before the migrations).
- `vendor/bin/phpcs`: clean (exit 0)
- Migrations: `migrate`, `rollback -t 20261120000001` and `migrate` run on the `migrations` connection (database `kualitet_app`). A digest of every role/permission row was identical before the migrations and after the rollback (17 rows) and identical after the second migrate (18 rows). The function exists after migrate and is gone after rollback. The test database is rebuilt by the suite's migrator, which also applied all three.
- Security review: self-review plus mutation checks, each seen red and then restored. Function: archived check removed, read-only check removed, `ends_on` made exclusive, `starts_on` check removed, role-permission check removed, `.view` suffix test broken. Builder: DELETE policy widened, INSERT policy on the read predicate, UPDATE granted on all columns, code regex without the end-of-string flag (a trailing newline would pass), fixed-column guard removed. Seed and revoke: `data_entry` dropped, `accountant` added, revoke made a no-op. Two survivors by design: the explicit request-context check inside `permission_dev_ids` (`session_user_id()` already requires it), and the UPDATE policy `USING` clause on the read predicate (the `WITH CHECK` still requires the write predicate for the same development because `development_id` is not updatable). A fresh-context review is still due (this touches RLS).
- Manual checks: none in a browser (no UI in this PR).
- Not verified: behaviour of `managedDomainTable` on the real units and sections tables (they land in PR 3a-ii; only a throwaway probe table was used); behaviour under concurrent role changes; GitHub Actions (not pushed).

## Database and row-level security

- New definer function `public.permission_dev_ids(text) bigint[]`: `STABLE`, `SECURITY DEFINER`, owner `kualitet_definer`, `search_path = pg_catalog, pg_temp`, `EXECUTE` for `kualitet_app` only (revoked from PUBLIC). It reads `role_assignments`, `roles`, `developments`, `role_permissions` and `permissions` through the definer role's existing privileges. `down()` drops it.
- Seed: permission `units.manage`, plus `role_permissions` rows for `administrator` and `data_entry`. `super_administrator` needs no rows. `down()` deletes only those rows and the permission.
- Revoke: the `role_permissions` row of `resident` and `units.view` is deleted. `down()` re-grants only that row (idempotent).
- Security note: the resident loses `units.view`. This was confirmed by the user on 2026-11-18 (AD13). Until the resident sub-scope ships in the deferred lifecycle change, a resident-only user is refused every unit permission, because the row-level rule that would limit a resident to their own units does not exist yet (RB-R8, fail closed).
- Layer difference to know about (W-E): for write codes `permission_dev_ids` leaves archived developments out (the database refusal), while `AccessControl::allowedFor` keeps them and `DevelopmentPolicy::canManage*` refuses them, the super-administrator included. The super-administrator is not listed by the function (no development rows); policies add `is_super()`.

## Deployment and setup notes

Run `bin/cake migrations migrate -c migrations` with the migration (owner) login, after `bin/provision_db_roles.php` (the definer role must exist). No new environment variable. Deploying the revoke removes the resident's `units.view` immediately.

## Known limits and follow-ups

- `managedDomainTable` needs the list of updatable columns (the task named only table and code); column grants cannot be written without it.
- The existing `RowLevelSecurity::identifier()` regex has no end-of-string flag, so a table or column name with a trailing newline is accepted. It is only called from migrations with literal names; left unchanged here.
- `people.manage` and `occupancies.manage` are not seeded here (their slices seed them, AD6).
- Size: 232 added lines of non-test code (3 in a seed comment), 811 test lines. Under the 400-line budget, so the planned 3a-i-a / 3a-i-b split was not needed.

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
