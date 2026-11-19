# Tasks: Developments and Units

Change: `developments-units` | Mode: openspec | Strict TDD: active | delivery_strategy: auto-chain | chain_strategy: stacked-to-develop

## Review Workload Forecast

| Item | Value |
|---|---|
| Chained PRs recommended | Yes (14 PRs: 1a-i, 1a-ii, 1b, 2a, 2b, 3a-i, 3a-ii, 3b-i, 3b-ii, 4a, 4b, 5a, 5b, 5c) |
| 400-line budget risk | Medium overall; High for 3a-i, 3a-ii, 4b, 5a, 5c (each sits at or near 400 once reviewer-required items are folded in) |
| Estimated total changed lines | ~4,550 (design estimate ~4,350 plus ~200 for W-A..W-E / S-1..S-4 additions) |
| Decision needed before apply | No. Strategy is resolved (auto-chain, stacked-to-develop). If any PR measures over 400 during apply, apply MUST split it (pre-agreed fallback splits are listed per PR) rather than request size:exception. |

Chain rules (skills `chained-pr`, `work-unit-commits`):
- Each PR targets `develop`, is cut from `develop` AFTER the previous PR merges, and must be independently green (phpunit + phpcs + migration up/down/up where relevant). Never commit on `develop`, `qa`, `main`.
- Every PR description follows `.github/pull_request_template.md`, fills every section ("None" / "No database change" instead of deleting), states what was not verified, and includes a Chain Context block (start, end, prior dependencies, follow-up, out of scope) plus a dependency diagram marking the current PR with a pin marker.
- Commits are work units (behavior + its tests + docs together), Conventional Commits, no AI attribution.
- Strict TDD in every PR: for each behavior write the failing test first (RED), implement (GREEN), then refactor.
- Run `cd app && vendor/bin/phpunit` ALONE, never in parallel with another run (shared test DB). Also `cd app && vendor/bin/phpcs`.
- Docs obligation in EVERY PR (AGENTS.md): update `docs/progress.md`, `docs/tasks.md`, `docs/change-log.md`, and record evidence in `docs/test-results.md`. Not repeated per PR below beyond the "Docs" line.

Dependency order (sequential; no parallel PRs because of the single-writer rule):

    1a-i -> 1a-ii -> 1b -> 2a -> 2b -> 3a-i -> 3a-ii -> 3b-i -> 3b-ii -> 4a -> 4b -> 5a -> 5b -> 5c

Requirement traces use spec names: DS = development-scope, DM = development-management, U = units, P = people, O = occupancies.

---

## PR 1a-i: Scoped behavior, stash and exceptions (S-1 pre-split)

- Branches (split into three stacked PRs): `feat/du-1a-i-1-scope-stack` (1.1, 1.2, stack), `feat/du-1a-i-2-scoped-reads` (1.3 to 1.6), `feat/du-1a-i-3-scoped-writes` (1.7 to 1.14)
- Goal: layer-1 `DevelopmentScopedBehavior` with fail-closed reads, scoped writes, exception-safe rules-phase scope stash (W-A, W-B). No table uses it yet.
- Satisfies: DS "Scoped queries", "Fail closed without scope", "Scope on writes" (AD1, AD2, AD4, AD4b).
- Est. lines: ~330

Tasks (TDD order):
- [x] 1.1 RED: unit tests for exceptions `MissingDevelopmentScopeException` (LogicException) and `DevelopmentScopeViolation` (types, messages carry no row data).
- [x] 1.2 GREEN: create both exception classes under `app/src/Model/` (or existing exceptions namespace).
- [x] 1.3 RED: behavior tests on a test-only scoped table fixture: primary find without `developmentScope` throws; `isAll` adds no filter; empty scope yields `1 = 0` and returns no rows; scope with ids filters `field` (default `development_id`; `field => 'id'` and `through` config variants).
- [x] 1.4 GREEN: implement `DevelopmentScopedBehavior::beforeFind` with config `field`, `through`.
- [x] 1.5 RED: contained-association tests (AD2, revised after security review): a non-primary query always needs an explicit scope; a scoped parent hands its scope only to contained associations whose foreign key links the two development columns; unscoped parents, plain foreign keys, many-to-many, matching/joinWith and through-mode targets (`People`) without explicit scope throw.
- [x] 1.6 GREEN: implement explicit scope propagation to development-linked contains (explicit inner scope narrowed, never widened) and the through-mode guard (composite primary keys rejected).
- [x] 1.7 RED: write-path tests: `beforeMarshal` throws `DevelopmentScopeViolation` when body `development_id` differs from scope target; column not accessible; save option `developmentId` sets it on new entities; dirty `development_id` on update or target outside scope throws; inserting a `developments` row requires `isAll()`.
- [x] 1.8 GREEN: implement `beforeMarshal`, `beforeSave` target checks.
- [x] 1.9 RED (W-A): exception-safety tests: a validation rule throws during `save()`; the NEXT unscoped primary find on the same table MUST throw `MissingDevelopmentScopeException` (stash not leaked). Also: stash is cleared after a successful save, after a failed save, and after `delete()`.
- [x] 1.10 RED (W-B): stash-as-shared-stack tests: (a) an `ExistsIn` rule whose target is a DIFFERENT scoped table (simulating `units.section_id -> Sections`) runs its primary find under the same operation scope and succeeds; (b) nested association saves push/pop independently and restore the outer scope after the inner save; (c) after the outermost operation ends the stack is empty.
- [x] 1.11 GREEN (W-A, W-B): implement a shared static scope stack (registry) used by all scoped tables. Override `save()`/`delete()` in a trait (e.g. `ScopedOperationTrait`, or in the behavior via the table) that reads `developmentScope` from options, pushes it, and pops it in `try/finally`. Primary finds with no option consult the top of the stack only while non-empty. Behavior still handles `Model.beforeRules` requirement check (save without `developmentScope` option throws).
- [x] 1.12 RED/GREEN: map DB `23505`/`23503` raised by races to a field error (AD4b) with a unit test using a stubbed driver exception. Review fix: only the outermost atomic save without associated entities converts, and only outside a transaction or with savepoints; saveMany, nested saves and caller transactions without savepoints rethrow.
- [x] 1.13 REFACTOR: extract scope-resolution helpers; keep phpcs clean.
- [x] 1.14 Docs: progress, tasks, change-log, test-results.

