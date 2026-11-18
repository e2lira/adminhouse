# Exploration: developments-units

Module 2 of the CakePHP 5 rebuild (ADR-0006 order): developments, sections, units, people, occupancies. User decisions D1-D8 were approved on 2026-11-18 and are recorded in `proposal.md`.

## Current state in `app/`

- **`developments` table** (migrations `20261117000001..5`, `20261118000001..5`): `id, type (condominium|subdivision), name, section_label, number_label, timezone (default America/Mexico_City), created, modified`.
- **People and users:**
  - `people`: `name, email, phone, rfc`.
  - `person_developments`: unique `(person_id, development_id)`.
  - `users`: unique `person_id`.
- **RLS** (`20261119000001..3`):
  - Helpers: `dev_ids()`, `admin_dev_ids()`, `is_super()`, `person_id()`, `session_user_id()`, `person_in_admin_scope()`.
  - `developments`: SELECT in scope; writes are super-administrator only.
  - `people`: SELECT through `person_developments`. UPDATE for the person's own row, administrators of every linked development, or the super-administrator. No INSERT.
  - `person_developments` and `users`: SELECT only.
- **`app/src/Database/RowLevelSecurity.php`:** the builders every new table must use: `scopedDomainTable()`, `catalogTable()`, `definerFunction()`, `enable()`, `policy()`, `grant()`, `grantDefiner()`, plus the predicates `IS_SUPER`, `DEV_IDS`, `ADMIN_DEV_IDS`, `SESSION_USER_ID` and `PERSON_ID`.
- **`AccessControl::allowedFor(userId, permission)`** returns a `DevelopmentScope` (all, or a list of ids). Read-only assignments keep only `.view` permissions.
- **Permission catalogue** (`20261118000001`): `developments.view`, `units.view`, `payments.create`, `reports.view`, `gate.log`, `role_assignments.manage`.
  - `units.view` goes to administrator, accountant, data_entry, supervisor and resident.
  - There is no `*.manage` permission for developments, units, people or occupancies.
  - Rule from the seed's comment: add a permission code in the same PR as the code that checks it.
- **Business UI:** only `DevelopmentsController::index` (read-only), guarded by `DevelopmentsTablePolicy`.
  - Default deny is active (`requireAuthorizationCheck`).
  - Scoping is done by hand in `DevelopmentsTablePolicy::scopeIndex`.
- **Not present yet:**
  - Tables: `sections`, `units`, `occupancies`, `unit_payer_changes`.
  - `DevelopmentScopedBehavior` (ADR-0005 layer 1, "mandatory"). Only mentioned in a comment at `RowLevelSecurity.php:193`.
  - Definer function `person_create`.
  - Create and edit screens for developments.
- **`btree_gist`:** not provisioned anywhere (`bin/provision_db_roles.php`, CI).

## Legacy reference (read only)

- **`condominio`:** `nombre, logo, dir, direccion, telefono, no_cuenta, banco_cuenta, rfc`.
- **`condomino`:** the unit and its resident in one row.
  - Unit and owner: `numero_departamento`, owner contact fields, `rfc`.
  - Tenant: `arrendatario` and `telefono_arrendatario`, text only.
  - Fees: `saldo_inicial` and `cuota` (both floats).
  - Other: vehicle fields, `qr`, `nota`, `estatus` (ACTIVO/INACTIVO).
- **Inventory** (`docs/feature-inventory.md`): every `Condominio::*` and `Condomino::*` action is "redesign", except `Condominio::pa`, which is "retire".

## Business rules already decided (MD-001, ADR-0005)

- **MD-R1:**
  - A development has a type, a name, and labels for sections and numbers.
  - The type sets defaults and labels only (AC-MD-3).
- **MD-R2:**
  - Each record belongs to exactly one development.
  - A record from another development answers 404 and reveals no metadata (AC-MD-1).
  - The super-administrator sees every development (AC-MD-2).
