# Agent Handoff (Coding Agent -> OpenClaw Verifier)

Update this file after EVERY task.

## Latest Task
- Gate: G3 (IN_PROGRESS)
- Task: SEC-001-FOCUSED-RUN - focused Users/Pago characterization tests executed on portable PHP 7.4.33
- Spec Ref: `docs/security-hardening-task-1.md`, `docs/specs/login-payment-characterization.md`, `docs/php74-phpunit-runner.md`
- Summary of changes: One user-authorized production change (`config/routes.php:377` now uses `Router::scope`, behavior-preserving); the wrapper runs each focused file separately; test assertions completed with recorded `Location` values. Focused run: 8 tests, 27 assertions, exit 0. G3 is not PASS: HTTP non-reachability of the two removed `.DS_Store` paths and user approval remain pending.

## Acceptance Criteria Mapping
- RH-001 to RH-004: APPROVED - G2 is PASS; implementation remains gated to G3 and subsequent verification.

## OpenClaw Verification Checklist
### CLI Checks (OpenClaw agent runs in terminal)
- [x] Review G0 planning artifacts for captured mission, scope, journeys, and modes.
- [x] Review G1 feature specifications, acceptance criteria, risks, and traceability.
- [x] Obtain explicit user approval of G1 specs before G2 evaluation.
- [x] Complete category/consumer inventory for public-file exposure in the G2 design baseline.
- [x] G2 approved the isolated datasource boundary design; runtime implementation and test execution remain G3 work.

### Browser/Manual Checks (OpenClaw agent runs in browser tools)
- [x] N/A — G1 is planning only; no web surface was changed.

## Known Risks / Caveats
- G1 and G2 are PASS; G3 remains pending for runtime remediation and the first verified slice.

## If Verification Fails
- Describe the failure clearly and hand back to coding agent with exact repro + logs.

## G2 Design Baseline (2026-11-06)
- Prepared as-is architecture, inventory, dependency map, risk register, characterization strategy, compatibility matrix, migration/rollback strategy, and ADR-0002.
- G2 is design-only. G3 owns test datasource/bootstrap/fixture implementation and test execution.
- G2 is PASS after explicit user approval; G3 owns runtime changes and test execution.

## G2 Public File Exposure Correction (2026-11-06)
- Fresh review confirmed 34 functional files below `webroot/files/**` across Areas, Comunicado, Condominio, Excel, Mensaje, and Ticket. Two `.DS_Store` metadata artifacts (root and `Mensaje/adjunto`) were removed under user authorization; direct consumers include attachments, Excel import/export, logos, and area images.
- This remains an unresolved exposure. FILE-001 must inventory record/category/consumer/checksum ownership, classify protected binaries versus presentation assets, discover database/production candidates, migrate protected files to private storage, change consumers to authorized delivery, verify authorization scenarios, and then deny legacy direct paths. G3 must also verify both removed metadata paths are not statically reachable or restored.
- No application, template, configuration, test, dependency, migration, or data change was made by this correction; the two explicitly authorized metadata-file deletions are recorded separately.


## G3 SEC-001 Update (2026-11-09)
- Gate: G3 (IN_PROGRESS)
- Added a fail-closed test bootstrap requiring `KUALITET_TEST_DATABASE_URL`, rejecting unsafe DSNs, and configuring both Cake datasource names to isolated sqlite memory.
- Added deterministic hashed-user fixtures, login characterization tests, and payment authorization policy tests that do not call `PagoController::index()` or `add()`.
- No production code/configuration, routes, normal data, email, PDF, or spreadsheet work changed.
- Blocker: PHPUnit 6.5 cannot run on the installed PHP runtime (`Cannot acquire reference to $GLOBALS` in `vendor/phpunit/phpunit/src/Util/Configuration.php:543`). Do not mark G3 PASS until the focused tests execute successfully.

- G3 correction (2026-11-10): G3 is IN_PROGRESS. Blockers are the PHPUnit 6.5/PHP incompatibility and outstanding HTTP/static non-reachability checks for both removed .DS_Store paths. SEC-001 policy tests prove only that index()/dd() are not dispatched; collaborator-level instrumentation requires explicit production seams and remains follow-up work.

## G3 PHPUnit Runner Update (2026-11-10)
- Added `tools/run-php74-phpunit.ps1` and `docs/php74-phpunit-runner.md`. The wrapper requires `KUALITET_PHP74`, validates PHP 7.4 plus `intl` and `pdo_sqlite`, exports the isolated SQLite-memory test signal, and runs `--list-suites` before it can run only `UsersControllerTest` and `PagoControllerTest`.
- Local evidence is pending the wrapper's expected fail-closed refusal command. Docker CLI exists but the daemon was unavailable, so no Docker runner and no PHPUnit execution is claimed.
- G3 remains IN_PROGRESS. Next verifier command after provisioning PHP 7.4: `.\tools\run-php74-phpunit.ps1 -Mode Focused`. Record actual output/exit code before recording exact login redirects or marking G3 PASS.