Verification:
- `cd app && vendor/bin/phpunit` (alone); `cd app && vendor/bin/phpcs`.
- No migration.

PR descriptions: `docs/pr/du-1a-i-1-scope-stack.md`, `du-1a-i-2-scoped-reads.md`, `du-1a-i-3-scoped-writes.md`, per `.github/pull_request_template.md`; Database change: "No database change"; state that no production Table uses the behavior yet; not verified: behavior against real RLS (lands with 3a).
Rollback boundary: revert the PR; nothing depends on it yet.

---

## PR 1a-ii: Bulk-operations trait and architecture tests (S-1)

- Branch: `feat/du-1a-ii-scope-bulk-arch` (cut from `develop` after 1a-i merges)
- Goal: close the bulk/raw bypass and make the association whitelist enforceable.
- Satisfies: DS "Scope on writes" (bulk throws), "Scoped queries" (unscoped association scenario), AD2, AD4c.
- Est. lines: ~200

Tasks:
- [x] 2.1 RED (W-C): tests that `updateAll()`, `deleteAll()`, `updateQuery()`, `deleteQuery()` throw `LogicException` on a table using the trait; a dependent `hasMany` cascade that calls `deleteAll` is also refused (documented, expected throw).
- [x] 2.2 GREEN: trait `ScopedBulkOperations` overriding all four; message names the table.
- [x] 2.3 RED (W-C): architecture test: every Table class that adds `DevelopmentScopedBehavior` also uses `ScopedBulkOperations` (reflection over `app/src/Model/Table`); fails on a fixture violation.
- [x] 2.4 RED: architecture test walking every Table's associations: an association may enter a scoped table only from a scoped table; whitelist contains `UsersTable hasMany RoleAssignments` (legal: `RoleAssignments` not scoped, AD3); unlisted entries fail.
- [x] 2.5 RED: architecture test scanning `app/src` for scoped table names in raw SQL / manual `->join()`, excluding `app/config/Migrations` and `App\Database` (AD4c); initially a vacuous pass with a fixture proving it detects a violation.
- [x] 2.6 GREEN: implement the scanners/helpers (test support classes).
- [x] 2.7 REFACTOR; Docs.

Verification: phpunit (alone), phpcs. No migration.
PR description: template; "No database change"; note residual risk of string-built SQL covered only by RLS.
Rollback boundary: revert; trait unused by production tables until 1b.

Implementation note (1a-ii): the trait is named `ScopedBulkOperationsTrait` (coding standard requires the suffix); branch `feat/du-1a-ii-bulk-guards`; task 2.4 reconciled with revised AD2 (static guard: an association entering a scoped table needs a scoped source with a development-column key, or a reasoned whitelist entry; `UsersTable hasMany RoleAssignments` needs none because RoleAssignments is unscoped); `updateQuery()`/`deleteQuery()` let through only the ORM's own single-row `_update`/`_processDelete`. Review round 1: those statements carry the scope's development condition, `insertQuery()` is guarded too, the behavior refuses a changed primary key, and the audits were hardened (alias, overrides, raw-SQL gaps, discovery). Round 2: a zero-row scoped update throws `DevelopmentScopeViolation` (`ScopedUpdateQuery`), and the audit rejects overridden ORM internals.

---

## PR 1b: Scope component and DevelopmentsTable retrofit

- Branch: `feat/du-1b-scope-component`
- Goal: `DevelopmentScopeComponent`, `DevelopmentsTable` retrofit (field `id`), scope option set by policy, `RoleAssignmentsController::managedDevelopment` aligned.
- Satisfies: DS "Retrofit of existing scoped tables", "Cross-development access answers not found", AC-MD-1/2 (partial) (AD3, AD5).
- Est. lines: ~250

Tasks:
- [x] 3.1 RED: component tests: `for(permission)` returns `DevelopmentScope` from `AccessControl::allowedFor` and is memoized per permission; `development(id, permission)` returns the id or throws NotFound (404) for out-of-scope and nonexistent ids identically.
- [x] 3.2 GREEN: `DevelopmentScopeComponent`.
- [x] 3.3 RED: `DevelopmentsTable` tests: user of A with `developments.view` lists only A; super sees all including one created after their account; query without scope throws; empty scope returns none and `canIndex` still yields 403.
- [x] 3.4 GREEN: add behavior (`field => 'id'`) and `ScopedBulkOperations` to `DevelopmentsTable`; `DevelopmentsTablePolicy::scopeIndex` sets the `developmentScope` option.
- [x] 3.5 RED/GREEN: update every existing caller of `DevelopmentsTable` finds (grep via `rg`) to pass a scope; integration test that existing development index still works (AC-MD-2).
- [x] 3.6 RED/GREEN: `RoleAssignmentsController::managedDevelopment` uses the component; existing tests stay green. `RoleAssignmentsTable` explicitly not retrofitted; architecture test (from 1a-ii) green.
- [x] 3.7 REFACTOR; Docs.

