# feat(db): sections and units tables with managed row-level security

Base branch: `develop`. Depends on PR 3a-i (`feat/du-3a-i-permission-dev-ids`): this branch is cut from its tip `4ae4008`, so retarget to `develop` once that PR merges. Branch: `feat/du-3a-ii-sections-units-schema`.

## Summary

PR 3a-ii of `developments-units` (tasks 7.1 to 7.6; spec units: Sections, Unit identity, Mixed unit types, Measurements, Active flag, Database enforcement; design AD4, AD7 and amendment A1). It creates the `sections` and `units` tables with their constraints and puts both under the managed-domain policies from PR 3a-i: reads need `units.view`, writes need `units.manage`, only the super-administrator deletes. No application code uses the tables yet (PR 3b-i adds the ORM layer).

## What is included

| Area | Commits | Summary |
|---|---|---|
| Sections | `feat(db): add sections table with per-development names and managed RLS` | Table, `UNIQUE (development_id, id)` and `UNIQUE (development_id, name)`, blank-name check, development-change trigger, managed RLS; new builder `RowLevelSecurity::forbidDevelopmentChange($table)` (`catalogTable` now uses it); `ownerSqlState()` test helper; schema tests and per-actor RLS tests (`SectionsSchemaTest`, `RlsSectionsUnitsTest`). |
| Units | `feat(db): add units table with composite section key, measurements and managed RLS` | Table with composite foreign key `(development_id, section_id)`, `UNIQUE NULLS NOT DISTINCT (development_id, section_id, number)`, type and measurement checks, `is_active`, trigger, managed RLS; `UnitsSchemaTest`; the RLS tests now run on both tables; `PermissionMigrationsRollbackTest` also rolls both tables back and forward. |
| Docs | `docs: record developments-units PR 3a-ii` | openspec tasks 7.1 to 7.6 and implementation note, change-log, tasks, progress, test-results, this description. |

## How to review

1. `app/config/Migrations/20261122000001_CreateSections.php` and `20261122000002_CreateUnits.php` (the class docblocks explain each constraint).
2. `app/src/Database/RowLevelSecurity.php` (`forbidDevelopmentChange`, a small extraction from `catalogTable`).
3. Tests: `RlsSectionsUnitsTest` (one scenario, both tables), `UnitsSchemaTest`, `SectionsSchemaTest`, `PermissionMigrationsRollbackTest`.

## Verification

- `vendor/bin/phpunit` (run alone, real PostgreSQL): `OK (1181 tests, 7360 assertions)`; 1115 and 6961 on the 3a-i tip. The schema tests were seen failing first (undefined table), the blank-name test caught that `btrim` keeps a tab.
- `vendor/bin/phpcs`: clean (exit 0)
- Migrations: `rollback` and `migrate` on the test database through `bin/cake migrations ... -c test_migrations` worked, and `PermissionMigrationsRollbackTest` now rolls the two tables, their 8 policies and 2 triggers back and forward and compares them (task 7.6 evidence).
- Security review: self-review plus mutation checks, each seen red and restored: DELETE policy on the write predicate (2 tests fail), SELECT policy on `dev_ids()` instead of the read permission (4 tests fail). The RLS tests read every denied write back as the owner, because an RLS-blocked UPDATE or DELETE matches zero rows instead of raising an error, and they check that probing does not work (below). A fresh-context review is recommended before merge (RLS change).
- Manual checks: none in a browser (no UI in this PR).
- Not verified: the developer database `kualitet_app` was not migrated (only the test database); ORM saves and finds on these tables (PR 3b-i); the `INSERT ... RETURNING` path on the real tables beyond the SQL-level tests (the 3a-i ORM test covers the mechanism on a probe table); behaviour under concurrent writes; GitHub Actions (not pushed).

## Database and row-level security

