# feat(app): add Sections and Units tables, entities and policies under the development scope

Base branch: `develop`. Depends on PR 3a-ii (`feat/du-3a-ii-sections-units-schema`): this branch is cut from its tip `f3a5426`, so retarget to `develop` once the earlier PRs of the chain merge. Branch: `feat/du-3b-i-units-model`.

## Summary

PR 3b-i of `developments-units` (tasks 8.1 to 8.5; spec units: Unit identity, Measurements, Who may read and write; spec development-scope: scope on writes; design AD2, AD4, AD4b, AD5, W-B). It adds the ORM layer for the `sections` and `units` tables from PR 3a-ii: Table and Entity classes under layer 1 (`DevelopmentScopedBehavior`), validation that mirrors the database checks, the two rules that must run under the save scope, and the four policies. No controller, template or route yet (PR 3b-ii).

## What is included

| Area | Commits | Summary |
|---|---|---|
| Tables and entities | `feat(app): add Sections and Units tables under the development scope with validation mirroring the database` | `SectionsTable`, `UnitsTable` (scoped behavior, bulk operations trait, save-scope trait), `Section` and `Unit` entities (only editable fields assignable), `Units belongsTo Sections` on the composite key, validation, `IsUnique` and `ExistsIn(section)` rules, `seedSection()` and `seedUnit()` test helpers, `SectionsTableTest`, `UnitsTableTest`. |
| Policies | `feat(app): add unit and section policies for view, add and edit with the archived-development rule` | `UnitPolicy`, `SectionPolicy` (view, add, edit), `UnitsTablePolicy`, `SectionsTablePolicy` (index, add, scopeIndex), shared `UnitsAccess`, `UnitsPoliciesTest`. |
| Docs | `docs: record developments-units PR 3b-i` | openspec tasks 8.1 to 8.5 and implementation note, change-log, tasks, progress, test-results, this description. |

## How to review

1. `app/src/Model/Table/UnitsTable.php` (validation, rules, the composite association) and `SectionsTable.php`.
2. `app/src/Policy/UnitsAccess.php` (the shared rule), then the four thin policies.
3. Tests: `UnitsTableTest`, `SectionsTableTest`, `UnitsPoliciesTest` (the entity and table files are mostly data-provider matrices; entities are short).

## Verification

- `vendor/bin/phpunit` (run alone, real PostgreSQL): `OK (1342 tests, 7660 assertions)`; 1181 and 7360 on the 3a-ii tip. The new tests were seen failing first (classes missing), then the unique-rule tests caught that Cake's `isUnique()` puts the error on the first key column (`development_id`) instead of `name` / `number`.
- `vendor/bin/phpcs`: clean
- Migrations: none in this PR.
- Security review: self-review only. A fresh-context review is recommended before merge (authorization policies, writes under the scope). The architecture tests (bulk operations trait on every scoped table, association whitelist, raw SQL scan) stay green with the two new tables.
- Manual checks: none (no UI in this PR).
- Not verified: GitHub Actions (not pushed); behaviour through a controller and a request (PR 3b-ii); the policies against a real signed-in request (the tests open a request-style session for each actor); concurrent writes; the developer database was not migrated or touched.

## Database and row-level security

No database change. The tables, constraints and policies come from PR 3a-ii. The model layer is a second line behind them: row-level security still bounds every statement.

## Deployment and setup notes

None.

## Known limits and follow-ups

- Validation mirrors the database bounds: name at most 150 and number at most 50 characters after trimming, non-blank by the same `\S` rule, type in apartment, house, lot, optional measurements that are positive and not NaN. The numeric bounds are checked after rounding (area from 0.005 to 9999999999.99, indiviso from 0.00005 to 100), so a value that would round to zero or overflow the column is a field error and not a 500; this is slightly stricter than the database for inputs such as `100.00004`.
- The messages of the two rules are English, like the existing save-scope trait messages; PR 3b-ii maps them to Spanish copy in the UI.
- `canAdd` on `UnitPolicy` and `SectionPolicy` reads the development from the entity; the controller of PR 3b-ii must set it from the URL before authorizing, or authorize with `DevelopmentPolicy::canManageUnits` on the development. A row without a development is refused (fail closed).
- The policies read the development (with `archived_at`) under the user's own `units.manage` scope; a user who can manage units but not see the development (no such role today) would be refused.
- No delete rule on purpose: units are deactivated through `is_active`, and only the super-administrator deletes rows at the database.
- The default-deny walk has nothing new to cover (no routes); PR 3b-ii extends it.
- Size: about 323 lines of non-test code (644 with docblocks and blank lines) and about 940 test lines. Under the 400-line budget for code.

## Checklist

- [x] Tests written first and passing
- [x] Coding standard clean
- [x] `docs/change-log.md`, `docs/tasks.md`, `docs/progress.md`, `docs/test-results.md` updated (AGENTS.md)
- [x] No secrets committed (`app/config/app_local.php` stays ignored)
- [x] Legacy application at the repository root unchanged
- [ ] GitHub Actions green (not pushed)

## Chain context

Start: PR 3a-ii tip (the `sections` and `units` tables under managed RLS). End: ORM tables, entities, validation and policies for both. Prior dependency: PR 3a-ii. Follow-up: PR 3b-ii (units and sections UI). Out of scope: controllers, templates, routes, people, occupancies, the `payer` column.

```
feat/units-sdd -> ... -> 3a-ii -> [3b-i (here)] -> 3b-ii -> ... -> 5c
```
