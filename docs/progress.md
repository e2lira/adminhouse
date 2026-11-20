# Progress

- Overall: 35%
- Current Gate: G3 (IN_PROGRESS - focused Users/Pago tests pass; HTTP non-reachability check and user approval pending)
- Status: IN_PROGRESS

## Gate Status
- G0 Intake: PASS — mission, scope, critical journeys, project/execution mode, and agents captured.
- G1 Planning: PASS — the user approved all four reviewed security specifications and their acceptance criteria.
- G2 Architecture: PASS — the user approved the design-only brownfield architecture baseline and FILE-001 containment plan.
- G3 Slice-1: IN_PROGRESS — SEC-001 isolation and test scaffolding are implemented; PHPUnit execution remains blocked by runtime incompatibility.
- G4 Full Build: PENDING
- G5 Security/Quality: PENDING
- G6 Release Candidate: PENDING
- G7 Handover: PENDING

## Activity Log
- [2026-11-05T23:54:09.031861+00:00] Documentation scaffold initialized (mode=brownfield).
- [2026-11-06] SEC-001 specification drafted and corrected after fresh review; no application code changed.
- [2026-11-06] G2 evaluation for SEC-001 marked BLOCKED pending brownfield architecture-baseline evidence.
- [2026-11-06] G0 PASS: captured gated brownfield security-hardening intake. No application, configuration, test, dependency, or data changes.
- [2026-11-06] Reconciled SEC-001 approval state: user approval is recorded; implementation remains blocked pending G2 PASS.
- [2026-11-06] G1 planning package prepared: specs for isolated characterization, TOTP, private file access, and authorization inventory; no application, configuration, test, dependency, or data changes.
- [2026-11-06] Incorporated approved TOTP backup-code recovery and private-file role/404 contracts; G1 specifications were later user-approved.
- [2026-11-06] G1 PASS: the user approved the corrected feature specifications and acceptance criteria; G2 remains blocked pending brownfield architecture baseline evidence.

- [2026-11-06] Reconciled G1 approval labels across primary specifications and traceability; G2 remains blocked pending brownfield architecture-baseline evidence.

- [2026-11-06] Completed design-only G2 baseline: as-is architecture, inventory, dependencies, risks, characterization plan, compatibility/migration/rollback, ADR-0001, and ADR-0002. No runtime changes or tests executed.
- [2026-11-06] Corrected G2 baseline after review: inventoried direct `webroot/files/**` exposure and documented FILE-001 containment, migration, rollback, and verification. No runtime changes or tests executed.



- [2026-11-08] Added maintainer-facing system overview and module map. An Archify source candidate was generated, but HTML publication failed during the Windows filesystem identity check; no diagram artifact was delivered. Documentation only; no application change.
- [2026-11-08] Completed comprehensive FILE-001 design inventory: 34 functional public files across Areas, Comunicado, Condominio, Excel, Mensaje, and Ticket, plus two `.DS_Store` filesystem-metadata artifacts and all confirmed direct consumers. Documented classification, metadata disposal, database/production discovery, migration, compatibility, rollback, and verification requirements. No runtime changes or tests executed.
- [2026-11-08] User-authorized limited G3 cleanup removed `webroot/files/.DS_Store` and `webroot/files/Mensaje/adjunto/.DS_Store`; filesystem absence was verified. Public functional-file delivery remains unresolved, and web-server non-reachability remains unverified.

- [2026-11-08] G2 PASS: user approved the design-only brownfield architecture baseline and FILE-001 containment plan after final risk review. G3 remains pending; no functional public-file remediation or test execution has occurred.- [2026-11-09] Implemented SEC-001 test-only boundary: `KUALITET_TEST_DATABASE_URL` is required, unsafe database names are rejected, and both Cake datasource names are replaced with isolated sqlite memory. Added deterministic hashed user fixtures, login redirect characterization coverage, and direct payment-policy coverage that does not dispatch payment actions. Focused PHPUnit execution is BLOCKED because PHPUnit 6.5 fatals on the installed PHP runtime; G3 remains IN_PROGRESS and no PASS is claimed.

- [2026-11-09] Corrected SEC-001 contracts: root/login route coverage and payment action-dispatch guards were added. Exact redirects remain deliberately unrecorded until a compatible isolated runner executes them; no G3 PASS is claimed.