- Table `sections`: `id` identity key, `development_id` NOT NULL (FK to `developments`), `name` (non-blank, `name ~ '\S'`, at most 150 characters), `created`, `modified`. `UNIQUE (development_id, id)` (target of the composite key of `units`) and `UNIQUE (development_id, name)`.
- Table `units`: `id` identity key, `development_id` NOT NULL, optional `section_id`, `number` (non-blank, at most 50 characters), `type` in `apartment`, `house`, `lot`, optional `lot_area_m2 numeric(12,2) > 0`, optional `indiviso_pct numeric(7,4)` in `(0, 100]` (both reject `NaN`, which PostgreSQL ranks above every number), `is_active` default true, `created`, `modified`. `UNIQUE (development_id, id)`; foreign key `(development_id, section_id)` to `sections (development_id, id)` (a unit can only use a section of its own development; with no section the key is not checked); `UNIQUE NULLS NOT DISTINCT (development_id, section_id, number)` (units with no section share one namespace per development). `NULLS NOT DISTINCT` needs PostgreSQL 15 or later.
- Trigger: `<table>_forbid_development_change` (BEFORE UPDATE, both tables) calls the existing generic function `public.forbid_development_id_change()` and raises `check_violation` when `development_id` changes, for the owner and definer logins too. The function is the one RLS-SEC-001 created (owned by `kualitet_definer`, `search_path` pinned, no PUBLIC execute) and is reused by later tables through `Rls::forbidDevelopmentChange()`.
- Policies (from `Rls::managedDomainTable($table, 'units.view', 'units.manage', ...)`, ENABLE and FORCE RLS, one per command, for `kualitet_app`): SELECT `is_super() OR development_id = ANY(permission_dev_ids('units.view'))`; INSERT (WITH CHECK) and UPDATE use `units.manage` (a writable assignment and an unarchived development); DELETE `is_super()` only. Resulting behaviour, tested per actor: administrator, data_entry and accountant of A read A only; administrator and data_entry write in A only; a resident or guard with an assignment reads nothing (they lack `units.view`); a read-only assignment reads but does not write; a job reads and writes nothing; the other development is invisible, so cross-development UPDATE and DELETE match no row; administrator and data_entry delete no row.
- Grants (runtime role, column level): SELECT and DELETE on the table; INSERT on `development_id`, the updatable columns and `created`; UPDATE on `name, modified` (sections) and `section_id, number, type, lot_area_m2, indiviso_pct, is_active, modified` (units). Never `id`, `development_id` or `created`. The definer role gets the table privileges the other scoped tables give it. No sequence grant (the ORM reads the id from `RETURNING *`, see PR 3a-i).
- Existence probing: foreign-key and unique checks bypass RLS, so all keys carry the development. Tested: a name or number that exists only in another development is accepted (no "duplicate" answer), and a unit pointing at a section of another development gets the same SQLSTATE `23503` as one pointing at an id that does not exist.
- `down()` of each migration drops its table (policies, trigger and grants go with it). `units` is dropped before `sections` on rollback because of the foreign key.

## Deployment and setup notes

Run `bin/cake migrations migrate -c migrations` with the migration (owner) login. PostgreSQL 15 or later is required (`UNIQUE NULLS NOT DISTINCT`). No new environment variable.

## Known limits and follow-ups

- The blank check for `name` and `number` uses `~ '\S'` instead of the `btrim(...) <> ''` sketched in the design, because `btrim` keeps a tab or line break; PR 3b-i validation should trim the same way.
- Review round 1 additions: the length bounds (150 for `name`, 50 for `number`) and the NaN checks must be mirrored by the PR 3b-i validation; job and anonymous write tests, a privilege check on every column, a supervisor actor and SQLSTATE 42501 assertions were added; full suite `OK (1181 tests, 7360 assertions)`.
- `created` and `modified` are set by the client (the ORM), so they are not an audit trail: a holder of `units.manage` can write any value to them. A trusted timestamp would need a trigger.
- The `down()` methods are for development and test databases only: they drop the tables with their data.
- `created` and `modified` have no database default, as in the other tables; the ORM timestamp behaviour sets them (PR 3b-i).
- Units are never deleted by the application; a super-administrator delete of a section that still has units is refused by the foreign key.
- `payer` arrives with PR 5b; occupancies reference `units (development_id, id)` in PR 5a.
- Size: about 146 added lines of non-test code (two migrations and a small builder extraction) and about 780 test lines. Under the 400-line budget, so the planned sections / units split was not needed beyond the two commits.

## Checklist

- [x] Tests written first and passing
- [x] Coding standard clean
- [x] `docs/change-log.md`, `docs/tasks.md`, `docs/progress.md`, `docs/test-results.md` updated (AGENTS.md)
- [x] No secrets committed (`app/config/app_local.php` stays ignored)
- [x] Legacy application at the repository root unchanged
- [ ] GitHub Actions green (not pushed)

## Chain context

Start: PR 3a-i tip (the permission-driven RLS builder, `units.manage` seed, resident revoke). End: `sections` and `units` tables under managed RLS. Prior dependency: PR 3a-i. Follow-up: PR 3b-i (Sections and Units tables, entities, policies under layer 1). Out of scope: ORM tables, entities, policies, controllers, the `payer` column, occupancies.

```
feat/units-sdd -> ... -> 3a-i -> [3a-ii (here)] -> 3b-i -> ... -> 5c
```