Verification: phpunit (alone), phpcs. No migration.
PR description: template; "No database change".
Rollback boundary: revert PR (restores previous DevelopmentsTable queries); 1a-* unaffected.

Implementation note (1b): `DevelopmentScopeComponent::development()` reads the id through the scoped `Developments` table under the permission's scope, so out-of-scope and nonexistent ids (super-administrators included) share one `NotFoundException`. `RoleAssignmentManager::requireExistingDevelopment` now reads through the scoped table with the actor's scope instead of raw SQL, which resolves the 1a-ii raw-SQL audit finding with no whitelist entry. No association enters `developments`, so the association whitelist did not change.

---

## PR 2a: Development profile fields, archive state, policies

- Branch: `feat/du-2a-development-fields`
- Goal: schema/fields, `developments.manage` seed, validation, policy methods.
- Satisfies: DM "Super-administrator only", "Archive instead of delete" (storage), "Profile fields", DS "Permission seeds" (AD6).
- Est. lines: ~300

Tasks:
- [x] 4.1 RED: migration test/RLS test: new columns `address, phone, rfc, bank_name, bank_account text NULL`, `archived_at timestamp NULL` exist; RLS on `developments` unchanged (non-super write still denied); `developments.manage` exists with NO role rows.
- [x] 4.2 GREEN: migration (up + down); `down()` removes only its own code/columns (AD6).
- [x] 4.3 RED: `AccessControlTest::roleMatrix` updated: `developments.manage` held by no role; super allowed.
- [x] 4.4 RED: `DevelopmentsTable` validation tests: five optional fields accepted empty; type, name, labels, timezone required as today.
- [x] 4.5 GREEN: validation + entity accessibility.
- [x] 4.6 RED: `DevelopmentPolicy::canEdit`, `canArchive` allow only super; administrator, data_entry, read-only refused (DM scenarios). Also `canManageUnits/People/Occupancies` skeletons are added in later PRs when used (not here).
- [x] 4.7 GREEN: policy methods.
- [x] 4.8 Migration up/down/up on a clean DB; Docs.

Verification: phpunit (alone), phpcs, migration `bin/cake migrations migrate`, `rollback`, `migrate` (run from `app`).
PR description: template; Database change: describe added nullable columns and permission row; state `down()` verified.
Rollback boundary: roll back migration then revert PR.

---

## PR 2b: Development management UI

- Branch: `feat/du-2b-development-management`
- Goal: add/edit/archive/unarchive screens for super only.
- Satisfies: DM all requirements, AC-MD-1/2, labels part of AC-MD-3 (AD5, W-E).
- Est. lines: ~380 (fallback split: 2b-i add/edit, 2b-ii archive/unarchive)

Notes from review of PR 2a (carry into 2b):
- `timezone` is required on create (validation); the add form must default it to `America/Mexico_City`.
- Save results must be asserted: a `false` or 0-row save (the scoped update throws or returns false) must surface as an error, never as a success flash.
- `bank_account`, `rfc` and `phone` are readable by every role that sees the development at the database level (coarse model). Read screens must select fields explicitly; which roles see the bank fields is a pending product decision.

Tasks:
- [x] 5.1 RED: integration tests: super creates with and without optional fields (AC-MD-2); administrator, data_entry, read-only get refused on add/edit/archive; non-super opening edit of B gets 404; no delete route exists (router assertion).
- [x] 5.2 GREEN: `DevelopmentsController` add/edit, templates (Spanish copy), explicit routes `[0-9]+`.
- [x] 5.3 RED/GREEN: archive and unarchive actions (POST only): archive sets `archived_at`, keeps data; unarchive super only.
- [x] 5.4 RED/GREEN (W-E): `DevelopmentPolicy::canManageUnits/People/Occupancies` return false when archived, INCLUDING for super-administrator (decision: super must unarchive first; policy-level refusal consistent with "only super unarchives"). Tests: archived dev refuses admin, data_entry and super for these abilities; unarchived allows.
- [x] 5.5 RED/GREEN: default-deny walk covers the new actions (DefaultDeny test).
- [x] 5.6 REFACTOR; Docs; add the W-E decision to the change-log.

Verification: phpunit (alone), phpcs.
PR description: template; "No database change"; not verified: archived-write refusal at DB level (RLS layer comes in 3a-i and is coarse by design).
Rollback boundary: revert PR; schema from 2a stays.

---

## PR 3a-i: permission_dev_ids, managed-domain RLS helper, units.manage seed, resident revoke

- Branch: `feat/du-3a-i-permission-dev-ids`
- Goal: layer-2 helper so DB write rights follow the permission catalogue; seed `units.manage`; revoke resident `units.view` (AD13).
- Satisfies: DS "Permission seeds", "Resident data protection", AD6, AD7, AD13, W-E.
- Est. lines: ~380 (fallback split: 3a-i-a function + parity test, 3a-i-b seed/revoke/matrix)

Notes from review of PR 2b (carry into 3a-i):
- `DevelopmentPolicy::canManageUnits/People/Occupancies` fail closed when `archived_at` was not loaded: call sites must pass the full development entity (never one read with a field list).
- Add a real-database test that administrator and data_entry holders of `units.manage` are allowed on an unarchived development and refused on an archived one (the 2b policy tests use a stub for those roles because the permission is only seeded here).