- [2026-11-09] G3 SEC-001 redesign: test mode now skips app_local.php and registers only the explicit isolated datasource. Login form assertions and payment no-side-effect guards were added; PHPUnit execution remains blocked by the legacy runner.

- [2026-11-10] G3 evidence correction: test mode is isolated, but focused execution remains blocked by PHPUnit 6.5/PHP incompatibility and static HTTP non-reachability for the removed .DS_Store paths is still outstanding.

- [2026-11-10] Added `tools/run-php74-phpunit.ps1`, a preflighted wrapper for locked PHPUnit 6.5.14 under a caller-supplied PHP 7.4 executable. Docker daemon access was unavailable, so no container runner or PHPUnit execution is claimed. The wrapper lists suites before it can run only Users/Pago controller tests; G3 remains IN_PROGRESS.

- [2026-11-16] User chose a portable PHP 7.4 as the G3 runner (goal: migrate to a newer CakePHP). Added `tools/setup-php74-portable.ps1`; it is not yet executed, so focused PHPUnit execution is still pending and G3 remains IN_PROGRESS.

- [2026-11-16] Portable PHP 7.4.33 provisioned under `tools/php74`; smoke check passed (PHPUnit 6.5.14 lists suite `app`). The PHPUnit/PHP incompatibility blocker is removed. Focused Users/Pago execution and the HTTP non-reachability check are still pending; G3 remains IN_PROGRESS.

- [2026-11-16] First focused run executed on PHP 7.4.33 and FAILED (6 errors, 0 assertions): `config/routes.php:377` uses an undefined file-scope `$routes`, and the wrapper did not execute PagoControllerTest. G3 remains IN_PROGRESS; no redirect values recorded.

- [2026-11-16] SEC-001-FOCUSED-RUN done: `config/routes.php:377` now uses `Router::scope` (user-authorized production change, behavior-preserving), the wrapper runs Users and Pago separately, and the focused run passes on PHP 7.4.33 (8 tests, 27 assertions, exit 0). Exact login `Location` values recorded and asserted. G3 remains IN_PROGRESS: HTTP non-reachability of the two removed `.DS_Store` paths and user approval are still pending.

- [2026-11-16] Scope change (user-requested, local machine only): local database moved to PostgreSQL 18. Database `kualitet` loaded from the 2026-10-29 dump (45 tables, 340 rows, verified); `config/app_local.php` now uses the Postgres driver; ORM smoke passes for 43 models (4 models point to tables that the dump never had). Application pages are NOT yet verified on PostgreSQL and two MySQL-only expressions are known. Production stays on MySQL. G3 status unchanged (IN_PROGRESS).

- [2026-11-16] PostgreSQL page sweep (GET-only, index actions): 30 of 47 pages render; 11 fail on a database-independent `isAuthorized()` gap; 6 fail on PostgreSQL incompatibilities (varchar/integer comparison, `SUM` over varchar, `CAST ... AS UNSIGNED`, date arithmetic on text, strict `GROUP BY`). Fix approach pending user decision (LOCAL-PG-002).

- [2026-11-16] LOCAL-PG-002: the six PostgreSQL incompatibilities found on index pages are fixed through `src/Database/SqlDialect.php` (MySQL SQL unchanged by design, unit-tested). Sweep now shows 35 of 47 index pages rendering and no SQL errors; the remaining 12 failures are database-independent. Reports, POST flows, email and exports are not yet verified on PostgreSQL. G3 status unchanged.

- [2026-11-16] Message list now orders by `Mensaje.fecha DESC` on all engines (user-authorized production behavior change; removed from APP-DEFECTS-001).

- [2026-11-16] Conceptos list page fixed for empty lists (user-authorized, test-first). APP-DEFECTS-001 still open for: 11 controllers without `isAuthorized()`, four models without tables, `EmailController.php:518`.

- [2026-11-17] LOCAL-PG-003 sweep: 106 read-only actions exercised on PostgreSQL with no writes. Remaining PostgreSQL failures are concentrated in reports (loose `GROUP BY`, about 61 queries). The resident profile page appears broken on any engine (APP-DEFECTS-002). Decision pending on the report fix approach.

- [2026-11-17] LOCAL-PG-003: reports now run on PostgreSQL through a central loose-GROUP-BY relaxer (no-op on MySQL) and a local text-sum aggregate. Read paths show no PostgreSQL-caused SQL errors. Remaining failures are engine-independent defects (APP-DEFECTS-001/002). Report rewrite scheduled for the CakePHP 5 migration (CAKE5-REPORTS-001).

