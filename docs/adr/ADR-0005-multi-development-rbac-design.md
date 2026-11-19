# ADR-0005: Multi-Development and RBAC Design

- Status: Accepted with condition (G2 PASS for the MD-001 / RBAC-001 track by explicit user approval on 2026-11-17; revision 4 after three independent reviews). Condition: the row-level security policies, definer functions and triggers of section 1 get their own security review and the policy test suite must pass against a real database before they reach production (task RLS-SEC-001).
- Date: 2026-11-17
- Requirements: RH-005 (`docs/specs/multi-development.md`), RH-006 (`docs/specs/rbac.md`), both G1 PASS on 2026-11-17
- Related: ADR-0004 (gradual CakePHP upgrade), ADR-0003 and FILE-001 (private files), PG-PROD-001, PAY-001, GATE-001, CAKE5-REPORTS-001

## Context

Kualitet must run condominiums and residential subdivisions as isolated developments, with role-based access per development. Today the condominium is an informal tenant key on 35 of 45 tables, units and residents share one row, roles are six global profile numbers checked by hand in 39 controllers, and money is stored as binary `FLOAT` in production MariaDB. Production will move to PostgreSQL (PG-PROD-001) and the code to CakePHP 5 (ADR-0004). PostgreSQL 16 or later is required (`ANY_VALUE`, `NULLS NOT DISTINCT`); the target is 18.

The 2026-10-29 dump used for local work contains only catalogs and 3 users: units, residents, debts, payments and expenses are empty. It is enough to design the schema, not to rehearse the data migration.

This ADR fixes how the approved requirements will be built. It is design only: no code, schema, data or production change.

## Decision

### 1. Tenancy and isolation (MD-R2, MD-R10, RB-R7, RB-R8; AC-MD-1, AC-MD-2, AC-MD-10, AC-MD-11, AC-RB-2, AC-RB-5)

**Shared database, shared schema.** Every development-owned table has a non-null `development_id`, including child tables (`deliveries`, `fee_targets`, `unit_balances`, `expense_lines`, `message_replies`, `inspection_items`), which carry it with a composite foreign key to their parent. One administration company operates the system, so there is no organization level.

**Scope is computed per permission.** `Scope::allowedFor(permission)` returns the developments where the user holds an active, non-read-only role granting that permission (all developments for the super-administrator). A user who is Accountant in A and Resident in C gets `{A}` for `reports.view`.

**Resident sub-scope, per permission.** When a permission is granted in a development only through the Resident role, results are limited to the units of the person's current occupancies. A read-only (former) Resident assignment grants only read permissions, limited to the person's own charges, payments and receipts dated within that occupancy period (G2 decision 4).

**Guard projection.** Guards hold gate permissions plus `gate.lookup`, served by a column-limited view (unit identifiers, current occupant names, vehicles, service-staff authorisations); it never exposes contact data, RFC or money (AC-MD-11).

**Layer 1 - application scope (mandatory).** A CakePHP 5 ORM behavior (`DevelopmentScopedBehavior`) applies the permission-aware scope of the running action to every query on a scoped table. Writes set and validate `development_id`. A query on a scoped table without an active scope throws instead of returning everything. Layer 1 is the only place where per-role rules (for example "a resident cannot edit a fee") are enforced; layer 2 stops cross-development access.

**Layer 2 - PostgreSQL row-level security (backstop).** Confirmed by the user 2026-11-17 (G2 decision 1).

*Session values.* Every request, job and webhook runs inside one database transaction (middleware), and sets with `SET LOCAL` only `app.user_id` and `app.context` (`request`, `job`, `webhook`), plus `app.job_dev_ids` for jobs. The database derives everything else: `STABLE SECURITY DEFINER` helpers compute `dev_ids()` (developments where the user has an active role), `admin_dev_ids()` (developments where the user is Administrator), `is_super()` and `person_id()` from `role_assignments` and `users` on each transaction, so a revoked role takes effect on the next request and the application cannot claim super-administrator rights by setting a value. Helpers read settings with `NULLIF(current_setting(name, true), '')`, so an empty or missing value means "no access", never an error.

