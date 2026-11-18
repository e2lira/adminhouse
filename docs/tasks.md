# Tasks

| ID | Task | Status | Gate | Owner | Updated |
|---|---|---|---|---|---|
| G0-001 | Capture mission, scope, critical journeys, mode, agents, and initial acceptance targets. | DONE | G0 | Codex / Claude | 2026-11-06 |
| G1-001 | Create review-ready feature specs, acceptance evidence, traceability, risks, and assumptions for RH-001 through RH-004. | DONE — APPROVED | G1 | Codex / Claude | 2026-11-06 |
| G2-001 | Document brownfield architecture baseline, risks, compatibility, migration/rollback, and design-only G2 ADRs. | DONE — APPROVED | G2 | Codex / Claude | 2026-11-08 |
| G2-002 | Complete the FILE-001 public-file category/consumer inventory and containment, classification, migration, compatibility, and rollback design. | DONE — APPROVED | G2 | Codex / Claude | 2026-11-08 |
| G3-001 | Remove the two public `.DS_Store` metadata artifacts and record filesystem absence; retain web-server non-reachability verification as remaining G3 evidence. | DONE — LIMITED USER-AUTHORIZED CLEANUP | G3 | Codex / Claude | 2026-11-08 |
| SEC-001 | Establish a fail-closed datasource and characterize login/payment authorization. Spec: `docs/security-hardening-task-1.md`. | IN_PROGRESS - focused run passes; G3 evidence pending | G3 | Codex / Claude | 2026-11-09 |
| CR-2026-11-06 | Assess and deliver gated brownfield security hardening. | READY_FOR_G3 | G3+ | Codex / Claude | 2026-11-08 |

## SEC-001 Dependencies

- [x] G1 approved requirements and the SEC-001 specification.
- [x] G2 approved the design-only datasource boundary, lifecycle, and side-effect guard architecture.
- [x] The user approved `docs/security-hardening-task-1.md`; implementation remains gated on G2 PASS.
- [x] G2 is `PASS`; implementation remains scheduled for G3.

## G1 Approval Dependencies

- [x] Review-ready specs exist for RH-001 through RH-004.
- [x] Testable acceptance criteria and traceability are documented.
- [x] Risks, assumptions, constraints, and non-goals are documented.
- [x] TOTP backup-code recovery and private-file authorization/404 contracts are documented.
- [x] User approved the G1 feature specifications and acceptance criteria.



| CR-2026-11-06T23:41:51.516903+00:00 | Assess and implement change: Design-only G2 baseline for security hardening: characterize login and payment authorization before TOTP, private-file access, and roles inventory changes. | READY_FOR_G3 | G3+ | orchestrator | 2026-11-08 |

## G3 SEC-001 Update (2026-11-09)

| ID | Status | Evidence |
|---|---|---|
| SEC-001 | IN_PROGRESS â€” test boundary implemented; focused runner blocked | `tests/TestDatabaseBootstrap.php`; PHPUnit 6.5/PHP runtime incompatibility |

| SEC-001-G3-EVIDENCE | Record remaining G3 blockers: compatible PHPUnit runner plus HTTP/static non-reachability checks for the removed metadata paths. | IN_PROGRESS | G3 | Codex / Claude | 2026-11-10 |

| SEC-001-PHP74-RUNNER | Provide a PHP 7.4-compatible runner for the locked PHPUnit 6.5.14 and bounded focused execution. | IN_PROGRESS â€” wrapper ready; compatible PHP execution pending | G3 | Codex | 2026-11-10 |

| SEC-001-FOCUSED-RUN | Fix file-scope `$routes` in `config/routes.php` (authorized production change), run Users and Pago focused tests separately, record exact login `Location` values and complete the pending assertions. | DONE - 8 tests, 27 assertions, exit 0 on PHP 7.4.33 | G3 | Claude | 2026-11-16 |

| SEC-001-PHP74-RUNNER | Provide a PHP 7.4-compatible runner for the locked PHPUnit 6.5.14 and bounded focused execution. | DONE - portable PHP 7.4.33 runner executes the focused files | G3 | Codex / Claude | 2026-11-16 |