Tasks:
- [x] 6.1 RED: RLS tests for `permission_dev_ids(code)` per actor (super, adminA, dataEntryA, accountantA, resident, adminB, job): active assignment holding the code; `.view` codes include read-only assignments; non-`.view` codes need a writable assignment and a non-archived development; request context only (empty for jobs).
- [x] 6.2 GREEN: migration: `permission_dev_ids(p_code text) bigint[]` definer, STABLE, owner `kualitet_definer`, pinned `search_path`, EXECUTE only to `kualitet_app`; `down()` drops it.
- [x] 6.3 RED: parity test `permission_dev_ids` vs `AccessControl::allowedFor` across role x read_only x window (W-E). Archived dimension is an EXPECTED DIVERGENCE for write codes: `permission_dev_ids` excludes archived developments for non-`.view` codes (RLS), whereas `allowedFor` (layer 1) includes archived developments; the test asserts this divergence explicitly.
- [x] 6.4 GREEN (W-E): document in code comment and `docs/` that layer 1 includes archived developments and refusal comes from `DevelopmentPolicy::canManage*` (incl. super, per 2b) plus RLS.
- [x] 6.5 RED: `Rls::managedDomainTable($table, $code)` tests: validates `^[a-z_]+\.[a-z_]+$` (rejects others); SELECT `is_super OR development_id = ANY(permission_dev_ids(readCode))` (amended 2026-11-19, design A1; read code must end in `.view`, write code must not); INSERT/UPDATE `is_super OR development_id = ANY(permission_dev_ids(code))`; DELETE `is_super` only; column UPDATE grants exclude `id`, `development_id`.
- [x] 6.6 GREEN: `Rls::managedDomainTable`, `Rls::permissionDevIds()` helpers (`app/config/Migrations` support class). Applied signature: `managedDomainTable($table, $readCode, $writeCode, array $updatableColumns, array $insertOnlyColumns = [])` (the column lists are required because column grants must be explicit).
- [x] 6.7 RED: `AccessControlTest::roleMatrix`: `units.manage` for administrator and data_entry only; resident has no `units.view`; resident-only user refused (DS resident requirement).
- [x] 6.8 GREEN: seed migration `units.manage`; revoke migration for resident `units.view` with symmetric `down()` that re-grants only that row; each `down()` removes only its own rows (AD6).
- [x] 6.9 Update `RlsTestCase` with the `dataEntryA` actor; update catalogue seed comment (20261118000001) and ADR-0005 with the AD13 note and the AD8/archived-divergence note.
- [x] 6.10 Migrations up/down/up; Docs.

Verification: phpunit (alone), phpcs, migrate/rollback/migrate.
PR description: template; Database change: new definer function, seed, revoke; security note: resident loses `units.view` (user-confirmed 2026-11-18).
Rollback boundary: rollback the migrations (restores resident `units.view`); revert PR.

---

## PR 3a-ii: sections and units schema + RLS

- Branch: `feat/du-3a-ii-sections-units-schema`
- Goal: tables with constraints and RLS using the 3a-i helper.
- Satisfies: U "Sections", "Unit identity", "Mixed unit types", "Measurements", "Active flag", "Database enforcement"; AD4 trigger.
- Est. lines: ~400 (fallback split: sections / units)

Notes from review of PR 3a-i (binding):
- Table contract for `managedDomainTable`: NOT NULL `development_id`; database-generated primary key (no sequence grant is needed, the ORM reads the id from `RETURNING *`); list `modified` among the updatable columns and `created` among the insert-only ones.
- Cross-table references between managed tables use composite foreign keys `(development_id, x_id)` onto `UNIQUE (development_id, id)`, and unique indexes are scoped per development (for example `UNIQUE (development_id, name)`): foreign-key and unique checks bypass RLS and can leak existence of rows of a development the caller cannot read.
- Write implies read: every role holding `units.manage` must also hold `units.view` (the seed invariant test enforces it).

Tasks:
- [x] 7.1 RED: schema tests for `sections` (name non-blank check, `UNIQUE (development_id, id)`, `UNIQUE (development_id, name)`).
- [x] 7.2 RED: schema tests for `units`: number non-blank; type CHECK; `lot_area_m2 > 0`; `indiviso_pct > 0 AND <= 100`; `is_active` default true; composite FK `(development_id, section_id) -> sections`; `UNIQUE NULLS NOT DISTINCT (development_id, section_id, number)`; same number in different section/development accepted; foreign-development section rejected.
- [x] 7.3 RED: `forbid_development_id_change()` trigger tests (update of `development_id` rejected).
- [x] 7.4 GREEN: migration (up/down) creating both tables, trigger (function reusable by later tables), RLS via `managedDomainTable('sections'|'units', 'units.view', 'units.manage', [updatable columns incl. modified], ['created'])` (design A1: reads follow `units.view`).
- [x] 7.5 RED/GREEN: RLS tests per actor (super, adminA, dataEntryA, accountantA, resident, adminB, job) per command: SELECT scope (follows `units.view` through `permission_dev_ids`: adminA, dataEntryA, accountantA read; resident and guard hold an assignment but read nothing; design A1), INSERT/UPDATE allowed only adminA/dataEntryA/super, DELETE super only (adminA/dataEntryA delete removes no row), cross-development reads/writes see/change nothing, job read-only.
- [x] 7.6 Migration up/down/up; Docs.

Verification: phpunit (alone), phpcs, migrate/rollback/migrate.
PR description: template; Database change: two tables, trigger, policies, grants.
Rollback boundary: rollback migration (drops tables) then revert; no app code uses them yet.

Implementation note (3a-ii): two migrations (`20261122000001_CreateSections`, `20261122000002_CreateUnits`) instead of one, so each table is its own work unit and rollback boundary. The trigger reuses the existing generic `public.forbid_development_id_change()` (from RLS-SEC-001); the new builder `Rls::forbidDevelopmentChange($table)` attaches it and `catalogTable` now uses the builder. `name`/`number` blank checks use `~ '\S'` rather than `btrim(...) <> ''` (the design sketch), because `btrim` keeps a tab or line break. `created` and `modified` are NOT NULL without defaults, as in the existing tables (the ORM sets them). Updatable columns: sections `name, modified`; units `section_id, number, type, lot_area_m2, indiviso_pct, is_active, modified`; insert-only `created`. Task 7.6 evidence is automated: `PermissionMigrationsRollbackTest` rolls both tables, their 8 policies and 2 triggers back and forward.

