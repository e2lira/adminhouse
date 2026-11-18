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