| SEC-001-G3-EVIDENCE | Remaining G3 blockers: HTTP/static non-reachability checks for the two removed `.DS_Store` paths, then user approval. | IN_PROGRESS | G3 | Codex / Claude | 2026-11-16 |

| LOCAL-PG-001 | Migrate the local development database from MariaDB to PostgreSQL 18 (schema, data, local config, driver). Local machine only. | DONE - database loaded and verified; ORM smoke passes | n/a (scope change) | Claude | 2026-11-16 |

| LOCAL-PG-002 | Verify application pages and reports on PostgreSQL; decide how to handle the two MySQL-only expressions (`CuotasController.php:342`, `EmailController.php:518`) and the four models without tables. Requires user decision because it touches production code. | TODO | n/a (scope change) | Claude | 2026-11-16 |

| LOCAL-PG-002 | Updated: index sweep done. Six PostgreSQL incompatibilities found (`caja`, `indiviso`, `conceptos`, `condomino`, `mensaje`, `saldo-condominio`) plus two known MySQL-only expressions. Decide schema-level versus code-level fixes; report actions and POST flows still unverified. | IN_PROGRESS - waiting for user decision | n/a (scope change) | Claude | 2026-11-16 |

| LOCAL-PG-002 | Updated: index-page PostgreSQL incompatibilities fixed and re-verified (0 SQL errors on 47 index pages). | DONE for index pages | n/a (scope change) | Claude | 2026-11-16 |

| LOCAL-PG-003 | Verify report actions, POST flows and exports on PostgreSQL with representative data; handle `EmailController.php:518`. | TODO | n/a (scope change) | Claude | 2026-11-16 |

| APP-DEFECTS-001 | Pre-existing, database-independent defects found by the sweep: 11 controllers without `isAuthorized()`; `src/Template/Conceptos/index.ctp:95` fails on an empty list; `MensajeController` index ordering key `'Mensaje-fecha'` sorts oldest first on MySQL; four models without tables (Formulario, Pendientes, Posts, UsuarioCondomino). | TODO - needs triage | n/a | Claude | 2026-11-16 |

| LOCAL-PG-003 | Updated: read-only action sweep done. Reports `informe`, `pdf-informe`, `informe-seg`, `info-anual`, `departamento`, `cierre` fail on PostgreSQL; about 61 aggregate queries (Reportes 44, Email 17) rely on MySQL loose `GROUP BY`. Needs a user decision on how to fix. | IN_PROGRESS - waiting for user decision | n/a (scope change) | Claude | 2026-11-17 |

| APP-DEFECTS-002 | Database-independent defects found by the action sweep: `UsuarioController::profile()` (resident landing page) references an undefined alias `T` and builds a join condition from a possibly null id; `ReportesController::cierre()` compares a date with a `Y-m` string; missing classes `NumerosEnLetras` (Conceptos `montoletras`) and `Configure` (Invoices `view`); several actions without templates. | TODO - needs triage | n/a | Claude | 2026-11-17 |

| LOCAL-PG-003 | Updated: read paths verified on PostgreSQL; no remaining PostgreSQL-caused SQL errors in 159 requests. POST flows, email and exports still unverified. | DONE for read paths | n/a (scope change) | Claude | 2026-11-17 |

| CAKE5-REPORTS-001 | During the CakePHP 5 migration (ADR-0004), rewrite `ReportesController` and `EmailController` aggregate queries as explicit, engine-neutral queries (group every selected column, numeric amount columns, no copy-paste per month) and remove `SqlDialect::relaxGroupBy()` and the text-sum aggregate. | TODO | ADR-0004 stage | Claude | 2026-11-17 |

| APP-DEFECTS-002 | Updated: also `ReportesController::pdfInforme()` uses an ambiguous `estatus` (line 1149; `informe()` uses `T.estatus`), so the informe PDF fails on MySQL too; `cierre()` details: unquoted `Y-m` comparison and hardcoded `id_condominio = 4`. | TODO - needs triage | n/a | Claude | 2026-11-17 |