---

## PR 3b-i: Sections/Units tables, entities, policies

- Branch: `feat/du-3b-i-units-model`
- Goal: ORM layer for sections/units under layer 1.
- Satisfies: U "Unit identity", "Measurements", "Who may read and write" (policy part), DS scope on writes (AD2, AD4, AD5, W-B).
- Est. lines: ~350

Tasks:
- [x] 8.1 RED: `SectionsTable`, `UnitsTable` tests: behavior + `ScopedBulkOperations` used (architecture test from 1a-ii green); validation (blank number, type set, area > 0, indiviso <= 100); rules `IsUnique` and `ExistsIn(section)` run under the save scope (units.section_id -> Sections target table, W-B); foreign-development section rejected; body `development_id` mismatch throws `DevelopmentScopeViolation`.
- [x] 8.2 GREEN: Table + Entity classes, associations (`Units belongsTo Sections` composite; whitelist updated), `development_id` not accessible.
- [x] 8.3 RED: policies `UnitsTablePolicy`, `SectionsTablePolicy`, `UnitPolicy`, `SectionPolicy`: view requires `units.view`; write requires `units.manage` and non-archived development (via `DevelopmentPolicy::canManageUnits`); accountant, supervisor, resident, read-only refused; resident-only refused reads; default deny walk.
- [x] 8.4 GREEN: policies and resolver mapping.
- [x] 8.5 REFACTOR; Docs.

Implementation note (3b-i): `SectionsTable` and `UnitsTable` use `DevelopmentScoped`, `ScopedBulkOperationsTrait` and `ScopedOperationTrait` (the trait that publishes the save scope, so `IsUnique` and `ExistsIn` run under it, W-B). `Units belongsTo Sections` is composite (`(development_id, section_id)` onto `(development_id, id)`, LEFT join) and needs no whitelist entry: it is safe by construction. Cake's `isUnique()` names the first column of the key as the error field, so the rules are added with `IsUnique` directly to put the error on `name` / `number`, and units use `allowMultipleNulls => false` (a NULL section is a value of its own, like `NULLS NOT DISTINCT`). `ExistsIn` is skipped for a NULL section (with `allowNullableNulls` Cake would still query the development's sections). Validation mirrors the database: `name` <= 150 and `number` <= 50 after trim and non-blank (`\S`), type in apartment/house/lot, measurements optional and positive, NaN refused (not numeric), bounds taken after rounding (`lot_area_m2` in [0.005, 9999999999.99], `indiviso_pct` in [0.00005, 100]) so a value that rounds to zero or overflows the column is a field error, not a 500. Policies: `UnitPolicy`/`SectionPolicy` (`canView`, `canAdd`, `canEdit`; no delete) and `UnitsTablePolicy`/`SectionsTablePolicy` (`canIndex`, `canAdd`, `scopeIndex`) share `UnitsAccess`, which reads the development with `archived_at` under the user's `units.manage` scope and asks `DevelopmentPolicy::canManageUnits`. The resolver needs no mapping (`OrmResolver` finds them by name). No route exists yet, so the default-deny walk has nothing new to cover (PR 3b-ii adds the routes and extends it).

Binding notes for PR 3b-ii (from review round 1 of 3b-i): (1) `UnitsTablePolicy::canAdd` / `SectionsTablePolicy::canAdd` are only UI gates. The controller MUST build the entity, set its `development_id` from the route and call `authorize($entity, 'add')` before saving; the table policy never authorizes a write. (2) Writes load and save under the `units.manage` scope (`DevelopmentScopeComponent::for('units.manage')` or `development(id, 'units.manage')`), never the view scope; edit loads the row under the view scope and then authorizes with `canEdit`, and saves under the manage scope. (3) The tables refuse saves into an archived development themselves (`RefusesArchivedDevelopmentTrait`, error on `development_id`); the controller should show that as the archived-development message, not as a field error.

Verification: phpunit (alone), phpcs.
PR description: template; "No database change".
Rollback boundary: revert PR; schema from 3a-ii stays unused.

---

## PR 3b-ii: Units and sections UI

- Branch: `feat/du-3b-ii-units-ui`
- Goal: `UnitsController` (sections inline), templates with development labels, routes, integration tests.
- Satisfies: U all UI scenarios, AC-MD-1/3/4, DS "Every type is covered" for sections and units.
- Est. lines: ~380 (fallback split: sections UI / units UI)

Tasks:
- [x] 9.1 RED: integration tests: list/open/add/edit unit; data_entry writes succeed; accountant/supervisor/resident/read-only refused; foreign unit and foreign section answer 404 identical to nonexistent; mixed types (house, apartment, lot) in one development (AC-MD-4); duplicate number w/o section rejected with field error; zero area and indiviso 120 rejected; deactivate/reactivate unit via (de)activate action keeps the row; archived development refuses writes; creating unit w/o occupancy accepted.
- [x] 9.2 GREEN: `UnitsController` index/view/add/edit/toggle-active, inline section create; scope via `DevelopmentScopeComponent::development(id, 'units.manage'|'units.view')`; explicit nested routes `/developments/{developmentId}/units` with `[0-9]+`.
- [x] 9.3 RED/GREEN: templates use the development's section/number labels (Block/Lot) (AC-MD-3); Spanish UI copy.
- [x] 9.4 RED/GREEN: map DB 23505/23503 to field errors; no delete route for units/sections.
- [x] 9.5 REFACTOR; Docs.