- [2026-11-17] Report defects fixed (user-authorized): informe PDF ambiguous column and monthly close period/condominium/join. Verified on PostgreSQL with synthetic data. The resident profile defect was misdiagnosed earlier; the corrected cause is recorded and no change was made.

- [2026-11-17] Resident profile now shows a clear message for accounts without an assigned unit (user-authorized, test-first).

- [2026-11-17] APP-DEFECTS-002 closed (user-authorized, test-first where SQLite allows). Remaining known defects are tracked in APP-DEFECTS-001 and the APP-DEFECTS-002 closure note.

- [2026-11-17] APP-DEFECTS-003 done: dead profile stubs are no longer routable and the receipt batch report lists one receipt per payment of the batch month. Open: APP-DEFECTS-001 (authorization policy, models without tables, `EmailController.php:518`).

- [2026-11-17] `EmailController.php:518` fixed (ported the Reportes `saldo` definition; user-authorized). The email informe action remains unreachable until `EmailController` gets an authorization policy and a template.

- [2026-11-17] MD-001 started (scope change): multi-development design for condominiums and subdivisions, planning only, in parallel with the CakePHP migration. Draft spec written; business questions in progress.

- [2026-11-17] RBAC-001 started (scope change, planning only): move from fixed profiles to role-based access control, specified separately as RH-004 requires, linked to MD-001.

- [2026-11-17] User decision: production database will be PostgreSQL (planning task PG-PROD-001). Target architecture description in progress (`docs/target-architecture-description.md`).

- [2026-11-17] User decision: online payments through a gateway (planning task PAY-001; provider not chosen).

- [2026-11-17] Business question rounds for MD-001 and RBAC-001 complete; answers recorded in `docs/specs/multi-development.md` and `docs/specs/rbac.md`. Target architecture diagram updated. Next: requirements RH-005 and RH-006 for G1 review.

- [2026-11-17] Requirements RH-005 and RH-006 drafted (REVIEW, not approved). Six proposals await the user's decision at G1 review; debt restrictions deferred pending legal validation.

- [2026-11-17] All six G1 decisions for RH-005 and RH-006 recorded; G1 approval of both requirement sets is pending.

- [2026-11-17] G1 PASS for RH-005 and RH-006 (user approval). Next for this track: G2 design ADR. The security track remains at G3 IN_PROGRESS.

- [2026-11-17] G2 started for the MD-001 / RBAC-001 track: ADR-0005 drafted (Proposed). Three design decisions pending with the user.

- [2026-11-17] ADR-0005: all three G2 decisions confirmed (two isolation layers, English schema names, framework first). G2 approval pending.

- [2026-11-17] ADR-0005 revised after an independent review (revision 2). G2 approval pending. A complete production dump is needed before any migration rehearsal.

- [2026-11-17] ADR-0005 revision 3 after a second independent review. One new G2 decision (access for former residents) is pending, then G2 approval.

- [2026-11-17] ADR-0005 revision 4 after a third independent review. G2 approval pending; row-level security policies remain the highest-risk part and are covered by a required policy test suite at implementation.

- [2026-11-17] G2 PASS with condition for MD-001 / RBAC-001 (ADR-0005 Accepted with condition; RLS-SEC-001). Prerequisites before implementation: RH-004 inventory and a complete production dump. Security track unchanged at G3 IN_PROGRESS.

- [2026-11-17] RH-004 authorization inventory completed (read-only). Key risk: the guard visit page `/usuario/vigilante/{id}` is reachable without a session (AZ-1). Inventory awaiting user review.

- [2026-11-17] AZ-1 fixed (user-authorized): the guard visit page now requires a staff sign-in. AZ-2 (no condominium check on URL ids) remains open.

- [2026-11-17] AZ-2 interim fix (user-authorized): condominium ids in the URL must belong to the signed-in user unless Superuser. Test fixture drift recorded as TEST-FIXTURES-001.

- [2026-11-17] Test fixtures aligned with the production schema (17 rewritten); full suite errors 4 -> 2. Remaining failures are unrelated to fixtures (TEST-SUITE-001).

- [2026-11-17] Full test suite green on SQLite memory: 363 tests, 0 errors, 0 failures, 299 incomplete placeholders.

- [2026-11-17] SMTP passwords moved to `config/app_local.php` (not versioned). Local repository ready for a first commit; nothing committed or pushed. SMTP password rotation recommended.

