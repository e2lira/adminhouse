# feat(app): add the units and sections screens under the development scope

Base branch: `develop`. Depends on PR 3b-i (`feat/du-3b-i-units-model`, tip `c7e70bf`, itself stacked on 3a-ii and 3a-i): this branch is cut from that tip, so retarget to `develop` once the earlier PRs of the chain merge. Branch: `feat/du-3b-ii-units-ui`.

## Summary

PR 3b-ii of `developments-units` (tasks 9.1 to 9.5; spec units: all UI scenarios, Labels on screens, Who may read and write; spec development-scope: every type is covered for sections and units; AC-MD-1, AC-MD-3, AC-MD-4). It adds `UnitsController` (list, open, add, edit, deactivate, reactivate and inline section creation), the templates, the explicit routes under `/developments/{id}/units`, and the integration tests. While proving the database-race mapping (task 9.4) it also fixes a real defect of the save-scope trait: under row-level security PostgreSQL omits the DETAIL line of a unique or foreign key error, so the violated field was never known and a race became a 500.

## What is included

| Area | Commits | Summary |
|---|---|---|
| Constraint violations | `fix(app): attribute a unique or foreign key violation to its field when row-level security hides the detail` | `ConstraintViolation` keeps the constraint name (last quoted identifier of the primary message) and `withColumns()`; `ScopedOperationTrait` looks the columns up in the catalog for the table being saved; `ConstraintViolationTest`. |
| Units screens | `feat(app): add the units screens with inline sections under the development scope` | `UnitsController`, routes, `templates/Units/{index,view,form}.php`, `DevelopmentScopeComponent::recordId()`, `UnitsControllerTest` (87 tests), default-deny walk for every new route in six scenarios. |
| Review round 1: model | `fix(app): keep the active flag out of mass assignment and read the constraint name strictly` | `Unit.is_active` is no longer mass-assignable; the constraint name comes from the token after the word for the constraint (English or Spanish), so the referenced side of a foreign key does not name a table; `ConstraintViolationLookupTest` pins the catalog lookup (real name, another table, unknown name, failing lookup, no name). |
| Review round 1: tests and labels | `test(app): walk every unit route with the methods it does not accept`, `feat(app): use the development's own labels in unit field errors and harden the unit screen tests` | Field errors name the development's labels; same-user-both-developments 404 comparisons (status, body, headers), foreign section refused by the rule for a user of both developments, reactivation refusals, maximum and above-maximum measurements, overflowing ids, races on edit, deactivate and sections, pagination order. |
| Docs | `docs: record developments-units PR 3b-ii` and `docs: record developments-units PR 3b-ii review round 1` | openspec tasks 9.1 to 9.5 and implementation note, change-log, tasks, progress, test-results, this description. |

## How to review

1. `app/src/Controller/UnitsController.php`: the class docblock states the order of checks; then `readableDevelopment()`, `writableDevelopment()`, `unitOf()`, `persist()`.
2. `app/config/routes.php` (explicit routes, no delete) and the three templates (everything is escaped with `h()`; labels come from the development).
3. `app/src/Database/ConstraintViolation.php` and `ScopedOperationTrait::violationOf()` (small).
4. Tests: `UnitsControllerTest` (matrix per role, 404 identity, scope non-vacuity, races), the unit-route walk at the end of `DefaultDenyTest`.

## Verification

- `vendor/bin/phpunit` (run alone, real PostgreSQL): `OK (1529 tests, 8497 assertions)` after review round 1 (1499 and 8257 before it; 1403 and 7743 on the 3b-i tip). The new tests were seen failing first (80 of 83 before the controller existed).
- `vendor/bin/phpcs`: clean on every touched file (the repository still reports older warnings elsewhere).
- Migrations: none in this PR.
- Security review: self-review only so far; a fresh-context review is recommended before merge (authorization, writes under the scope, the 404 identity). Hand mutations, each seen red and restored: entity `authorize(add)` skipped; write resolved under `units.view`; the development filter removed from the row lookup; `authorize(edit)` skipped; the save scope switched to the view scope (survived until a dedicated test was added); the catalog lookup removed from the violation mapping.
- Manual checks: none in a browser; HTML was asserted in integration tests running as real roles (administrator, data_entry, accountant, supervisor, resident, read-only, super-administrator, a user of another development, a user with no assignment).
- Not verified: GitHub Actions (not pushed); a rendered browser (HTML only); two truly concurrent requests (the races are produced by a `beforeSave` listener that writes through the owner connection); the "no payer" flag, which arrives with occupancies in 5c; a lookup failure inside a real aborted transaction (unreachable by design, tested with a connection that throws); column sorting (not offered in the screen); the developer database was not migrated or touched.

## Database and row-level security

No database change. The tables, constraints and policies come from PR 3a-ii; row-level security still bounds every statement. The only SQL added is a read of `pg_constraint` and `pg_attribute` by constraint name, after a violation, to find the field.

## Deployment and setup notes

None.

## Known limits and follow-ups

- Order of checks: a user with no `units.view` anywhere (a resident, a user with no assignment) gets 403 on every unit route, whatever the id; a user with `units.view` somewhere gets the bare 404 for a foreign, missing, malformed or overflowing development or unit. Write routes use the same two steps with `units.manage`. An archived development answers 403 on every write screen (entity policy) while its units stay readable; a development archived during a save is shown as a message, not a field error.
- A foreign or missing `section_id` in the unit form is a field error with the same message for both (never a 404): the section is not addressed on its own, and the answer does not tell which ids exist. The task text said "foreign section answers 404"; this keeps the same non-disclosure without a route for sections.
- A `development_id` in a request body is dropped, not rejected; only `number`, `type`, `section_id`, `lot_area_m2` and `indiviso_pct` are read from a unit form. `is_active` changes only through the POST-only activate and deactivate actions.
- Section creation is inline (a form on the list); a refusal comes back as a message on the list after a redirect, not as a field error next to the input. There is no screen to rename a section.
- The list is paginated at 100 per page in the default order (section name, then number as text, so "10" sorts before "9"; units without a section come last). No sorting or filtering controls yet.
- Field errors are translated to Spanish in the controller; the model messages stay English.
- Write implies read: the controller relies on the seed invariant that every role holding `units.manage` also holds `units.view` (tested in the 3a-i seed tests); a future role with manage but not view would get a 403 from the table gate on edit and (de)activate, because the row is loaded under the view scope.
- Size: this PR goes with `size:exception`. About 470 lines of non-test code (about 700 with docblocks and blank lines), above the 400-line budget by roughly 15 percent, including about 60 lines for the violation fix. Left as one branch because sections are created inline from the unit screens, so the planned sections UI / units UI split would not stand alone; the violation fix is its own commit and can be moved to its own PR if the reviewer prefers. About 1000 test lines.
- Follow-up for 5c: the "no payer" flag on the list.

## Checklist

- [x] Tests written first and passing
- [x] Coding standard clean
- [x] `docs/change-log.md`, `docs/tasks.md`, `docs/progress.md`, `docs/test-results.md` updated (AGENTS.md)
- [x] No secrets committed (`app/config/app_local.php` stays ignored)
- [x] Legacy application at the repository root unchanged
- [ ] GitHub Actions green (not pushed)