## G3 SEC-001-FOCUSED-RUN (2026-11-16)
- Production change (user-authorized, only one): `config/routes.php:377` `$routes->scope('/', ...)` -> `Router::scope('/', ...)`. The block connects no routes, so `pdf` is not and was not a parsed route extension; behavior is unchanged. Impact analysis is in `docs/change-log.md`.
- `tools/run-php74-phpunit.ps1 -Mode Focused` now runs UsersControllerTest and PagoControllerTest as separate PHPUnit invocations and fails if either fails.
- Recorded from a real run: profile 1 -> `/pages/bienvenido`; profile 6 -> `/usuario/profile`; default profile (2) -> `/pages/bienvenido`. Login page text asserted: the Spanish prompt rendered by the `login` layout.
- Verifier command: `powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\setup-php74-portable.ps1 -Run Focused; echo "EXIT=$LASTEXITCODE"`. Expected: Users `OK (6 tests, 18 assertions)`, Pago `OK (2 tests, 9 assertions)`, `EXIT=0`.
- Do NOT mark G3 PASS. Pending: HTTP verification that the two removed `.DS_Store` paths are not reachable, and user approval.

## Local PostgreSQL migration (2026-11-16, scope change, local machine only)
- Local datasource is now PostgreSQL 18: database and role `kualitet` on `127.0.0.1:5432`, configured only in `config/app_local.php` (previous file: `config/app_local.mysql-backup.php`). `config/app.php` still defaults to MySQL for production.
- Rebuild from the dump: `python -I tools/convert-mysql-dump-to-pgsql.py <dump> config/schema/kualitet-postgresql.sql <data-out>` then load both files with `psql -v ON_ERROR_STOP=1`. The data file contains real records; keep it out of the repository.
- Verified: row counts, keys, sequences, ORM read access for 43 models, focused tests still green. Not verified: application pages over HTTP. Known MySQL-only expressions: `CuotasController.php:342`, `EmailController.php:518`. Models without a table in the dump: Formulario, Pendientes, Posts, UsuarioCondomino.
- G3 SEC-001 state is unchanged by this work.

## LOCAL-PG-002 query fixes (2026-11-16)
- New `src/Database/SqlDialect.php` plus `tests/TestCase/Database/SqlDialectTest.php` (7 tests). Eight controllers use it; on any non-PostgreSQL driver the emitted fragments equal the legacy strings.
- Verified on PostgreSQL: 35 of 47 index pages return 200, 0 SQL errors. Not verified: MySQL execution of the changed controllers, report actions, POST flows, email, exports.
- Database-independent defects recorded as APP-DEFECTS-001 in `docs/tasks.md`; none were changed.

## MD-001 multi-development design (2026-11-17, planning only)
- Draft spec: `docs/specs/multi-development.md` (current-state evidence, draft concepts, eight open business questions, dependencies on ADR-0004, CAKE5-REPORTS-001 and APP-DEFECTS-001).
- Implementation is deferred to the CakePHP 5.x stage. Do not change the CakePHP 3.9 code for this feature.

## MD-001 / RBAC-001 requirements (2026-11-17, planning only)
- `docs/specs/multi-development.md` (RH-005: MD-R1 to MD-R14, AC-MD-1 to AC-MD-12) and `docs/specs/rbac.md` (RH-006: RB-R1 to RB-R11, AC-RB-1 to AC-RB-8) are in REVIEW.
- Six G1 decisions are listed at the end of each requirements section. Do not treat the proposed defaults as approved.
- Target architecture: `docs/target-architecture-description.md` and `.archify/architecture-kualitet-target-20261117-112858/kualitet-target.html`.

## MD-001 / RBAC-001 G1 PASS (2026-11-17)
- RH-005 and RH-006 approved by the user. Specs are APPROVED; acceptance criteria AC-MD-1 to AC-MD-13 and AC-RB-1 to AC-RB-9.
- Next gate: G2 design ADR for this track. Do not start implementation; it belongs to the CakePHP 5.x stage. RBAC baseline depends on the RH-004 inventory.

## MD-001 / RBAC-001 G2 PASS with condition (2026-11-17)
- ADR-0005 revision 4 is Accepted with condition RLS-SEC-001: security review of the row-level security policies, definer functions and triggers, plus the policy test suite passing against a real database, before production.
- Order of work for this track: RH-004 inventory and a complete production dump; framework upgrade (ADR-0004 stages 0 to 3) on the current schema; PostgreSQL production move; remodel stage on 5.x with data migration, RBAC seed and rebuilt reports.
- Do not implement any of this on CakePHP 3.9.

## ADR-0006 rebuild decision (2026-11-17)
- The legacy CakePHP 3.9 application is now a functional reference. Do not port it; build the new CakePHP 5 application from RH-005, RH-006 and ADR-0005.
- Next tasks: REBUILD-000 (location), REBUILD-001 (feature inventory), REBUILD-002 (skeleton), REBUILD-003 (identity and RBAC).

## REBUILD-002 skeleton (2026-11-17)
- New app: `app/` (CakePHP 5.4.4, PHP 8.3 system interpreter, PostgreSQL databases `kualitet_app` and `kualitet_app_test`, role `kualitet_app`; credentials in `app/config/app_local.php`).
- Commands from `app/`: `vendor/bin/phpunit`, `vendor/bin/phpcs`. CI: `.github/workflows/app-ci.yml`.
- Legacy app stays at the repository root with PHP 7.4 (`tools/php74`) only as a functional reference.

## RLS-SEC-001 (2026-11-18)
- Database roles: `kualitet_app` (runtime, `default`/`test`/`auth_side`), `kualitet_owner` (`migrations`/`test_migrations`), `kualitet_definer` (NOLOGIN). Provision with `php bin/provision_db_roles.php --write-local` (superuser via pgpass) before migrating a new database.
- Migrate: `bin/cake migrations migrate -c migrations`. `bin/cake create_user` needs the owner login enabled.
- New scoped tables: use `App\Database\RowLevelSecurity` helpers and add policy tests under `tests/TestCase/Database/Rls`. Residual risks are listed at the end of ADR-0005.