Implementation note (3b-ii): `UnitsController` (index, view, add, edit, activate, deactivate, addSection) with explicit routes under `/developments/{developmentId}/units` (`[0-9]+`); sections are created by `POST .../units/sections` from the list screen and come back as a flash message. Read actions authorize the table (`index`, 403 for a user with no `units.view` anywhere, so a resident gets 403) and resolve the development under `units.view`; write actions authorize the table (`add`, the UI gate) and resolve the development under `units.manage`; a foreign or missing development is the same bare 404 (including a malformed or overflowing id). Rows are always looked up by id AND the development of the URL, under the view scope; edit and (de)activate load there, authorize `canEdit` and save under the manage scope; add and addSection build the entity, set `development_id` from the route, authorize `add` on the entity and only then marshal and save. An archived development answers 403 on every write screen (the entity policy); the table-level refusal (a development archived during the save) is a flash message, not a field error. The body `development_id` is dropped before marshalling, and only `number, type, section_id, lot_area_m2, indiviso_pct` are taken from a unit form (`is_active` changes only through the (de)activate actions, which are POST-only and idempotent with a warning). A foreign or missing `section_id` in a unit form is a field error identical for both (same message), not a 404: the section is never addressed on its own. Field errors are translated to Spanish in the controller (the model keeps English messages). Finding: under row-level security PostgreSQL leaves the DETAIL line out of a unique or foreign key error, so the field of a race (23505/23503) was never known and became a 500; `ConstraintViolation` now keeps the constraint name and `ScopedOperationTrait` looks up its columns in the catalog (task 9.4, covered by a real-database race test per constraint). The list is paginated (100 per page). Review round 1: `Unit.is_active` is not mass-assignable (the activate and deactivate actions set it); field errors carry the development's own labels; the constraint name is read only from the token after the word for the constraint. Not delivered here: the "no payer" flag (arrives in 5c).

Verification: phpunit (alone), phpcs.
PR description: template; "No database change"; not verified: "no payer" flag (arrives in 5c).
Rollback boundary: revert PR (routes/templates only).

---

## PR 4a: People database layer

- Branch: `feat/du-4a-people-db`
- Goal: `person_in_permission_scope`, new `person_create` body, people UPDATE policy, seeds. Seed first (S-4).
- Satisfies: P "Create person through definer function", "Editing people", "Database enforcement", AD8, S-3.
- Est. lines: ~380

Tasks:
- [ ] 10.1 RED: `AccessControlTest::roleMatrix`: `people.view` for administrator, accountant, data_entry, supervisor (not resident); `people.manage` for administrator, data_entry.
- [ ] 10.2 GREEN (S-4): migration seeds `people.view` and `people.manage` FIRST, in the same migration, before replacing the `person_create` body; `down()` deletes only these codes and restores the previous `person_create` and people policy.
- [ ] 10.3 RED: RLS tests for `person_create(development, data)` per actor: adminA and dataEntryA create (person + link atomic); accountant, supervisor, resident, read-only refused and nothing stored; adminA for B refused with no info about B; archived development refused; super allowed; direct INSERT into `people` by `kualitet_app` denied.
- [ ] 10.4 GREEN: `person_create` uses `permission_dev_ids('people.manage')`; owner `kualitet_definer`, pinned `search_path`, EXECUTE only `kualitet_app`.
- [ ] 10.5 RED: `person_in_permission_scope(person_id, code)` tests (S-3): definer function with pinned `search_path`; true only if the person has at least one link AND every linked development is in `permission_dev_ids(code)`; zero links false.
- [ ] 10.6 GREEN: function; people UPDATE policy `is_super OR id = person_id() OR person_in_permission_scope(id, 'people.manage')`.
- [ ] 10.7 RED/GREEN: UPDATE scenarios: admin and data_entry edit person linked only to A; shared A+B person refused for both (S-3: documented that an archived linked development blocks editing a shared person, since the archived dev drops out of `permission_dev_ids`); accountant/supervisor/read-only refused; unlinked editor sees/changes 0 rows.
- [ ] 10.8 Document in ADR-0005 the AD8 deviation, the S-3 archived-linked-development behavior; Migration up/down/up; Docs.

Verification: phpunit (alone), phpcs, migrate/rollback/migrate.
PR description: template; Database change: definer functions, policy, seeds; flag security review of definer functions.
Rollback boundary: rollback migration restores old `person_create` and policy; revert PR.

---

## PR 4b: People model and UI

- Branch: `feat/du-4b-people-ui`
- Goal: `PeopleTable` (through), policies, `PeopleController` index/add/edit.
- Satisfies: P "Person fields", "No existence oracle", "Read scope", "Create ... ORM insert", DS resident protection for people.
- Est. lines: ~400 (fallback split: 4b-i model/policies, 4b-ii controller/templates)

