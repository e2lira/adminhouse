# Design: Developments and Units

## Technical Approach

Stacked PRs to `develop` (table below), each test-first. Layer 1 (`DevelopmentScopedBehavior`) lands first; layer 2 adds one SQL helper, `permission_dev_ids(code)`, so database write rights follow the permission catalogue (D6). UI copy stays Spanish (existing templates); code, SQL and identifiers are English. Binding inputs: D1-D8 and AD13 (user-confirmed 2026-11-18).

## Architecture Decisions

| # | Decision | Rejected | Rationale |
|---|---|---|---|
| AD1 | Scope travels **explicitly** as option `developmentScope` (`DevelopmentScope`). Primary find, save or delete without it throws `MissingDevelopmentScopeException` (LogicException, 500). | Ambient static scope | No global state; per-permission (ADR-0005 alt. 3); testable. |
| AD2 | Associations: `beforeFind` on a contained query has `primary = false` and **does not receive the parent's options**. Nothing is inherited implicitly (revised after the PR 1a-i security review: the target's own declarations say nothing about who the parent of a query is). A non-primary query on a scoped table always needs an explicit `developmentScope`; a scoped parent supplies it, as that explicit option and narrowed to the parent's scope, to the contained associations where (a) the source table is itself scoped and (b) the foreign key equates the development columns of both tables (`(development_id, x_id)`, or `id` to `development_id` for developments). Plain foreign keys, many-to-many, `matching()`/`joinWith()` and unscoped parents need an explicit scope or throw. Through-mode tables have no development column to guard on writes and rely on the scoped read of the entity. Through-mode targets (`People`) always need an explicit scope, else throw. Whitelist rule: **an association may enter a scoped table only from a scoped table**; an architecture test walks every Table class's associations and fails on violations or unlisted entries (`UsersTable hasMany RoleAssignments`, UsersTable.php:36, is legal because `RoleAssignments` is not behavior-scoped, AD3). | Require scope on every contain | Composite FKs make inheritance sound; the test makes it enforceable. |
| AD3 | Config `field` (default `development_id`); `DevelopmentsTable` uses `field => 'id'`; `PeopleTable` uses `through => person_developments`. **Retrofit only existing Table classes over development-scoped data**: `DevelopmentsTable` only. Excluded: `RoleAssignmentsTable` (it is the source AccessControl reads to compute scope, circular; super rows have NULL `development_id`; reads already bounded by `managedDevelopment` conditions + RLS); `person_developments` (no Table class; any future one must use the behavior). | Scope everything | Avoids circularity; honest about NULL rows. |
| AD4 | Writes: `beforeMarshal` throws `DevelopmentScopeViolation` when the body carries a `development_id` different from the scope target; the column is not accessible; save option `developmentId` sets it on new entities; dirty `development_id` on update or target outside scope throws (controller maps to 404). Inserting a `developments` row requires `isAll()`. Trigger `forbid_development_id_change()` attached to every new scoped table. | Trust controller | ADR-0005 layer 1 contract. |
| AD4b | **Rules phase**: `Table::exists()` (ORM/Table.php:1990) runs primary finds from `IsUnique`/`ExistsIn` before `beforeSave`. The behavior handles `Model.beforeRules` (requires `developmentScope` in save options, stashes it as the operation scope) and clears it in `Model.afterRules`; primary finds with no option use the stash only while it is set. DB `23505`/`23503` raised anyway (races) are mapped to a field error. | Map DB errors only | Rules give field-level messages; stash is bounded to one operation. |
| AD4c | **Bulk/raw bypass**: scoped tables use trait `ScopedBulkOperations` whose `updateAll()`/`deleteAll()` throw LogicException. Raw SQL or manual `->join()` onto scoped tables is forbidden outside `app/config/Migrations` and `App\Database`; an architecture test scans `app/src` for scoped table names in raw SQL. Residual risk (string-built SQL the scan misses) is covered only by RLS. | Allow and review | Behavior cannot hook bulk ops. |
| AD5 | `DevelopmentScopeComponent::for(permission)` (memoized) and `development(id, permission)` (404 otherwise). Default deny requires a policy per resource: `UnitsTablePolicy`, `SectionsTablePolicy`, `PeopleTablePolicy`, `OccupanciesTablePolicy`; entity policies `UnitPolicy`, `SectionPolicy`, `PersonPolicy`, `OccupancyPolicy`; `DevelopmentPolicy` gains `canEdit`, `canArchive`, `canManageUnits/People/Occupancies` (false when archived). | Scope in controllers | Mirrors `RoleAssignmentsController::managedDevelopment`. |
| AD6 | Permissions seeded in the slice that checks them. `developments.manage` has no role rows (super only, D1). Every seed migration's `down()` deletes only its own codes/rows (unlike the catalogue seed, 20261118000001:75). | Hard-coded super check | One mechanism. |
| AD7 | `permission_dev_ids(p_code text) bigint[]`: definer, STABLE, **request context only** (empty in jobs, so jobs are read-only on new tables), active assignment whose role holds the code; non-`.view` codes need a writable assignment and a non-archived development. `Rls::managedDomainTable($table, $code)` validates `^[a-z_]+\.[a-z_]+$`, then SELECT = `is_super OR development_id = ANY(dev_ids())`; INSERT/UPDATE = `is_super OR development_id = ANY(permission_dev_ids(code))`; DELETE = `is_super` only (D4 deactivates; D2 archives). Column UPDATE grants exclude `id`, `development_id` (and `payer`, AD11). | Role names in SQL | D6 maps to the catalogue. |
| AD8 (ADR deviation) | `person_create` and occupancy writes use `permission_dev_ids('people.manage'/'occupancies.manage')` instead of `admin_dev_ids()`. People UPDATE becomes `is_super OR id = person_id() OR person_in_permission_scope(id, 'people.manage')`, which mirrors `person_in_admin_scope` (links exist AND every linked development is in scope). `PeopleTable` refuses `save()` of new entities; creation only via `PeopleTable::createFor()` calling `person_create`. Note in ADR-0005. | Admin-only | D6 (create AND edit for data_entry). |
| AD9 | `occupancies.person_id` has **no** plain FK; only composite `(person_id, development_id) → person_developments (person_id, development_id)` (UNIQUE exists, 20261117000002:26). FK checks bypass RLS, so safety comes from the INSERT policy forcing `development_id` into `permission_dev_ids`: the FK only tests links of in-scope developments, which the caller already sees. Nonexistent and foreign person ids fail the same constraint; the app maps every 23503 on occupancies to one generic message. | Auto-link trigger | Auto-linking would let id guessing pull foreign people in. |
| AD10 | **No linking of existing people in this change** (`person_link` removed). Shared people come from the migration stage (D8). | Link action | Scope creep and an existence oracle risk. |
| AD11 | `units.payer` is a role (`owner`/`tenant`). Changed only by definer `unit_set_payer(p_unit_id, p_payer)` checking `is_super` or the unit's development in `permission_dev_ids('occupancies.manage')` (same error for missing/foreign unit). Runtime role has no UPDATE on `payer`. Definer trigger (AFTER INSERT; AFTER UPDATE OF payer) appends `unit_payer_changes`; runtime role has SELECT only; UPDATE/DELETE rejected by trigger for every role. | App writes history | Unforgeable; payer rights tied to occupancies explicitly. |
| AD12 | `btree_gist` created by superuser in `bin/provision_db_roles.php`; the migration checks `pg_extension` and throws a clear `RuntimeException`. | Extension in migration | Provisioning owns extensions. |
| AD13 (**user-confirmed 2026-11-18**) | Resident loses `units.view` (revoke migration, symmetric `down()`), re-granted with the resident sub-scope in the deferred lifecycle change (D7). `people.view` excludes resident. Update the catalogue seed comment and ADR-0005 note. | Ship sub-scope now | RB-R8 fail-closed. |