| LOCAL-PG-004 | Known limits of `SqlDialect::relaxGroupBy()` on PostgreSQL (none hit today): default or contained fields with `GROUP BY`, `ORDER BY` on non-grouped columns, pattern-based aggregate detection, alias collisions. Fix only if a page hits one; otherwise superseded by CAKE5-REPORTS-001. | TODO - watch | n/a (scope change) | Claude | 2026-11-17 |

| APP-DEFECTS-002 | Updated: `pdfInforme` and `cierre` fixed. `usuario/profile` cause corrected (only fails for users without an assigned unit); needs a product decision. Still open: missing classes `NumerosEnLetras` and `Configure`, actions without templates, cierre fee subquery with `id_cuotas = id_cuotas`, unit column formatted as currency in the cierre template. | IN_PROGRESS | n/a | Claude | 2026-11-17 |

| APP-DEFECTS-002 | Updated: `usuario/profile` now shows a no-unit message. Still open: missing classes `NumerosEnLetras` and `Configure`, actions without templates, cierre fee subquery with `id_cuotas = id_cuotas`, unit column formatted as currency in the cierre template. | IN_PROGRESS | n/a | Claude | 2026-11-17 |

| APP-DEFECTS-002 | Closed: all listed defects fixed (pdfInforme, cierre period/condominium/join, profile without unit, missing classes, template-less actions, cierre fee subquery, unit column). Not in this list and still open from the sweep: `administrador/profile` and `users/profile` (`find()` on bool), `reportes/recibos` (`all()` on null). AJAX endpoints that need `page`/`size` query parameters were not exercised with them. | DONE | n/a | Claude | 2026-11-17 |

| APP-DEFECTS-001 | Still open: 14 controllers without `isAuthorized()` (authorization policy decision needed), four models without tables (Formulario, Pendientes, Posts, UsuarioCondomino), `EmailController.php:518` MySQL-only date arithmetic. | TODO | n/a | Claude | 2026-11-17 |

| APP-DEFECTS-003 | Fix `users/profile`, `administrador/profile` (`find()` on bool) and `reportes/recibos` (`all()` on null, wrong joins, no period filter). | DONE | n/a | Claude | 2026-11-17 |

| APP-DEFECTS-001 | Updated: `EmailController.php:518` fixed by porting the Reportes `saldo` definition. Still open: 14 controllers without `isAuthorized()` (including `EmailController`), four models without tables (Formulario, Pendientes, Posts, UsuarioCondomino), `EmailController::informeSeg` has no template; both informeSeg reports drop units without an opening balance row and repeat rows for units with several. | IN_PROGRESS | n/a | Claude | 2026-11-17 |

| MD-001 | Multi-development design (condominiums and subdivisions, isolated per development): answer business questions, write RH-005 requirements and acceptance criteria, then a design ADR (data model, tenant isolation, data migration). Spec: `docs/specs/multi-development.md`. | IN_PROGRESS - business questions round | G0/G1 (planning) | Claude | 2026-11-17 |

| RBAC-001 | Role-based access control design: answer business questions, define permissions and initial roles that reproduce the RH-004 inventory, then requirements and design ADR. Spec: `docs/specs/rbac.md`. Depends on RH-004 inventory; implemented with MD-001 in CakePHP 5. | IN_PROGRESS - business questions round | G0/G1 (planning) | Claude | 2026-11-17 |

| PG-PROD-001 | Plan the production move from MariaDB to PostgreSQL: target version, data migration (reuse `tools/convert-mysql-dump-to-pgsql.py` findings), validation of row counts and totals, backup/restore, rollback, and in which ADR-0004 stage it happens. | TODO | planning | Claude | 2026-11-17 |

| PAY-001 | Online payments: choose a gateway provider, then specify hosted checkout, notification handling, automatic and idempotent payment recording, reconciliation, fees and refunds. Implemented on CakePHP 5 with RBAC and multi-development. | TODO - planning | G0 (planning) | Claude | 2026-11-17 |