- **MD-R3:** a unit has:
  - an optional section, a number, and a type (apartment, house or lot);
  - optional `lot_area_m2` and `indiviso_pct`;
  - a unique key on `(development_id, section_id, number)` with NULLS NOT DISTINCT;
  - mixed types allowed inside one development (AC-MD-4).
- **MD-R4:** people are separate from units.
  - An occupancy is owner or tenant, with `[starts_on, ends_on)` half-open and history kept.
  - Exclusion constraints (`btree_gist`): the same person cannot hold overlapping occupancies of the same role on a unit, and a unit has at most one primary owner and one primary tenant at a time.
  - Co-owners and roommates are non-primary.
- **MD-R5:**
  - Each unit records its payer.
  - Changes are logged in `unit_payer_changes (unit, payer, changed_at, by)`.
  - The snapshot of the payer on charges belongs to the fees module.
- **MD-R6:** the module must answer "current occupants of a unit" (used by communications).
- **Conventions:**
  - money is `numeric(12,2)`;
  - composite foreign keys `(development_id, id)`;
  - every scoped table has an index starting with `development_id`;
  - names are in English.

## RLS implications

- **`sections`, `units`, `unit_payer_changes`:** use `Rls::scopedDomainTable()`. `unit_payer_changes` is append-only (no UPDATE or DELETE grants).
- **`occupancies`:** policies written by hand.
  - SELECT: `person_id = person_id() OR is_super() OR development_id = ANY(dev_ids())`.
  - Writes in ADR-0005 are administrator-only. D6 changes this: data_entry must be able to write too.
- **`people`:** has no INSERT policy, so new people come only from a `person_create(development, data)` definer function that also inserts the `person_developments` row.
  - D6: the function must accept administrator and data_entry.
  - Linking an existing person to another development must not become an existence oracle.
- **Resident sub-scope:** `dev_ids()` includes a resident's developments, so at the database level a resident can read every unit, occupancy and person (including RFC) of their development.
  - Layer 1 is therefore security-critical, not just UX.
  - Tests must be written first, both at the application layer and as RLS tests that document the coarse database behaviour.
- **Composite keys:**
  - `units` needs `UNIQUE (development_id, id)`.
  - Occupancies reference `(development_id, unit_id)`.
  - A `person_developments` row must exist for the occupant: enforce it with a trigger or a definer function.
- **Triggers:** new trigger functions use `Rls::definerFunction()` with a pinned search_path.

## Date conventions

- Occupancies are half-open `[starts_on, ends_on)`.
- `role_assignments.ends_on` is inclusive (`ends_on >= today`).
- Any future conversion needs one documented rule. In this change it matters only as the D7 boundary.

## Permissions

| Need | Existing | Proposal |
|---|---|---|
| See developments | `developments.view` | reuse |
| See units | `units.view` | reuse; the resident limit waits for the deferred resident lifecycle |
| Manage sections and units | none | `units.manage` (administrator, data_entry) |
| Edit or archive developments | none | super-administrator only (D1); no new permission needed |
| Manage people and occupancies, change payer | none | `people.manage` / `occupancies.manage` (administrator, data_entry), or fold into `units.manage` (design decides) |

## Size estimate

| Slice | Scope | Changed lines |
|---|---|---|
| 1 | Layer 1 behaviour, permission seed, tests | 300-400 |
| 2 | Developments create/edit/archive | 350-450 |
| 3 | Sections and units | 500-650 |
| 4 | People and definer functions | 450-600 |
| 5 | Occupancies and payer | 600-800 |

Docs updates add 30-60 lines per PR.

## Deferred

- Vehicles and `qr` (gate module).
- Logo (FILE-001).
- `saldo_inicial` and `cuota` (fees module).
- Statements (reports module).
- Legacy migration scripts.
- Guard lookup view.
- Resident invitations and lifecycle (D7).