## Permissions

| Code | Roles | PR |
|---|---|---|
| `developments.manage` | none (super) | 2a |
| `units.manage`; revoke resident `units.view` | administrator, data_entry | 3a-i |
| `people.view` | administrator, accountant, data_entry, supervisor | 4a |
| `people.manage` | administrator, data_entry | 4a |
| `occupancies.manage` | administrator, data_entry | 5a |

Each PR updates `AccessControlTest::roleMatrix` (tests/TestCase/Authorization/AccessControlTest.php:150-180).

## Schema

- **developments** (2a): `address, phone, rfc, bank_name, bank_account text NULL`, `archived_at timestamp NULL`. No delete route; RLS unchanged (super may DELETE in SQL; sections/units FKs block non-empty ones; `person_developments` cascades). Unarchive: super only. Coarse model: `bank_account` is SQL-readable by any role in the development.
- **sections** (3a-ii): `id, development_id NOT NULL REFERENCES developments, name text NOT NULL CHECK (btrim(name) <> '')`, timestamps; `UNIQUE (development_id, id)`, `UNIQUE (development_id, name)`.
- **units** (3a-ii): `id, development_id, section_id NULL, number text NOT NULL CHECK (btrim <> ''), type CHECK IN ('apartment','house','lot'), lot_area_m2 numeric(12,2) NULL CHECK (> 0), indiviso_pct numeric(7,4) NULL CHECK (> 0 AND <= 100), is_active boolean DEFAULT true`, timestamps; `UNIQUE (development_id, id)`; FK `(development_id, section_id) → sections`; `UNIQUE NULLS NOT DISTINCT (development_id, section_id, number)`. 5b adds `payer text NOT NULL DEFAULT 'owner' CHECK IN ('owner','tenant')`.
- **occupancies** (5a): `id, development_id, unit_id, person_id, role CHECK IN ('owner','tenant'), is_primary, starts_on, ends_on NULL`, timestamps; `CHECK (ends_on IS NULL OR ends_on > starts_on)`; FK `(development_id, unit_id) → units`; FK `(person_id, development_id) → person_developments` RESTRICT; two gist exclusions (same person/role overlap; one primary per role). Grants `UPDATE (ends_on, modified)` only; trigger allows `ends_on` NULL → date only. Corrections: super deletes and staff recreate. SELECT omits `person_id = person_id()` (D7).
- **unit_payer_changes** (5b): `development_id, unit_id, payer, changed_at, changed_by_user_id NULL`; backfill one row per unit.