- [2026-11-17] No active production server (user). Repository published to GitHub by the user (`d5613b6`). Production-related tasks re-scoped.

- [2026-11-17] ADR-0006 accepted: rebuild on CakePHP 5; ADR-0004 superseded. Next: decide the location of the new application (REBUILD-000), then the feature inventory (REBUILD-001).

- [2026-11-17] Feature inventory drafted (REBUILD-001): 367 legacy actions classified; 46 need a user decision.

- [2026-11-17] Feature inventory complete: 367 legacy actions, 0 open decisions. Next: REBUILD-002 (CakePHP 5 skeleton in `app/`).

- [2026-11-17] REBUILD-002 done: CakePHP 5.4.4 skeleton in `app/` on PostgreSQL 18, tests and coding standard green. Next: REBUILD-003 (identity, TOTP and RBAC with development scoping), test-first.

- [2026-11-17] REBUILD-003 slice 1 verified: identity schema, role catalogue and password sign-in in `app/` (41 tests green). Next: slice 2 (permissions, development scoping, default deny).

- [2026-11-18] REBUILD-003 slice 1 committed (`bd62a64`); slice 2 verified (82 tests green): permissions, development scope, default deny, per-request user reload. Next: confirm the role matrix, commit, then slice 3 (TOTP).

- [2026-11-18] REBUILD-003 slice 3 verified and committed locally: TOTP second factor for super-administrators (150 tests green). Next: slice 4 (role-assignment management screen).

- [2026-11-18] REBUILD-003 slice 4 verified and committed locally: role-assignment management screen (223 tests green). Identity and RBAC slices 1 to 4 complete. Pending follow-ups: F1, F3, F4, F5, F7; RLS-SEC-001 before first deployment.

- [2026-11-18] RLS-SEC-001 done and committed locally: PostgreSQL row-level security with three roles, definer functions, dedicated security review (407 tests green). Migrations now run with `bin/cake migrations migrate -c migrations`; new databases need `bin/provision_db_roles.php` first.

- [2026-11-18] PR #1 merged to `main`. CI was red because of the TOTP key placeholder in CI; fixed on `fix/ci-totp-test-key` (targets `develop`). New promotion flow: work branches -> `develop` -> `qa` -> `main`.