*Privileged objects.* All `SECURITY DEFINER` functions and triggers are owned by a dedicated `NOLOGIN` role with `BYPASSRLS`, use `SET search_path = pg_catalog, pg_temp` and schema-qualified names, and have `EXECUTE` revoked from `PUBLIC` and granted only to the application role. The application role owns no table; every table has `FORCE ROW LEVEL SECURITY`. The migration role is a separate `BYPASSRLS` login used only during the migration window and set to `NOLOGIN` afterwards.

*Policies are written per command* (`SELECT`, `INSERT`, `UPDATE`, `DELETE`), because PostgreSQL applies a read predicate to updates and deletes as well:

| Table class | SELECT | INSERT / UPDATE / DELETE |
|---|---|---|
| Scoped domain tables | `is_super() OR development_id = ANY(dev_ids())` | same (coarse; per-role limits in layer 1) |
| Catalogs (`development_id` NULL = global) | `development_id IS NULL OR is_super() OR development_id = ANY(dev_ids())` | `is_super() OR (development_id IS NOT NULL AND development_id = ANY(admin_dev_ids()))`; a trigger forbids changing `development_id` |
| `developments` | `is_super() OR id = ANY(dev_ids())` | `is_super()` |
| `role_assignments` | own rows `OR is_super() OR development_id = ANY(admin_dev_ids())` | `is_super() OR development_id = ANY(admin_dev_ids())`; a trigger forbids changing `user_id`, `role_id` or `development_id` (revoke and create instead) and lets only a super-administrator grant `super_administrator` |
| `occupancies` | `person_id = person_id() OR is_super() OR development_id = ANY(dev_ids())` | `is_super() OR development_id = ANY(admin_dev_ids())` |
| `people` | own row, `is_super()`, or linked through `person_developments` to `dev_ids()` | no direct `INSERT`: `person_create(development, data)` (definer) inserts the person and its `person_developments` row atomically after checking `admin_dev_ids()`. `UPDATE` only for `is_super()`, the person's own contact fields, or administrators when every development linked to the person is in `admin_dev_ids()`; shared people are edited by the super-administrator |
| `person_developments` | `development_id = ANY(dev_ids())`, own rows, or `is_super()` (a person's links to other developments stay hidden) | through definer functions only |
| `users` (username, `person_id`, status) | own row, `is_super()`, or linked through `role_assignments` to `admin_dev_ids()` | through `user_create` and assignment functions only |
| `user_credentials` (password hash, TOTP secret, backup codes) | no access for the application role | sign-in, TOTP and resets only through definer functions; a reset is refused when the target holds a super role or any assignment outside the actor's `admin_dev_ids()` |
| `roles`, `permissions`, `role_permissions` | readable | migration role only |
| `audit_log` | `is_super()`, or rows of `dev_ids()` | insert only through a definer function; no update or delete |

*Identity before sign-in.* Sign-in, TOTP and password reset call definer functions (`auth_sign_in`, `auth_verify_totp`, `auth_reset`) that read `users` and `user_credentials` and return only what the step needs. No policy depends on an identity that does not exist yet.

*Webhooks.* A gateway notification runs with `app.context = 'webhook'` and no user. The handler calls a single definer function, `record_gateway_payment(reference, amount, signature data)`, which resolves the intent and writes only payment tables (section 2); no other table write is possible in that context. The audit actor is "system: gateway".

*Jobs and CLI.* Every job and command runs with a `DevelopmentContext` that sets `app.context = 'job'` and `app.job_dev_ids` (one development, or an explicit list for super-administrator jobs); helpers use that list instead of role assignments in job context. Reminder and notice jobs run once per development. A test fails if any job, command or webhook touches a scoped table without a context.

*Catalog references.* Rows referencing a catalog item use a simple foreign key plus a trigger that accepts the item only if it is global or belongs to the same development (composite keys cannot reference global rows).

**Not found, not forbidden.** Access to another development's record answers 404 without metadata (same rule as FILE-001). Every scoped table has an index starting with `development_id`; policies call helpers inside `(SELECT ...)` so PostgreSQL evaluates them once per query.

**Consolidated reports** use `allowedFor('reports.consolidated')`.

**Policy test suite.** Because these policies are security-critical, the implementation stage ships an automated suite that runs each legitimate operation (sign-in and TOTP, a resident and a former resident reading their own records, a guard writing the access log and using the lookup, an administrator creating a person and an occupancy, a gateway notification, a reminder job, the super-administrator in any development) and each forbidden one (cross-development read and write, a resident inserting an assignment, an administrator editing a global catalog item or another development's user credentials) directly against the database role.

### 2. Data model (MD-R1, MD-R3 to MD-R9, MD-R11, MD-R12)

Names follow CakePHP conventions in English (confirmed by the user 2026-11-17, G2 decision 2). Only internal names change: screens, receipts and reports stay in Spanish, rendered from one template set per document type that takes the development's labels and fee details (MD-R11).

| Area | Tables (key columns and rules) |
|---|---|
| Developments | `developments` (type `condominium`/`subdivision`, name, `section_label`, `number_label`, `timezone`, settings) |
| Units | `sections` (development, name); `units` (development, section nullable, number, type `apartment`/`house`/`lot`, `lot_area_m2` nullable, `indiviso_pct` nullable, current `payer` `owner`/`tenant`); unique `(development_id, section_id, number)` with `NULLS NOT DISTINCT`; `unit_payer_changes` (unit, payer, changed_at, by) |
| People | `people` (name, contact, RFC); `person_developments` (person, development); `occupancies` (development, unit, person, role `owner`/`tenant`, `is_primary`, `starts_on`, `ends_on`), periods as half-open ranges `[starts_on, ends_on)` so a same-day transfer ends one occupancy on the day the next starts. Exclusion constraints (`btree_gist`, daterange): the same person cannot hold overlapping occupancies of the same role on the same unit; and at most one **primary** owner and one primary tenant per unit at any time. Co-owners and roommate tenants are additional non-primary rows. `users.person_id` is unique: one login per person |
| Fees and charges | `fees` (development, fee type, method `fixed`/`per_m2`/`indiviso`, amount or rate, period); `fee_targets` (fee, unit, `included`/`excluded`, reason, by); units created after a fee is defined are not targeted unless added. `charges` (development, unit, fee, period, base used, amount, `payer_person_id` snapshot, status); unique `(unit_id, fee_id, period)` |
| Payments | `payment_intents` (development, unit, person, amount, `gateway_reference` unique, status `pending`/`succeeded`/`failed`/`expired`) for online payments; `payments` (development, payer person, amount, method `manual`/`gateway`, intent nullable and unique, folio, status `posted`/`reversed`); `payment_exceptions` (development, intent, reference, amount, reason, resolution) for gateway money that cannot become a payment automatically; `payment_allocations` (development, payment, charge, amount); `receipt_series` (development, series, next folio), folio taken with `SELECT ... FOR UPDATE`; unique `(development_id, series, folio)` |
| Credit | `credit_entries` (development, person, unit, amount +/-, source, reason); the balance per person and unit is their sum and cannot go below zero (trigger) |
| Expenses | `expenses`, `expense_lines`, `petty_cash`, `bank_accounts`, `bank_movements`, `supplier_bills` |
| Closings | `period_closings` (development, period, opening and closing balance, `closed_at`); `unit_balances` (unit, period, balance). A trigger rejects inserts, updates or reversals dated inside a closed period |
| Catalogs | `payment_types`, `fee_types`, `expense_types`, `expense_categories`, `suppliers`, `supplier_categories`, `visit_types`: `development_id` nullable, NULL = global |
| Communications | `notices`, `messages`, `message_replies`, `reminders`, `tickets`, `regulations`, `amenities`, `bookings`; `deliveries` (source type `notice`/`reminder`/`statement`/`receipt`, source id, person, channel, sent_at) |
| Gate | `visits` (QR), `access_log` (development, guard, unit, person or visitor, direction, at), `vehicles` (unit, plates, tag), `service_staff`, `service_staff_authorizations` (unit, weekdays, time window in the development's timezone) |
| Inspections | `inspections`, `inspection_items` (current `supervision` and `areas`) |
| Files | `files` (development, owner entity, category, storage key, checksum), stored outside the web root and served through the FILE-001 authorization check |
| Access control | see section 3 |
| Audit | `audit_log` (development nullable for global actions, actor user or system, action, route, entity, entity id, at, before and after summary), append-only |

Ledger and process rules:

- **Money is `numeric(12,2)`**, never floating point.
- **Same development everywhere**: charges, payments, intents, allocations, units and credit entries carry `development_id`; foreign keys are composite `(development_id, id)`.
- **Allocation invariants** (`SECURITY DEFINER` trigger, same transaction, after locking the payment and the charge rows with `SELECT ... FOR UPDATE`): allocations of a payment total at most its amount; allocations to a charge total at most its amount; the unallocated part of a payment becomes a `credit_entries` row.
- **Payer snapshot** (MD-R5): when a charge is generated, `payer_person_id` is the primary owner or the primary tenant of the unit, according to `units.payer`. If that primary does not exist, generation stops and lists the units, like MD-R8.
- **Allocation by payer**: online payments allocate only to charges whose payer is the paying person; staff with `payments.allocate_any` can allocate across payers of the same unit.
- **Reversals, never deletes**: a cancelled payment is marked `reversed`, its allocations are reversed by negative rows and credit entries adjusted. Inside a closed period nothing changes: the correction is an adjustment entry in the current open period, or the super-administrator reopens the period with a recorded reason.
- **Per-square-metre fees** (MD-R8): generation stops if any included target lacks `lot_area_m2` and lists those units.
- **Indiviso fees** (AC-MD-5): the included targets' `indiviso_pct` must add up to 100.00 or generation stops; the rounding remainder goes to the included unit with the largest `indiviso_pct` (ties: lowest unit id).
- **Owner and tenant delivery** (MD-R6, AC-MD-8): notices, reminders, statements and receipts create one `deliveries` row per current owner and tenant (primary and non-primary) of each targeted unit.
- **Online payments** (PAY-001): an intent is created before redirecting to the gateway. `record_gateway_payment` verifies the notification and runs `UPDATE payment_intents SET status = 'succeeded' WHERE gateway_reference = ? AND status = 'pending'`; only if that row changes does it create the payment, in the same transaction. A repeated notification changes no row and creates nothing. An amount different from the intent, or a success for an intent already `expired` or `failed`, creates a `payment_exceptions` row for staff, who either record the payment or refund it; money is never received without a record.

### 3. RBAC (RH-006)

| Table | Purpose |
|---|---|
| `permissions` | Named permissions, for example `payments.create`, `payments.allocate_any`, `reports.view`, `reports.consolidated`, `gate.log`, `gate.lookup`, `catalogs.global.edit` |
| `roles` | Fixed catalogue: administrator, accountant, data_entry, supervisor, guard, resident, super_administrator (RB-R2) |
| `role_permissions` | Seeded by a migration; no editing screen in this release (RBAC-002 later) |
| `role_assignments` | user, role, `development_id`; unique `(user_id, role_id, development_id)` with `NULLS NOT DISTINCT`; a user may hold several roles in one development; `development_id` NULL if and only if the role is `super_administrator` (check constraint) |

- **Enforcement** with the CakePHP 5 Authorization plugin: request policies for actions and entity policies for records ask `identity->can(permission, development)`; scopes come from `Scope::allowedFor(permission)`. No code compares profile numbers (RB-R6, AC-RB-7).
- **Default deny:** a middleware refuses any request whose action has no policy; an automated test walks every route (AC-RB-1).
- **Resident assignments follow occupancies:** `role_assignments` has `starts_on`, `ends_on` and `read_only`. A Resident assignment is created when an occupancy starts; when the occupancy ends it is set `read_only = true`, which removes every write permission and limits reads to the person's own charges, payments and receipts dated within the occupancy period (G2 decision 4, confirmed 2026-11-17).
- **Super-administrator:** TOTP is mandatory on every sign-in path, including CLI tools that act as a user; at most 2 assignments, enforced by a `SECURITY DEFINER` trigger that locks before counting (AC-RB-9), plus an application check. Every request it makes (method, route, development, time, outcome) and every write (with before and after summary) goes to `audit_log`; this covers "every action" in RB-R5 and AC-RB-4. The request entry is written in its own transaction, so denied or failed attempts are kept even when the request rolls back.
- **Baseline:** (1) produce the RH-004 inventory on the current system; (2) seed `role_permissions` so that every current profile reproduces its matrix; (3) after migration, run the matrix as automated tests before any intended change (RB-R10, AC-RB-6). Permissions with no current equivalent (`reports.consolidated`, `gate.log`, `gate.lookup`, `payments.allocate_any`, `catalogs.global.edit`) are granted only as RH-005 and RH-006 define and are tested separately from the baseline.

### 4. Migration (MD-R14, RB-R11)

1. **Source**: a complete production dump taken for the rehearsal. The 2026-10-29 dump is not sufficient (no operational rows).
2. **Staging**: load the source into a staging schema with the migration role (`BYPASSRLS`).
3. **Transform** each table as mapped in the appendix. Amounts are rounded half-up to two decimals per row. Each migrated `condomino` resident becomes the primary owner.
4. **Quarantine**: rows that cannot be mapped (non-catalog rows with NULL `id_condominio`, references to missing rows, unparsable `varchar` keys in `indiviso.id_condomino` and `amenidad_condominio.id_condominio`, a `condomino` row whose resident fields cannot be split) go to quarantine tables with the reason; the business resolves each one before production.
5. **Catalog merge**: the script writes a merge proposal; nothing merges until a person approves it; references are re-pointed to the surviving rows (AC-MD-13).
6. **Users**: profile to role mapping; `users_condominio` to assignments; `asigna` to resident assignments and `users.person_id`; `dante` and `edgar` to super-administrator; `Alan` to the per-development roles named before the run (RB-R11).
7. **Reconciliation** (AC-MD-12): evaluated after the quarantine is resolved (each row migrated, corrected or approved as discarded). Row counts per table and development match exactly; money totals compare the sum of the rounded source rows with the target in cents and match exactly. A second report lists every row whose rounding changed the stored value; a person approves it as part of the release.
8. Rehearse on a copy until both reports are clean; production runs only with backup, restore and rollback evidence (`docs/migration-plan.md`).

### 5. Sequencing within ADR-0004

**Amended by ADR-0006 (2026-11-17):** there is no production server, so the user replaced the staged upgrade with a new CakePHP 5 application that implements this design from the start. The text below is kept for history.

Confirmed by the user 2026-11-17 (G2 decision 3). The framework upgrade (stages 0 to 3) runs first on the current schema. This model is then introduced as a separate stage on 5.x with its own data migration and the rebuilt reports (CAKE5-REPORTS-001). The PostgreSQL production move (PG-PROD-001) happens before that stage, or as its first step, so row-level security ships with the new model; it never shares a release with a framework stage.

## Alternatives

1. **Database or schema per development** - rejected: one company runs every development, consolidated reports need cross-development queries, and per-development schemas multiply migrations and backups.
2. **Application scope only, without RLS** - declined by the user (G2 decision 1).
3. **Per-user instead of per-permission scope** - rejected after review: a user with different roles in different developments would see data through the wrong role.
4. **Keep the current Spanish table names** - declined by the user (G2 decision 2).
5. **Remodel during the framework upgrade** - declined by the user (G2 decision 3).
6. **Keep `FLOAT` for money** - rejected: binary floating point cannot represent cents exactly.

## Consequences

- Every query on a scoped table depends on an active, permission-aware scope; jobs, commands and webhooks need an explicit context.
- RLS is a coarse backstop; per-permission and per-unit rules depend on layer 1 and its tests.
- A small set of `SECURITY DEFINER` functions and triggers becomes security-critical code and needs its own review and tests.
- `SqlDialect` and the local PostgreSQL compatibility helpers are retired when the rebuilt reports replace the current ones.
- The RH-004 inventory and a complete production dump are hard prerequisites.
- The reconciliation reports, the rounding report, the quarantine decisions and the catalog merge file are release artifacts of the migration stage.

## Review (2026-11-17)

- **Review 1** (revision 1): 5 critical, 9 warning, 3 suggestion items. Revision 2 addressed them, except constant-time "not found" responses (not adopted: low risk for this application).
- **Review 2** (revision 2): confirmed most fixes and found 3 critical, 6 warning and 6 suggestion items. Revision 3 addresses: identity loading before sign-in, user and context settings, policies for access tables, people and users without recursion, webhook and job contexts, migration role, definer triggers (C1); people ownership through `person_developments` (C2); co-owners and roommates with a primary flag instead of a contradictory exclusion rule (C3); guard projection (W1); payer when the primary is missing and payment intents (W2); deliveries for reminders, statements and receipts, and resident access after occupancy (W3, with G2 decision 4); audit of every super-administrator request and system actors (W4); multiple roles per development (W5); AC-MD-12 after quarantine (W6); timezone, folio locking, PostgreSQL 16 minimum, merged fee targets, closed-period lock, non-negative credit, missing-setting handling, seeding of new permissions (S1 to S6). Revision 3 was re-reviewed (review 3).
- **Review 3** (revision 3, focused on the changes): 3 critical, 9 warning and 3 suggestion items. Revision 4 addresses: per-command policies with administrator-only writes on catalogs and access tables and immutable assignment keys (C1); credentials moved to `user_credentials`, reachable only through definer functions that refuse resets across developments or of a super-administrator (C2); `person_create` and `user_create` definer functions for atomic creation (C3); owner role of definer objects, `NOLOGIN` migration role after the run (W1); only `app.user_id` and `app.context` set by the application, everything else derived in the database, empty settings handled, one transaction per request (W2); occupancies readable by `person_id` (W3); catalog references by simple key plus trigger (W4); `development_id` on child tables and a class for every table, hidden cross-development links (W5); row locks, unique intent per payment, payment exceptions for mismatched or late gateway money (W6); read-only resident assignments with dates (W7); request audit in its own transaction (W8); corrections for closed periods (W9); webhook limited to one definer function (W10); column-limited lookup view, `(SELECT ...)` helpers, half-open occupancy ranges, and a policy test suite. Revision 4 has not been re-reviewed.

## Decisions needed at G2 review

1. ~~Two isolation layers.~~ Confirmed 2026-11-17.
2. ~~English CakePHP-convention names.~~ Confirmed 2026-11-17.
3. ~~Framework first, then the remodel as its own stage.~~ Confirmed 2026-11-17.
4. ~~Access for former residents.~~ Confirmed 2026-11-17: when an occupancy ends, the former owner or tenant keeps read-only access to their own charges, payments and receipts of that occupancy period, and to nothing created after it ended.

All four G2 decisions are confirmed. G2 approved by the user on 2026-11-17 with the condition stated in the header (RLS-SEC-001).

## Appendix: current table mapping

| Current table | Target | Notes |
|---|---|---|
| `condominio` | `developments` | type `condominium` |
| `condomino` | `units`, `people`, `person_developments`, `occupancies` | resident becomes the primary owner; split failures quarantined |
| `asigna` | `role_assignments` (resident), `users.person_id` | |
| `users`, `perfil`, `users_condominio` | `users`, `roles` (seed), `role_assignments` | |
| `cuotas` | `fees` | method `fixed` |
| `indiviso` | `fees` (method `indiviso`) and `charges` | per-unit rows to confirm during rehearsal; `varchar` unit key parsed or quarantined |
| `bloque` | `charges` | batch charges per unit; to confirm during rehearsal |
| `adeudos` | `charges` | `estatus` to charge status |
| `pago` | `payments`, `payment_allocations` | allocation from `id_cuotas` where present; otherwise credit |
| `favor` | `credit_entries` | |
| `folios` | `receipt_series` | |
| `recibos_pago` | retired; receipt batches regenerated from `payments` | |
| `saldo_condominio`, `saldo_cierre` | `period_closings` | |
| `saldo_condomino` | `unit_balances`; `contra` becomes an opening-balance charge | |
| `cierre_condomino` | `unit_balances` | `saldo` is `varchar`; parsed or quarantined |
| `gasto`, `conceptos`, `caja` | `expenses`, `expense_lines`, `petty_cash` | `conceptos.gasto` is text; parsed or quarantined |
| `cargo` | `supplier_bills` | `monto` is `real` |
| `banco`, `bancaria` | `bank_movements`, `bank_accounts` | |
| `categoria`, `tipo_gasto`, `proveedores`, `rubro` | `expense_categories`, `expense_types`, `suppliers`, `supplier_categories` | catalog merge |
| `tipo_pago`, `tipo_cuota`, `tipo_visita` | `payment_types`, `fee_types`, `visit_types` | catalog merge |
| `comunicado`, `recordatorio`, `destinatarios` | `notices`, `reminders`, `deliveries` | |
| `mensaje`, `respuestas`, `ticket`, `reglamento` | `messages`, `message_replies`, `tickets`, `regulations` | |
| `amenidad`, `amenidad_condominio`, `events` | `amenities`, `bookings` | `amenidad_condominio.id_condominio` is `varchar` |
| `visitas` | `visits` | |
| `supervision`, `areas` | `inspections`, `inspection_items` | |
| `excel` | `files` | import history |

## RLS-SEC-001 implementation notes (2026-11-18)

Implemented in `app/` (migrations `20261119000001` to `20261119000003`) with three roles: `kualitet_app` (runtime, owns nothing, no `BYPASSRLS`), `kualitet_owner` (tables, migrations only, `NOLOGIN` in production after the migration window) and `kualitet_definer` (`NOLOGIN`, `BYPASSRLS`, owns every `SECURITY DEFINER` function and trigger function). A dedicated security review in two parts (database side and application side) was run before the first commit; its confirmed findings were fixed test-first.

Accepted residual risks (layer 2 is a backstop against application bugs, not against an attacker who can run arbitrary SQL as the runtime role):

1. **`app.user_id` is application trust.** The database cannot authenticate it; the application sets it only from the authenticated identity, inside the request transaction, with `set_config(..., true)`.
2. **Job context is a database setting.** `app.context = 'job'` with `app.job_dev_ids` widens `dev_ids()` for scoped domain tables. No HTTP path sets it. Stronger option for later: a separate login role for jobs.
3. **Pre-authentication functions take an explicit user id.** `auth_sign_in`, `auth_totp_*`, `auth_backup_code*`, `auth_rehash` and `auth_second_factor_required` run before an identity exists, so they cannot be bound to `app.user_id`. With SQL control an attacker could read a password hash or the encrypted TOTP secret (useless without the application key), or replace backup codes of a user. The application only calls them after the password check of the same sign-in.
4. **Existence oracles.** `person_in_admin_scope` and `role_assignment_grantee_id` can confirm that ids or usernames exist; accepted for the role-assignment screen.

## developments-units notes (2026-11-19)

Added by the `developments-units` change (PR 3a-i); the decisions are AD7, AD8, AD13 and W-E of that change.

1. **`permission_dev_ids(code)` (AD7).** A `STABLE SECURITY DEFINER` helper, next to `dev_ids()` and `admin_dev_ids()`, that returns the developments where the session user may exercise a permission code: request context only (empty in jobs and webhooks), an active assignment today on an active user whose role holds the code in `role_permissions`. A code that does not end in `.view` also needs a writable assignment and a development that is not archived. `super_administrator` has no development rows, so policies add `is_super()` next to it. `RowLevelSecurity::managedDomainTable($table, $code, $updatableColumns)` builds the policies: SELECT follows `dev_ids()`, INSERT and UPDATE follow `permission_dev_ids(code)`, DELETE is super-administrator only, and the runtime role may update only the listed columns (never `id` or `development_id`).
2. **Deviation from section 1 (AD8).** Writes to people and occupancies will use `permission_dev_ids('people.manage')` and `permission_dev_ids('occupancies.manage')` instead of `admin_dev_ids()`, so the `data_entry` role can create and edit them (decision D6). The people UPDATE policy mirrors `person_in_admin_scope`: the person has links and every linked development is in the permission scope. The existing `admin_dev_ids()` policies stay as they are until those tables are changed.
3. **Archived developments (W-E).** The two enforcement layers differ on purpose. Layer 1 (`AccessControl::allowedFor`) includes archived developments for write codes; the refusal there comes from `DevelopmentPolicy::canManageUnits`, `canManagePeople` and `canManageOccupancies`, which refuse an archived development even for the super-administrator (they unarchive first). Layer 2 (`permission_dev_ids`) leaves archived developments out of write codes, which is the database refusal for everyone except the super-administrator, whose `is_super()` bypass is not narrowed by it (the policy layer covers that role). The parity test `RlsPermissionDevIdsParityTest` pins both divergences.
4. **Resident and `units.view` (AD13, user-confirmed 2026-11-18).** The first catalogue gave the resident `units.view` on the promise of a row-level rule limiting them to their own units. That rule does not exist yet, so migration `20261121000003` revokes the row (RB-R8, fail closed); its `down()` re-grants only that row. The resident gets unit access back with the resident sub-scope in the deferred lifecycle change (D7). `people.view` is not granted to the resident.