## Queries

- `OccupanciesTable::findCurrent(on)`: `daterange(starts_on, ends_on, '[)') @> :on`, `:on` from `BusinessClock`. No `HalfOpenPeriod` class (belongs to D7).
- Responsible person = current primary occupancy whose `role = units.payer`; `UnitsTable::findWithoutPayer()` = active units with none.

## Data Flow

    Controller -> DevelopmentScopeComponent::for(code) -> AccessControl::allowedFor -> DevelopmentScope
         -> Table::find/save(developmentScope) -> DevelopmentScopedBehavior (WHERE | throw)
         -> PostgreSQL RLS (dev_ids / permission_dev_ids / is_super)

## PRs and File Changes

| PR | Content | Est. lines |
|---|---|---|
| 1a | `app/src/Model/Behavior/DevelopmentScopedBehavior.php`, `ScopedBulkOperations` trait, `MissingDevelopmentScopeException`, `DevelopmentScopeViolation`; unit + architecture tests | ~380 |
| 1b | `DevelopmentScopeComponent`; `DevelopmentsTable` retrofit; `DevelopmentsTablePolicy::scopeIndex` sets option; `RoleAssignmentsController::managedDevelopment` | ~250 |
| 2a | Migration fields/archived_at + `developments.manage`; validation; `DevelopmentPolicy::canEdit/canArchive`; matrix test | ~300 |
| 2b | `DevelopmentsController` add/edit/archive/unarchive, templates, routes, integration tests | ~380 |
| 3a-i | `permission_dev_ids`, `Rls::managedDomainTable`, `Rls::permissionDevIds()`, `units.manage` seed, resident revoke, matrix + parity tests, `dataEntryA` in `RlsTestCase`, seed comment (20261118000001), ADR-0005 AD13 note | ~350 |
| 3a-ii | sections/units migration + RLS tests | ~400 |
| 3b-i | `SectionsTable`, `UnitsTable`, entities, table/entity policies, tests | ~350 |
| 3b-ii | `UnitsController` (sections inline), templates with labels, routes, integration tests | ~380 |
| 4a | `person_in_permission_scope`, `person_create` body, people UPDATE policy, seeds, RLS tests, ADR-0005 note | ~380 |
| 4b | `PeopleTable` (through), `PersonPolicy`, `PeopleTablePolicy`, `PeopleController` (index/add/edit), templates | ~400 |
| 5a | Provisioning `btree_gist`, occupancies migration, `occupancies.manage`, RLS tests | ~400 |
| 5b | payer column, `unit_set_payer`, `unit_payer_changes`, triggers, RLS tests | ~350 |
| 5c | `OccupanciesTable`, `UnitPayerChangesTable`, finders, policies, `OccupanciesController` (add, end), `UnitsController::payer`, no-payer flag | ~400 |