- [2026-11-18] developments-units PR 1a-i implemented and split into three stacked PRs (`feat/du-1a-i-1-scope-stack`, `feat/du-1a-i-2-scoped-reads`, `feat/du-1a-i-3-scoped-writes`, on `feat/units-sdd`): `DevelopmentScopedBehavior`, `DevelopmentScopeStack`, `ScopedOperationTrait`, scope exceptions. A fresh security review found one critical issue (implicit scope inheritance through the target's composite belongsTo) and several warnings; all fixed with tests in the PR that introduced the code (474 tests green at the tip); a second review round (approve with fixes) was applied too. Next: PR 1a-ii (bulk-operations trait, W-C, and architecture tests).

- [2026-11-18] developments-units PR 1a-ii implemented on `feat/du-1a-ii-bulk-guards` (stacked on `feat/du-1a-i-3-scoped-writes`, not pushed): `ScopedBulkOperationsTrait` and three static guards (bulk trait on every scoped table, associations entering scoped tables, raw SQL or manual joins naming scoped tables) with fixtures. Heads-up for PR 1b: once `DevelopmentsTable` is scoped, the raw-SQL scan will flag `SELECT 1 FROM developments` in `src/Authorization/RoleAssignmentManager.php` (existence check); 1b must replace it or add an explicit, reasoned exception. Next: PR 1b (scope component and `DevelopmentsTable` retrofit).

- [2026-11-18] developments-units PR 1a-ii review round 1 applied (3 more commits on `feat/du-1a-ii-bulk-guards`): statement-level scope condition and primary-key-change guard, insertQuery guard, hardened audits. Next: second review, then PR 1b.

- [2026-11-18] developments-units PR 1a-ii review round 2 applied (zero-row scoped update now fails visibly, missing-scope exception, audit of ORM internals). Next: PR 1b.

- [2026-11-18] developments-units PR 1b implemented on `feat/du-1b-scope-component` (cut from `feat/du-1a-ii-bulk-guards`, not pushed): `DevelopmentScopeComponent` (`for(permission)` memoized, `development(id, permission)` answering one 404), `DevelopmentsTable` scoped by its own id with the bulk trait, `DevelopmentsTablePolicy::scopeIndex` now sets the `developmentScope` option, `RoleAssignmentsController::managedDevelopment` uses the component, and the raw `SELECT 1 FROM developments` of `RoleAssignmentManager` now reads through the scoped table under the actor's scope. Next: second review, then PR 2a (development profile fields, archive state, policies).

- [2026-11-18] developments-units PR 1b review round 1 applied (3 commits on `feat/du-1b-scope-component`): component memo keyed by identity and permission (the no-identity case is never remembered), id guard, tests that no longer rely on row-level security, single authorization path in `managedDevelopment`, `DevelopmentsController` reuses the component scope. Next: second review, then PR 2a.

- [2026-11-18] developments-units PR 2a implemented on `feat/du-2a-development-fields` (cut from `feat/du-1b-scope-component`, not pushed): migration adding the five optional profile columns, `archived_at` and the `developments.manage` permission (no role rows), validation for developments, `DevelopmentPolicy::canEdit` and `canArchive` (super-administrator only). Next: PR 2b (development management UI).

- [2026-11-18] developments-units PR 2a review round 1 applied on `feat/du-2a-development-fields` (no critical findings): optional profile fields trimmed and stored as NULL when empty, required text trimmed, explicit allow-list of the ten editable fields, length limits, and tests for super-administrator assignment dates, column writes under row-level security and schema checks. Next: second review, then PR 2b.

- [2026-11-18] developments-units PR 2b implemented on `feat/du-2b-development-management` (cut from `feat/du-2a-development-fields`, not pushed): add, edit, archive and unarchive screens for developments (super-administrator only, Spanish copy), explicit routes with `[0-9]+` ids and no delete route, list marks archived developments and selects its fields explicitly (no bank, RFC or phone), `DevelopmentPolicy::canManageUnits/People/Occupancies` refuse archived developments including for the super-administrator (W-E), default-deny route walk covers the new actions. Next: review, then PR 3a-i.

- [2026-11-19] developments-units PR 2b review round 1 applied on `feat/du-2b-development-management` (no critical findings): archive and restore read the row `FOR UPDATE` and a no-op shows a warning, W-E policy fail-closed for hidden fields with named permission codes, exact-status route walk for the super-administrator and an administrator, edit, id, super-administrator-variant and non-scalar tests. Next: second review, then PR 3a-i.

- [2026-11-19] developments-units PR 3a-i implemented on `feat/du-3a-i-permission-dev-ids` (cut from PR 2b's tip `6184e61`, not pushed): `permission_dev_ids(code)` definer function (request context only, active assignment whose role holds the code, writable assignment and unarchived development for non-`.view` codes), `RowLevelSecurity::managedDomainTable` and `permissionDevIds` builders, `units.manage` seeded for administrator and data_entry, resident `units.view` revoked (AD13), `dataEntryA` actor, parity test against `AccessControl::allowedFor`. Next: review, then PR 3a-ii (sections and units schema).

- [2026-11-19] developments-units PR 3a-i review round 1 applied on `feat/du-3a-i-permission-dev-ids` (no critical findings) plus the user decision "Si, por permiso": `Rls::managedDomainTable` now takes a read code (must end in `.view`) and a write code (must not), so managed-table reads follow the permission at the database layer (a resident or guard with an assignment reads nothing, a job reads nothing); INSERT is granted on an explicit column list; wider parity matrix over every catalogue code, archived and user-status dimensions; business-day clock pinned with far time zones and fixture dates from `business_today()`; policy test with full scoped reads; migration rollback test; `identifier()` end-of-string flag. Design amendment A1, ADR-0005 notes and PR description updated. Next: second review, then PR 3a-ii.

- [2026-11-19] developments-units PR 3a-i review round 2 applied on `feat/du-3a-i-permission-dev-ids` (no critical findings): write-implies-read pinned (a write-only role gets a bare INSERT, a refused RETURNING and a zero-row UPDATE) and kept by a seed invariant test; denied writes are read back as the owner; new ids under the runtime role checked (the CakePHP PostgreSQL driver uses `RETURNING *`, so no sequence grant is needed); table contract notes for PR 3a-ii (composite foreign keys, per-development unique indexes); rollback test in its own group with the original failure preserved. Next: PR 3a-ii.

- [2026-11-19] developments-units PR 3a-ii (tasks 7.1 to 7.6) implemented on `feat/du-3a-ii-sections-units-schema` (cut from the 3a-i tip 4ae4008, unpushed): migrations `20261122000001_CreateSections` and `20261122000002_CreateUnits` create `sections` and `units` with the 3a-i contract (NOT NULL `development_id`, identity key, composite foreign key `(development_id, section_id)` onto `sections (development_id, id)`, per-development unique keys, `UNIQUE NULLS NOT DISTINCT (development_id, section_id, number)`, measurement and type checks, `is_active`), a development-change trigger on both (new builder `Rls::forbidDevelopmentChange`, reusing the generic `forbid_development_id_change()`), and RLS through `Rls::managedDomainTable(..., 'units.view', 'units.manage', ...)`. Schema, trigger, per-actor RLS, existence-probe and rollback tests added. Next: PR 3b-i (Sections/Units tables, entities, policies).

- [2026-11-19] developments-units PR 3a-ii review round 1 applied on `feat/du-3a-ii-sections-units-schema` (no critical findings): `lot_area_m2` and `indiviso_pct` reject NaN (PostgreSQL ranks NaN above every number, so `> 0` accepted it); `sections.name` is bounded to 150 characters and `units.number` to 50 (PR 3b-i validation must mirror both); tests for NOT NULL on `name` and `number`, job and anonymous writes, privileges on every column of both tables, an update of every updatable column as administrator and data_entry, a supervisor in the read matrix and the non-writers, SQLSTATE 42501 checks on denied inserts; `units` and `sections` listed in `TEST_DATA_TABLES`. Known limits added to the PR description (client-set created/modified are not an audit trail; down migrations are dev and test only). Next: PR 3b-i.

- [2026-11-19] developments-units PR 3b-i (tasks 8.1 to 8.5) implemented on `feat/du-3b-i-units-model` (cut from the 3a-ii tip f3a5426, unpushed): `SectionsTable` and `UnitsTable` (DevelopmentScoped, ScopedBulkOperationsTrait, ScopedOperationTrait) with entities `Section` and `Unit` (only the editable fields are assignable; the development and key never are), `Units belongsTo Sections` on the composite key (LEFT join, safe by construction, no whitelist entry), validation that mirrors the database (name <= 150, number <= 50, non-blank after trim, type set, optional positive measurements, NaN refused, bounds checked after rounding so a value that rounds to zero or overflows the column is a field error), rules `IsUnique` (a NULL section is a value of its own) and `ExistsIn(section)` running under the save scope, and policies `UnitPolicy`, `SectionPolicy` (view, add, edit; no delete) and `UnitsTablePolicy`, `SectionsTablePolicy` (index, add, scopeIndex) over a shared `UnitsAccess` that reads the development with `archived_at` and asks `DevelopmentPolicy::canManageUnits`. No database change, no route (UI is PR 3b-ii). Full suite `OK (1342 tests, 7660 assertions)`, phpcs clean. Not pushed.

- [2026-11-19] developments-units PR 3b-i review round 1 applied on `feat/du-3b-i-units-model` (no critical findings): `RefusesArchivedDevelopmentTrait` makes `SectionsTable` and `UnitsTable` refuse a save (create or update) into an archived development themselves, in `beforeSave` inside the save transaction and even with `checkRules` off, reading the development through the scoped `DevelopmentsTable` with `FOR UPDATE` (the lock needs the UPDATE policy of `developments`, so only the super-administrator gets it; for anyone else the read is plain and row-level security already refuses the write atomically because `permission_dev_ids` excludes archived developments), an error on `development_id` and fail closed when the development is not found under the save scope; entity policies judge a stored row by `getOriginal('development_id')`; `UnitsAccess` accepts a route string or an int and fails closed on any other shape (no TypeError); `canDelete` is an explicit refusal; `section_id` must be at least 1; the table-level `canAdd` is documented as a UI gate only and binding notes for PR 3b-ii were added to openspec tasks.md; tests prove the validation bounds on the real database, multibyte lengths, a matching or mismatched body development on `newEntity` and `patchEntity`, malformed section ids and active flags, a unit moved to a foreign section, combined roles (resident plus data_entry), the scope list against real rows per table, and four hand mutations. Full suite `OK (1403 tests, 7743 assertions)`, phpcs clean.

- [2026-11-19] developments-units PR 3b-ii implemented on `feat/du-3b-ii-units-ui` (cut from `c7e70bf`, the 3b-i tip; not pushed): `UnitsController` with list (paginated), open, add, edit, deactivate and reactivate, and inline section creation, at `/developments/{id}/units` (explicit routes, `[0-9]+`, no delete route). Reads resolve the development under `units.view`, writes under `units.manage` (foreign, missing and malformed ids give the same bare 404; a user with no `units.view` anywhere gets 403); the entity is authorized with the development of the URL before it is saved and saved under the manage scope; archived developments refuse writes (403, and a flash message when the archive lands during the save); screens use the development's section and number labels; field errors are in Spanish. Finding fixed on the way: under row-level security PostgreSQL omits the DETAIL line of a unique or foreign key error, so the save-scope trait could not name the field of a race and it became a 500; `ConstraintViolation` now keeps the constraint name and the trait reads its columns from the catalog. The default-deny walk covers every new route for the super-administrator, a data_entry, an accountant, a resident, an administrator of another development and a user with no assignment. Full suite `OK (1499 tests, 8257 assertions)`, phpcs clean on touched files. Hand mutations on the authorization paths seen red and restored (see test-results). The "no payer" flag arrives in 5c.

- [2026-11-19] developments-units PR 3b-ii review round 1 applied on `feat/du-3b-ii-units-ui` (no criticals): the `Unit` entity no longer accepts `is_active` from request data (only the activate and deactivate actions set it); `ConstraintViolation` takes the constraint name only from the token after the word for the constraint (English `constraint "x"`, Spanish `restriccion de unicidad` and `llave foranea`), so the referenced side of a foreign key does not name a table; tests pin the catalog lookup (real name, another table's constraint, unknown name, failing lookup, no name) and the rethrow inside a caller transaction without savepoints is documented as unreachable for the lookup; field errors use the development's own labels; the unit-screen tests compare status, body and headers, prove for a user of both developments that a foreign section is refused by the rule and not by the database, cover reactivation refusals, the largest accepted and the above-maximum measurements, overflowing ids, section and archive races on edit and deactivate, the sections unique race, pagination page size and order, and the default-deny walk now also sends every unit route the methods it does not accept. Full suite `OK (1529 tests, 8497 assertions)`, phpcs clean on touched files. The PR will carry `size:exception`.

- [2026-11-19] developments-units PR 4a (people database layer, tasks 10.1 to 10.8) on `feat/du-4a-people-db`, cut from the 3b-ii tip `20b85bb` (stacked, unpushed). Migration `20261123000001_PeoplePermissionScope` seeds `people.view` (administrator, accountant, data_entry, supervisor; not resident) and `people.manage` (administrator, data_entry) first, adds the definer function `person_in_permission_scope(person, code)` (true only when the person has a link and EVERY linked development is in `permission_dev_ids(code)`), replaces the `person_create` body to check `permission_dev_ids('people.manage')` (same error for a foreign, a missing and a NULL development) and moves the people UPDATE policy to `is_super OR own row OR person_in_permission_scope(id, 'people.manage')`. `down()` restores the previous body and policy and deletes only its own codes. Tests first: role matrix and write-implies-read pair in `AccessControlTest`, `RlsPeopleTest` (42 tests: create per actor, no oracle, archived, atomic, scope function, privileges, UPDATE per actor incl. shared and archived-linked person), rollback test extended. Full suite `OK (1579 tests, 9631 assertions)`, phpcs clean on touched files. The people SELECT policy is unchanged and still follows `dev_ids()`: open decision for the user (see the PR description).

- [2026-11-19] developments-units PR 4a review round 1 (no criticals; reliability test gaps and two risk suggestions) on `feat/du-4a-people-db`: the rollback test now compares the exact function definition, owner, config and privileges of `person_create`, the absence of `person_in_permission_scope`, and USING and WITH CHECK of `people_update` before and after `down()` and after the second `up()`; the people RLS tests prove atomicity with a real mid-function failure (super-administrator, nonexistent development, 23503), assert the actor can read the row before every denied UPDATE (invisible actors moved to their own test), assert a 0-row result and the 42501 state and refusal message, and cover the super-administrator (person without links, person linked to an archived development, creation in an archived development); `person_in_permission_scope` evaluates `permission_dev_ids` once per call. ADR-0005 notes 8 (people.view has no database effect yet) and 9 (open question on own-row and staff edits of contact data). No change to the people SELECT policy (waiting for the user's decision). Full suite `OK (1584 tests, 9688 assertions)`, phpcs clean on touched files.