| RBAC-002 | Editable roles: a management screen for the administration to create roles and choose their permissions, on top of the fixed RBAC-001 catalogue. Deferred by user decision. | DEFERRED | after RBAC-001 | Claude | 2026-11-17 |

| MD-001 | Updated: business question round complete (8 questions plus follow-ups recorded in the spec's Decisions Log). Next: requirements RH-005 with acceptance criteria, including the open proposals (payer change and earlier charges, per-square-metre fee on units without lot area, global catalog editing and duplicate merge, debt restriction scope pending legal validation). | IN_PROGRESS - requirements next | G0/G1 (planning) | Claude | 2026-11-17 |

| RBAC-001 | Updated: business question round complete (roles per development, fixed catalogue, super-administrator with proposed safeguards, guards with login, suppliers without access). Next: requirements RH-006, after or alongside the RH-004 inventory. | IN_PROGRESS - requirements next | G0/G1 (planning) | Claude | 2026-11-17 |

| GATE-001 | Gate access for any development: access log, resident vehicles, frequent service staff, debt restrictions. Part of MD-001; the debt restriction scope must be validated by the business with legal advice before it is specified. | TODO - planning | G0 (planning) | Claude | 2026-11-17 |

| MD-001 / RBAC-001 | Requirements RH-005 and RH-006 drafted with acceptance criteria; awaiting user review, the six G1 decisions, and G1 approval. | REVIEW | G1 (planning) | Claude | 2026-11-17 |

| MD-001 / RBAC-001 | Updated: all six G1 decisions recorded. Waiting for explicit G1 approval of RH-005 and RH-006. Open operational item for the migration: which per-development roles `Alan` receives. | REVIEW - G1 approval pending | G1 (planning) | Claude | 2026-11-17 |

| MD-001 / RBAC-001 | G1 PASS 2026-11-17. Next: G2 design ADR (data model for developments, sections, units, people and occupancies; fee methods; catalogs; tenant isolation; RBAC permissions, roles and policies; data and user migration). RBAC baseline still depends on the RH-004 inventory. | G1 PASS - G2 next | G2 (design) | Claude | 2026-11-17 |

| MD-001 / RBAC-001 | G2 design drafted in `docs/adr/ADR-0005-multi-development-rbac-design.md`; three G2 decisions pending, then G2 approval. RH-004 inventory still required before the RBAC seed. | G2 IN_PROGRESS | G2 (design) | Claude | 2026-11-17 |

| MD-001 / RBAC-001 | Updated: three G2 decisions confirmed in ADR-0005. Waiting for G2 approval. | G2 IN_PROGRESS - approval pending | G2 (design) | Claude | 2026-11-17 |

| PG-PROD-001 | Updated: obtain a complete production dump for the migration rehearsal; the 2026-10-29 dump contains only catalogs and 3 users. | TODO | planning | Claude | 2026-11-17 |

| MD-001 / RBAC-001 | G2 PASS with condition (2026-11-17). Next for this track: prerequisites (RH-004 inventory, complete production dump), then implementation in the CakePHP 5.x remodel stage after the framework upgrade. | G2 PASS | G3 later (implementation) | Claude | 2026-11-17 |

| RLS-SEC-001 | G2 condition: dedicated security review of the row-level security policies, `SECURITY DEFINER` functions and triggers in ADR-0005 section 1, and the policy test suite (legitimate and forbidden operations run against a real database) passing before production. | TODO | before production of the remodel stage | Claude | 2026-11-17 |

| RH-004 | Authorization inventory: profiles, rules, matrix, critical actions, tenant checks, risks. Evidence: `docs/authorization-inventory-results.md`. | DONE - awaiting user review | G3 evidence | Claude | 2026-11-17 |

| AZ-1 | `/usuario/vigilante/{id}` answers without a session and shows any visit by id. Needs a decision: require a guard login now (production code change) or wait for RBAC. | TODO - user decision | security | Claude | 2026-11-17 |

| AZ-2 | Actions that receive a condominium id do not check it against `users_condominio` (cross-condominium access by URL for staff). Covered by MD-001/RBAC-001 isolation; interim fix needs a user decision. | TODO - user decision | security | Claude | 2026-11-17 |

| AZ-1 | Fixed 2026-11-17: guard visit page requires a staff sign-in. Before release, give an account to anyone who used the page without one. | DONE | security | Claude | 2026-11-17 |

| AZ-2 | Interim fix done 2026-11-17: condominium ids in the URL are checked against the user's membership for non-Superusers. Remaining gaps (query string or form ids, records opened by their own id) are covered by RBAC (ADR-0005). | DONE (interim) | security | Claude | 2026-11-17 |

| TEST-FIXTURES-001 | Several baked test fixtures no longer match the schema (for example `TipoPagoFixture` lacks `id_condominio`), which blocks integration tests on those controllers. Regenerate or update them from the current schema. | TODO | testing | Claude | 2026-11-17 |

| TEST-FIXTURES-001 | Done 2026-11-17: 17 fixtures aligned with the production-derived schema; `tools/sync-test-fixtures.py --check` reports future drift. | DONE | testing | Claude | 2026-11-17 |

| TEST-SUITE-001 | Remaining full-suite failures, not fixture-related: `CargoTableTest` tests a table class that does not exist; `PagesControllerTest` is the CakePHP skeleton test and assumes pages without sign-in. Decide: delete or rewrite. | TODO | testing | Claude | 2026-11-17 |

| TEST-SUITE-001 | Done 2026-11-17: full suite has 0 errors and 0 failures (363 tests). 299 baked placeholder tests remain incomplete; they are candidates to replace with real tests during the CakePHP 5 migration. | DONE | testing | Claude | 2026-11-17 |

| SECRETS-001 | Done 2026-11-17: SMTP passwords moved out of versioned files. Pending for the business: rotate the `gmail` and `alpa` SMTP passwords, and set them on the production server (`config/app_local.php` or `EMAIL_GMAIL_PASSWORD` / `EMAIL_ALPA_PASSWORD`) before deploying the new `config/app.php`. | DONE (rotation pending) | security | Claude | 2026-11-17 |

| REPO-001 | First commit to `git@github.com:e2lira/adminhouse.git`: local repository initialized with `.gitignore` and remote; 953 files ready, no secret among them. Pending: user review of the file list, fresh-context review before commit (AGENTS rule), and explicit approval to commit and push; check whether the remote already has content. | TODO - user approval | repository | Claude | 2026-11-17 |

| REPO-001 | Updated: first local commit `4793471` done (on top of remote `de284f1`). Pending: check whether the remote already has content, decide public or private visibility, rotate SMTP passwords, then push with explicit approval. | IN_PROGRESS - push pending | repository | Claude | 2026-11-17 |

| PG-PROD-001 | Re-scoped 2026-11-17: no active production server. The first real deployment starts directly on PostgreSQL; no MariaDB data migration unless real data appears. | TODO (simplified) | planning | Claude | 2026-11-17 |

| SECRETS-001 | Re-scoped 2026-11-17: no production server to configure. Still recommended: rotate the `gmail` and `alpa` SMTP passwords. | DONE (rotation recommended) | security | Claude | 2026-11-17 |

| REPO-001 | Done 2026-11-17: `main` pushed by the user; remote verified at `d5613b6`. | DONE | repository | Claude | 2026-11-17 |

| REBUILD-000 | Decide where the new CakePHP 5 application lives (same repository in a new directory, new repository, or replacing the legacy code). | TODO - user decision | planning | Claude | 2026-11-17 |

| REBUILD-001 | Feature inventory (parity checklist) of the legacy application: every screen, action, report, email and file flow, classified as implement, redesign (with the ADR-0005 element) or retire. | TODO | planning | Claude | 2026-11-17 |

| REBUILD-002 | New CakePHP 5 skeleton with PostgreSQL, test tooling (strict TDD), coding standards and CI basics. | TODO | build | Claude | 2026-11-17 |

| REBUILD-003 | Module 1: identity, TOTP and RBAC (permissions, roles, assignments, policies, default deny) with development scoping. | TODO | build | Claude | 2026-11-17 |

| ADR-0004 stages 1-3 | Cancelled by ADR-0006. | CANCELLED | n/a | Claude | 2026-11-17 |

| SEC-001 / G3 (legacy) | No longer leads to a production release (ADR-0006). The `.DS_Store` HTTP check applies only if the legacy application is served again. | ON HOLD | legacy | Claude | 2026-11-17 |

| REBUILD-000 | Done 2026-11-17: new application in `app/` within the same repository. | DONE | planning | Claude | 2026-11-17 |

| REBUILD-001 | Draft done 2026-11-17: `docs/feature-inventory.md`. Pending user decisions: 11 controllers unreachable today, 5 probable dead stubs (including `Email::informeSeg`, whose saldo was ported on 2026-11-17), report list per module. | DRAFT - user review | planning | Claude | 2026-11-17 |

| AZ-8 | Legacy reference: `Condominio::pa` and `Gasto::pa` print URL input unescaped (reflected XSS). Retired in the rebuild; make them `protected` only if the legacy app is served again. | NOTED | security | Claude | 2026-11-17 |

| REBUILD-001 | Updated: all decisions taken (0 open). Remaining: report list per module, confirmed when the reports module starts. | DONE | planning | Claude | 2026-11-17 |

| REBUILD-002 | Done 2026-11-17: CakePHP 5.4.4 skeleton in `app/` on PostgreSQL; tests and coding standard green; root CI workflow added (not yet run). | DONE | build | Claude | 2026-11-17 |

| REBUILD-003a | Slice 1 done 2026-11-17: identity schema, fixed roles, password sign-in, `create_user` command. Not committed. | DONE | build | Claude | 2026-11-17 |

| REBUILD-003-F1 | `create_user` echoes the password on Windows consoles (hidden input only on Unix ttys). Fix before creating real accounts on Windows. | TODO | build | Claude | 2026-11-17 |

| REBUILD-003-F2 | A user disabled while signed in keeps the session; re-check user status (and later role assignments) on each request. Planned in slice 2. | TODO | build | Claude | 2026-11-17 |

| REBUILD-003a | Slice 1 committed locally as `bd62a64` (2026-11-18). | DONE | build | Claude | 2026-11-18 |

| REBUILD-003b | Slice 2 done 2026-11-18: permission catalogue, AccessControl scope, authorization plugin with default deny, `/developments`, cookie hardening. Not committed; role matrix awaiting user confirmation. | DONE | build | Claude | 2026-11-18 |

| REBUILD-003-F2 | Done in slice 2: user reloaded on every request; disabled or deleted users are signed out. | DONE | build | Claude | 2026-11-18 |

| REBUILD-003-F3 | Static check that every routed action performs an authorization check (AC-RB-1); runtime check only fires after the action body. | TODO | build | Claude | 2026-11-18 |

| REBUILD-003-F4 | Role assignment date window uses `Date::today()` in `App.defaultTimezone` (UTC): in Mexico an assignment ending today stops granting around 18:00 local. Decide business timezone (per development or America/Mexico_City) and compute today with it. | TODO | build | Claude | 2026-11-18 |

| REBUILD-003c | Slice 3 done 2026-11-18: TOTP second factor with backup codes and sign-in audit; at most two super-administrators enforced in the database. | DONE | build | Claude | 2026-11-18 |

| REBUILD-003-F5 | Slice 3 review suggestions: atomic failure counter for parallel verify requests; avoid storing raw submitted usernames on failed password (may contain a pasted password); audit deactivated user abandoning the second step. | TODO | build | Claude | 2026-11-18 |

| REBUILD-003-F6 | Decide whether the max-two super-administrator rule should count only assignments in force (today it counts ended ones, so freeing a slot means deleting history). | TODO | decision | User | 2026-11-18 |

| REBUILD-003-F6 | Done 2026-11-18: limit counts only non-ended super_administrator assignments (user decision). | DONE | build | Claude | 2026-11-18 |