Tasks:
- [ ] 11.1 RED: `PeopleTable` tests: `through => person_developments` scoping; unscoped find throws; `save()` of a new entity throws `LogicException`; `createFor(developmentId, data, scope)` calls `person_create` and returns the person; name required; email/phone/RFC optional; no user or Resident assignment created.
- [ ] 11.2 GREEN: `PeopleTable`, `Person` entity, `createFor()`.
- [ ] 11.3 RED: policies `PeopleTablePolicy`, `PersonPolicy`: view needs `people.view`; resident-only refused including RFC; write needs `people.manage` and `canManagePeople` (archived refused).
- [ ] 11.4 GREEN: policies.
- [ ] 11.5 RED: integration tests: list only in-scope people; foreign person 404; add by adminA and dataEntryA; accountant/supervisor can view, cannot add/edit; shared person edit refused (clear non-oracle message); creating a person whose email exists in B gives an indistinguishable outcome (no hint); missing name rejected.
- [ ] 11.6 GREEN: `PeopleController` index/add/edit, templates (Spanish), nested routes `/developments/{developmentId}/people`; no link-existing-person action (AD10).
- [ ] 11.7 REFACTOR; Docs.
- [ ] 11.8 Carry-over from the PR 1a-i review (S2): people have no write guard in `DevelopmentScopedBehavior` (through-mode); every people write path (`createFor()`, edit) MUST first read the person under a scope, with a test that a write without that read is refused.

Implementation note (3b-i): `SectionsTable` and `UnitsTable` use `DevelopmentScoped`, `ScopedBulkOperationsTrait` and `ScopedOperationTrait` (the trait that publishes the save scope, so `IsUnique` and `ExistsIn` run under it, W-B). `Units belongsTo Sections` is composite (`(development_id, section_id)` onto `(development_id, id)`, LEFT join) and needs no whitelist entry: it is safe by construction. Cake's `isUnique()` names the first column of the key as the error field, so the rules are added with `IsUnique` directly to put the error on `name` / `number`, and units use `allowMultipleNulls => false` (a NULL section is a value of its own, like `NULLS NOT DISTINCT`). `ExistsIn` is skipped for a NULL section (with `allowNullableNulls` Cake would still query the development's sections). Validation mirrors the database: `name` <= 150 and `number` <= 50 after trim and non-blank (`\S`), type in apartment/house/lot, measurements optional and positive, NaN refused (not numeric), bounds taken after rounding (`lot_area_m2` in [0.005, 9999999999.99], `indiviso_pct` in [0.00005, 100]) so a value that rounds to zero or overflows the column is a field error, not a 500. Policies: `UnitPolicy`/`SectionPolicy` (`canView`, `canAdd`, `canEdit`; no delete) and `UnitsTablePolicy`/`SectionsTablePolicy` (`canIndex`, `canAdd`, `scopeIndex`) share `UnitsAccess`, which reads the development with `archived_at` under the user's `units.manage` scope and asks `DevelopmentPolicy::canManageUnits`. The resolver needs no mapping (`OrmResolver` finds them by name). No route exists yet, so the default-deny walk has nothing new to cover (PR 3b-ii adds the routes and extends it).

Binding notes for PR 3b-ii (from review round 1 of 3b-i): (1) `UnitsTablePolicy::canAdd` / `SectionsTablePolicy::canAdd` are only UI gates. The controller MUST build the entity, set its `development_id` from the route and call `authorize($entity, 'add')` before saving; the table policy never authorizes a write. (2) Writes load and save under the `units.manage` scope (`DevelopmentScopeComponent::for('units.manage')` or `development(id, 'units.manage')`), never the view scope; edit loads the row under the view scope and then authorizes with `canEdit`, and saves under the manage scope. (3) The tables refuse saves into an archived development themselves (`RefusesArchivedDevelopmentTrait`, error on `development_id`); the controller should show that as the archived-development message, not as a field error.

Verification: phpunit (alone), phpcs.
PR description: template; "No database change".
Rollback boundary: revert PR; DB layer from 4a stays.

---

## PR 5a: occupancies schema, extension provisioning, occupancies.manage

- Branch: `feat/du-5a-occupancies-schema`
- Goal: occupancies table with exclusion constraints and RLS (W-D).
- Satisfies: O "Occupancy records", "Person linked", "No overlapping", "One primary", "History is kept" (DB), "Database enforcement" (AD9, AD12, AD6).
- Est. lines: ~400 (fallback split: provisioning+seed / table+constraints+RLS)
- Operational note: re-run `php bin/provision_db_roles.php` before the migration (and in CI).

Tasks:
- [ ] 12.1 RED: provisioning test/check: `bin/provision_db_roles.php` creates `btree_gist`; migration throws a clear `RuntimeException` when `pg_extension` lacks it (AD12). Update CI workflow to provision it.
- [ ] 12.2 GREEN: provisioning change + migration guard.
- [ ] 12.3 RED: `roleMatrix`: `occupancies.manage` for administrator and data_entry only.
- [ ] 12.4 RED: schema tests for `occupancies`: `person_id NOT NULL` (W-D); `role` check; `CHECK (ends_on IS NULL OR ends_on > starts_on)`; FK `(development_id, unit_id) -> units`; NO plain FK on `person_id`, only composite `(person_id, development_id) -> person_developments` RESTRICT; nonexistent and foreign person ids fail with the SAME constraint (AD9).
- [ ] 12.5 RED (W-D): exact exclusion tests (SQLSTATE 23P01): `EXCLUDE USING gist (unit_id WITH =, person_id WITH =, role WITH =, daterange(starts_on, ends_on, '[)') WITH &&)` and `EXCLUDE USING gist (unit_id WITH =, role WITH =, daterange(starts_on, ends_on, '[)') WITH &&) WHERE (is_primary)`; overlap rejected; adjacent periods (end exclusive) accepted; co-owner non-primary accepted; primary owner + primary tenant accepted; boundary-day tests.
- [ ] 12.6 GREEN: migration creating table with exactly those constraints, `forbid_development_id_change()` trigger, trigger allowing `ends_on` NULL -> date only, grants `UPDATE (ends_on, modified)` only, RLS: SELECT via `dev_ids()` WITHOUT `person_id = person_id()` clause (D7), INSERT via `permission_dev_ids('occupancies.manage')`, DELETE super only; seed `occupancies.manage`; `down()` removes only its own rows/objects.
- [ ] 12.7 RED/GREEN: RLS tests per actor/command, including data_entry allow and deny, cross-development reads/writes, job read-only, other-field edits refused, ends_on set once.
- [ ] 12.8 Migration up/down/up (provision first); Docs.

