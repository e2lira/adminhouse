# Proposal: Developments and Units

## Intent

Module 2 of the ADR-0006 rebuild. Fees, charges and payments cannot exist until a development can be managed and its units, people and occupancies are captured. Today the CakePHP 5 app has `developments`, `people` and `person_developments`, but no sections, units, occupancies or payer history, no application-scope layer (ADR-0005 layer 1), and no way for staff to create people. Residents can read every neighbour's `people` row (including RFC) at the database level, so layer 1 must exist before resident-facing data grows.

## Scope

### In Scope
- `DevelopmentScopedBehavior` (layer 1): permission-aware scope on every scoped query, `development_id` set/validated on writes, throw when no scope is active.
- Developments: super-administrator create/edit/archive (D1, D2); new nullable fields address, phone, rfc, bank_name, bank_account (D3).
- Sections and units: section + number unique per development, unit type, `lot_area_m2`, `indiviso_pct`, active/inactive flag (D4); units may have no owner, UI flags "no payer" (D5).
- People: creation via `person_create` definer function and editing, by administrator or data_entry (D6); editing a person shared with another development requires `people.manage` in every linked development.
- Occupancies (owner/tenant, primary flag, half-open periods, `btree_gist` exclusion constraints) and unit payer role (`owner`/`tenant`, set through `unit_set_payer`) with `unit_payer_changes` history; writable by administrator and data_entry (D6).
- New permissions seeded (`developments.manage`, `units.manage`, `people.view`, `people.manage`, `occupancies.manage`); RLS policies and tests for every new table and definer function.
- Resident role loses `units.view` until the resident lifecycle (AD13, user-confirmed 2026-11-18).

### Out of Scope
- Resident login creation and the Resident assignment lifecycle tied to occupancies (D7; former PR 6, waits for invitations/communications).
- Legacy tenant data and any `condomino` migration (D8; migration stage). Linking an existing person to another development (shared people come from the migration).
- Development logo (FILE-001), settings jsonb (D3).
- Fees, charges, payer snapshot on charges, receipts and statements (later modules).
- Administrator editing of developments; RLS on `developments` unchanged (D1).
- Legacy CakePHP 3.9 code at the repository root.

## Capabilities

### New Capabilities
- `development-scope`: layer 1 application scope for development-owned tables.
- `development-management`: super-administrator lifecycle and profile fields of developments.
- `units`: sections and units within a development.
- `people`: staff capture of people linked to a development.
- `occupancies`: owner/tenant periods and the unit's responsible payer.

### Modified Capabilities
None (`openspec/specs/` is empty).

## Approach

Vertical slices, each split into PRs of about 400 changed lines or less (see design), merged to `develop` in order (stacked-to-develop), test-first, with RLS tests under `app/tests/TestCase/Database/Rls`. Permissions are seeded in the slice that checks them.

| # | Slice | Decisions |
|---|---|---|
| 1 | Foundation: `DevelopmentScopedBehavior`, retrofit of `DevelopmentsTable` | security base |
| 2 | Developments management | D1, D2, D3 |
| 3 | Sections and units | D4, D5 |
| 4 | People (`person_create`, policy update for data_entry) | D6 |
| 5 | Occupancies and payer (`btree_gist`, exclusion constraints, `unit_payer_changes`) | D5, D6, D7 boundary |

## Affected Areas

| Area | Impact | Description |
|---|---|---|
| `app/config/Migrations/` | New | sections, units, occupancies, unit_payer_changes, development fields, definer functions, permission seeds |
| `app/src/Model/Behavior/` | New | `DevelopmentScopedBehavior` |
| `app/src/Model/Table/`, `app/src/Policy/`, `app/src/Controller/` | New/Modified | tables, policies and screens per slice |
| `app/src/Database/RowLevelSecurity.php`, `app/src/Authorization/` | Modified | helpers, scope, permissions |
| `app/bin/provision_db_roles.php` | Modified | provision `btree_gist` (CI already runs this script) |
| `docs/adr/ADR-0005-multi-development-rbac-design.md` | Modified | implementation notes: D6 deviation, AD13 |
| `app/tests/TestCase/Database/Rls/` | New | policy tests |

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| Layer 1 bug exposes resident/neighbour data (RLS cannot sub-scope residents) | High impact | Slice 1 treated as security-critical; cross-development and resident tests per table; fresh security review |
| `btree_gist` missing in provision script or CI | Med | Add `CREATE EXTENSION` to the provisioning script (CI runs it) in slice 5; migration fails loudly if absent |
| Half-open `[starts_on, ends_on)` vs inclusive `role_assignments.ends_on` | Med | Boundary-day tests on `findCurrent`; the conversion point belongs to the deferred resident lifecycle (D7) |
| New definer functions widen privileges | Med | Owner `kualitet_definer`, fixed `search_path`, `EXECUTE` only to `kualitet_app`, RLS tests per function |
| data_entry write policies broader than intended | Low | Explicit allow/deny tests per role |

## Rollback Plan

Each slice is one PR with reversible migrations: revert the PR on `develop` and run `bin/cake migrations rollback` to the previous version. Slices are additive (new tables, nullable columns, new functions); no existing data is rewritten. Slice 1 can be reverted only together with later slices that depend on it.

## Dependencies

- RLS-SEC-001 foundation (migrations `20261119000001`-`03`).
- PostgreSQL 18 with `btree_gist` available.

## Success Criteria

- [ ] AC-MD-1: a user with a role only in development A gets "not found" for developments, sections, units, people and occupancies of B (automated tests per type).
- [ ] AC-MD-2: super-administrator opens every development's records, including one created after their account.
- [ ] AC-MD-3 (screens part): "Block"/"Lot" labels appear on unit screens; stored data identical to "Street"/"Number".
- [ ] AC-MD-4 (units part): one development holds a house, an apartment and a lot.
- [ ] MD-R4/MD-R5 data: occupancies keep history; payer changes are recorded in `unit_payer_changes`.
- [ ] Querying a scoped table without an active scope throws.
- [ ] Full `phpunit` and `phpcs` green in CI per slice.