Routes explicit, nested `/developments/{developmentId}/...`, `[0-9]+`.

## Testing Strategy

| Layer | What |
|---|---|
| Unit | Throw without scope; `isAll` no filter; empty scope `1 = 0`; beforeMarshal mismatch; rules-phase stash; bulk ops throw; association whitelist |
| RLS | Actors super, adminA, dataEntryA, accountantA, resident, adminB, job: per command; DELETE super only; exclusions (23P01); nonexistent vs foreign person id → same constraint/error; people edit of a person shared with B refused for dataEntryA; `unit_set_payer` allow/deny; log append-only; parity `permission_dev_ids` vs `allowedFor` across role × read_only × window |
| Integration | AC-MD-1..4, D1, D2, archived writes refused, resident-only refused, DefaultDeny walk |

## Migration / Rollout

Additive, reversible; `down()` restores previous `person_create` and people policy. Re-run `php bin/provision_db_roles.php` before 5a.

## Open Questions

None. AD13 and archived-development writes (refused; unarchive super only) are closed.

## Design amendments

### A1 (2026-11-19, user decision "Si, por permiso"): managed-domain reads follow the read permission

Amends AD7 (and AD13's consequence). The AD7 row above says SELECT on a managed domain table is
`is_super OR development_id = ANY(dev_ids())`. That is superseded:

- `Rls::managedDomainTable($table, $readCode, $writeCode, $updatableColumns, $insertOnlyColumns = [])` takes a read
  code that MUST end in `.view` and a write code that MUST NOT; it throws otherwise (this also closes the risk that a
  `.view` code is passed as the write code).
- SELECT = `is_super OR development_id = ANY(permission_dev_ids(readCode))`. INSERT and UPDATE use the write code.
  DELETE stays `is_super` only.
- Consequences: a resident (no `units.view` since AD13) and a guard that hold an assignment in a development read no
  row of a managed table at the database level, not only in layer 1. A read-only assignment still reads (`.view`
  codes count it). A job reads nothing from a managed table (`permission_dev_ids` is request-only), so any future job
  that needs such rows needs its own mechanism (a definer function or an explicit job policy), decided with that job.
- Write implies read: PostgreSQL applies the SELECT policy to `INSERT ... RETURNING` (the CakePHP PostgreSQL driver
  appends `RETURNING *` to every insert and takes the new id from it) and to the WHERE of an UPDATE. A role that holds
  the write code without the read code can only run a bare INSERT. Seeds must therefore give every role that holds a
  managed write code (`units.manage`) the matching read code (`units.view`); `AccessControlTest` enforces it.
- Table contract recorded in the builder: NOT NULL `development_id`; primary key generated by the database (the
  runtime role never inserts or updates `id`); INSERT is granted on `development_id` plus the updatable and the
  insert-only columns (chosen over a plain INSERT grant so an id or any later column cannot be set by accident);
  UPDATE only on the listed columns, which must include `modified` and never `created`. No sequence privilege is
  granted: new ids come back through `RETURNING`, as for the other scoped tables.
- References between managed tables use composite foreign keys `(development_id, x_id)` onto a unique
  `(development_id, id)`, and unique indexes are scoped per development. Foreign-key and unique checks bypass RLS, so
  a plain `x_id` reference or a global unique index would let a caller probe whether a row exists in a development
  they cannot read.
- Resident sub-scope (D7, deferred lifecycle change): it must NOT widen access by adding a PERMISSIVE policy next to
  these, because permissive policies are OR-ed and would reopen the table to every assignment. Narrowing (a resident
  sees only their own units) should be a RESTRICTIVE policy, or a change of the read predicate, so the permission
  check keeps applying.

### A2 (2026-11-19, user decision "Si, hace el 4a-ii"): people reads follow `people.view`

Extends A1 to `people` and `person_developments`, which are not managed-domain tables (no `development_id` on people). Superseded: people SELECT `own row OR is_super OR a link to a development in dev_ids()`.

- `people` SELECT = `id = person_id() OR is_super() OR EXISTS (link to a development in permission_dev_ids('people.view'))`.
- `person_developments` SELECT = `development_id = ANY(permission_dev_ids('people.view')) OR person_id = person_id() OR is_super()`. Left on `dev_ids()` a resident could enumerate person ids and links; the people policy's EXISTS also runs under this policy, so the two stay consistent.
- Consequences: resident and guard read only their own person and links; a read-only assignment and an archived development still read (`.view` code); a job reads no person (`permission_dev_ids` is request-only; no production job reads people today, a future one needs its own mechanism); write implies read holds because `people.manage` is seeded only with `people.view`; the gate is `AccessControlTest` (every role holding a managed write code also holds its read code), which fails if a seed ever breaks the pair.
- Migration `20261123000002_PeopleReadByPermission` replaces the two policies; `down()` restores the `dev_ids()` ones verbatim; `PermissionMigrationsRollbackTest` pins both states. UPDATE, DELETE, grants and functions are unchanged.
- PR 4b: layer 1 people reads use `people.view` (already the plan, tasks 11.3 and 11.5); now the database agrees.

### A3 (review round 1 of PR 4b-i): intended edge cases of people edits (AD7, AD8)

Not changes of behavior: two outcomes of the people UPDATE policy that a reviewer may find surprising, now pinned by tests in `PeopleTableTest`.

- Own row of a shared person (AD8): the update policy is `is_super OR id = person_id() OR person_in_permission_scope(id, 'people.manage')`. The own-row branch does not look at the person's links, so a user whose own person is linked to a development they do not manage can still edit that person, as the spec "Editing people" says ("the person themself"). The layer-1 read still has to reach the person under the user's scope (`testAUserMayEditTheirOwnSharedPerson`).
- Super-administrator and an archived linked development (AD7): `permission_dev_ids` drops an archived development for every write code, which is why no editor can change a person linked to one (S-3). The super-administrator is not narrowed by `permission_dev_ids` (`is_super` short-circuits), so they can edit such a person; this matches AD7, where they are the only actor who can also unarchive (`testTheSuperAdministratorEditsAPersonLinkedToAnArchivedDevelopment`). The people screens still refuse writes in an archived development of the URL to everyone, the super-administrator included (W-E, entity policy).
- Deleting: `PeopleTable::delete()` throws `LogicException`; no route exists and the database grants DELETE to the super-administrator only, for maintenance.
- Text fields: name, email, phone and RFC reject control characters (NUL, bell, line breaks) and bytes that are not UTF-8 as field errors; otherwise PostgreSQL would answer with an error and the request with a 500.

### A4 (review round 1 of PR 4b-ii): what a refused people edit reveals (AD10)

Accepted, not changed. A person shared with a development the editor cannot manage (or linked to an archived one) is shown an edit form, and the save is refused with one generic message ("No tienes permiso para modificar los datos de esta persona."). The refusal tells the editor that this person cannot be changed by them, so it hints that some link they cannot see exists; it never says which development, how many links or why. The alternative (hiding the edit action) would need the application to read links the database deliberately hides, which is worse. The message is the same for an archived linked development and for a shared person, and the 404 for a person that is not linked to the development of the URL stays identical to a missing one.

### A5 (PR 5a, orchestrator decision consistent with A1 and A2; user to confirm or override): occupancy reads follow `people.view`

Amends task 12.6 (and the Schema row of occupancies), which said SELECT via `dev_ids()`. An occupancy says who lives in which unit, which is personal data; the user decided twice (A1 `units.view`, A2 `people.view`) that database reads follow the view permission, not any assignment. So:

- `occupancies` SELECT = `is_super() OR development_id = ANY(permission_dev_ids('people.view'))`. Still WITHOUT a `person_id = person_id()` clause (D7: the resident sub-scope is deferred and must narrow with a RESTRICTIVE policy, see A1).
- INSERT and UPDATE = `occupancies.manage` through `permission_dev_ids`; DELETE = super only. `Rls::managedDomainTable('occupancies', 'people.view', 'occupancies.manage', ['ends_on', 'modified'], [...])` expresses all of it: its read and write codes are independent (only the `.view` suffix rule applies), so no hand-written policy was needed.
- Write implies read: every role holding `occupancies.manage` (administrator, data_entry) also holds `people.view`; `AccessControlTest::managedWritePairs` now carries the `occupancies.manage` to `people.view` pair.
- Consequences: a resident or guard reads no occupancy at the database level, not even the one of their own person; a read-only assignment of a role with `people.view` reads; a job reads nothing (request-only). No role has `units.view` without `people.view` today, so the spec line "reading requires `units.view`" (layer 1, PR 5c) loses nothing; the screens may still check `units.view` for the unit and `people.view` for the occupant names.
- If the user prefers a different read code (for example `units.view`), the change is one argument of the builder in `20261124000001_CreateOccupancies` and the pair in `managedWritePairs`.
- Review round 1 (W1): both exclusion constraints start with `development_id WITH =`. They run before the composite foreign key, so a key on the global `unit_id` alone answered 23P01 for a unit of another development that had a conflicting occupancy and 23503 for one that had not. Now a unit of another development gives the same `occupancies_unit_fkey` answer as a missing unit.
- Accepted as designed (review S1, S2): `ends_on` has no bound beyond being after `starts_on` (an end in the past is valid, so an occupancy can be ended retroactively); a mistaken same-day row is corrected by the super-administrator, who deletes it and staff recapture. Setting `ends_on` to the value it already has is accepted and changes nothing (an idempotent re-submit); only a different value is refused.

### A6 (PR 5b, orchestrator decision consistent with A1; user to confirm or override): payer history reads follow `units.view`

Fills a gap in AD11 (and task 13.5), which say who writes the history but not who reads it. The spec "Who may read and write" requires `units.view` to read occupancy data, so there is no deviation from the spec; the plan's habit of `dev_ids()` is not used (A1).

- `unit_payer_changes` SELECT = `is_super() OR development_id = ANY(permission_dev_ids('units.view'))`. The payer is a role (owner or tenant) and the row names no person, so it is unit data and follows the unit read permission (A1), not `people.view`. INSERT, UPDATE and DELETE have no policy and the runtime role holds no privilege for them: only the definer trigger writes, and a trigger rejects UPDATE and DELETE for every login.
- Write implies read, adapted: `unit_set_payer` is a definer function, so the RLS pair rule does not apply to it, but whoever changes the payer should see the log. `AccessControlTest::managedWritePairs` carries the pair `occupancies.manage` to `units.view` ("payer history").
- Consequences: a resident or guard reads no history row; a read-only assignment of a role with `units.view` reads; a job reads nothing (`permission_dev_ids` is request-only); the super-administrator reads everything. If the user prefers another read code, the change is one argument in `20261125000001_AddUnitPayerAndHistory` and the pair above.
- `unit_set_payer`: super or the unit's development in `permission_dev_ids('occupancies.manage')`. A missing unit, a unit of another development and a caller without the right all raise the same SQLSTATE 42501 with the same message (no id in it); the payer is validated after that check (22023), so a bad payer reveals nothing about a unit either. Setting the current value again is a no-op (no row touched, no history row). Like every managed write, `permission_dev_ids` drops an archived development for staff, while the super-administrator is not narrowed at the database level (A3); layer 1 (PR 5c) refuses it for everyone.
- Accepted as designed: (1) every unit now has at least one history row, and the foreign key is `ON DELETE RESTRICT`, so no unit can be deleted, not even by the super-administrator (SQLSTATE 23001); units are deactivated (D4). Two earlier tests that deleted a unit were updated. (2) Rolling the migration back drops `units.payer`, so stored payers are lost; going forward again backfills one row per unit with the default `owner`, no user, and the unit's `created` time. (3) `changed_by_user_id` is NULL outside a request (migration, job, owner login); it comes from `session_user_id()`, which trusts the request-scoped setting like every policy. (4) The owner of the table could still drop the trigger or the table; no application login can. (5) The append-only trigger is statement level, so even an UPDATE or DELETE that matches no row is refused; TRUNCATE is not covered by it (no application role holds it), and the test cleanup relies on that.