Verification: `php bin/provision_db_roles.php`, phpunit (alone), phpcs, migrate/rollback/migrate.
PR description: template; Database change detailed; note provisioning step required.
Rollback boundary: rollback migration (drops table, seed); extension stays (harmless).

---

## PR 5b: payer column, unit_set_payer, payer history

- Branch: `feat/du-5b-payer-history`
- Goal: payer role per unit with unforgeable history (AD11, S-2).
- Satisfies: O "Responsible payer", "Payer change history", "Database enforcement".
- Est. lines: ~350

Tasks:
- [ ] 13.1 RED: schema tests: `units.payer text NOT NULL DEFAULT 'owner' CHECK IN ('owner','tenant')`; runtime INSERT on `units` excludes `payer` via column grants (S-2) and runtime role has no UPDATE on `payer`.
- [ ] 13.2 RED: `unit_payer_changes` tests: columns `development_id, unit_id, payer, changed_at, changed_by_user_id NULL`; FK to units `ON DELETE RESTRICT` (S-2, deleting a unit with history fails); runtime role SELECT only; UPDATE and DELETE rejected by trigger for EVERY role; definer trigger AFTER INSERT and AFTER UPDATE OF payer appends rows; backfill one row per existing unit.
- [ ] 13.3 RED: `unit_set_payer(p_unit_id, p_payer)` tests: allowed for super and `permission_dev_ids('occupancies.manage')` developments; same error for missing and foreign unit; accountant, supervisor, resident, read-only refused; invalid payer rejected; logs acting user; pinned `search_path` verified via `pg_proc.proconfig` (S-2).
- [ ] 13.4 GREEN: migration: column, grants, table, triggers, function (definer, pinned `search_path`, EXECUTE only `kualitet_app`), backfill; `down()` reverses in order.
- [ ] 13.5 RED/GREEN: RLS tests per actor on `unit_payer_changes` (cross-development reads see none, append-only).
- [ ] 13.6 Migration up/down/up; Docs.

Verification: phpunit (alone), phpcs, migrate/rollback/migrate.
PR description: template; Database change detailed; security review requested for definer function.
Rollback boundary: rollback migration drops column/table/function; revert PR.

---

## PR 5c: occupancies model, UI, payer UI and no-payer flag

- Branch: `feat/du-5c-occupancies-ui`
- Goal: finish the change: ORM, finders, controllers, flag.
- Satisfies: O all requirements (app layer), U "Units without a payer", "No resident login lifecycle", AC-MD-1.
- Est. lines: ~400 (fallback split: 5c-i model/finders, 5c-ii controllers/UI)

Tasks:
- [ ] 14.1 RED: `OccupanciesTable` tests: scoped, bulk trait; `findCurrent(on)` uses `daterange(starts_on, ends_on,'[)') @> :on` with `:on` from `BusinessClock`; boundary day excluded; owner + current tenant returned, past tenant not; no `HalfOpenPeriod` class.
- [ ] 14.2 GREEN: `OccupanciesTable`, `Occupancy` entity, `UnitPayerChangesTable` (read only, bulk trait, no save).
- [ ] 14.3 RED/GREEN: `UnitsTable::findWithoutPayer()`: active units with no current primary occupancy of their payer role; responsible person = current primary of payer role.
- [ ] 14.4 RED: policies `OccupanciesTablePolicy`, `OccupancyPolicy`: reads need `units.view` (occupant names under `people.view`); writes need `occupancies.manage` and `canManageOccupancies`; resident-only refused; accountant/supervisor/read-only refused.
- [ ] 14.5 GREEN: policies; map every 23503 on occupancies to one generic message, 23P01 to a field error.
- [ ] 14.6 RED: integration tests: create occupancy (open-ended), different-development person rejected, nonexistent vs foreign person identical message, invalid period, overlap, co-owner, end a tenancy sets `ends_on` only, other edits refused, no delete route, foreign occupancy 404, no user/role assignment created, payer change by data_entry writes history, "no payer" flag on unit list.
- [ ] 14.7 GREEN: `OccupanciesController` (add, end), `UnitsController::payer`, no-payer flag in unit list (Spanish copy), nested routes.
- [ ] 14.8 Docs: close the change, update `docs/release-checklist.md` evidence as required; REFACTOR.

Verification: phpunit (alone), phpcs. Final full-suite run.
PR description: template; "No database change"; what was not verified: resident sub-scope (deferred, D7).
Rollback boundary: revert PR; DB layers (5a, 5b) remain inert.

Implementation note (2a): migration `20261120000001_AddDevelopmentProfileAndManagePermission` adds the five text columns, `archived_at` and the `developments.manage` permission row (no role rows); `down()` removes only those. RLS is untouched (column-agnostic policies and a table-level grant cover the new columns; a new RLS test proves non-super writes of the profile and archive columns are denied). `DevelopmentsTable::validationDefault` requires type (condominium or subdivision), name, section and number labels and a known time zone, and leaves the five profile fields optional; `Development` keeps `id` and `archived_at` out of mass assignment (archiving is its own action in 2b). `DevelopmentPolicy::canEdit` and `canArchive` ask `AccessControl` for `developments.manage` (one mechanism, AD6): only the super-administrator holds it. Migrate, rollback and migrate were run on the development database `kualitet_app` with the `migrations` connection and on a rebuilt test database.
