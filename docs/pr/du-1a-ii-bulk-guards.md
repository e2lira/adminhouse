# feat(app): refuse bulk statements on scoped tables and guard the scope with architecture tests

Base branch: `develop`. Stacked on PR 1a-i-3 (`feat/du-1a-i-3-scoped-writes`, itself on PR 1a-i-2 and 1a-i-1); retarget to `develop` once that chain merges. Branch: `feat/du-1a-ii-bulk-guards`.

## Summary

PR 1a-ii of `developments-units` (tasks 2.1 to 2.7). A bulk statement has no entity and fires no find event, so the layer-1 behavior cannot scope it. This PR makes scoped tables refuse `updateAll()`, `deleteAll()`, `updateQuery()` and `deleteQuery()`, and adds three static guards so the rest of the scope cannot be bypassed by accident: the trait is required on every scoped table, associations entering a scoped table are safe or listed with a reason, and no raw SQL or manual join under `src` names a scoped table. No production table uses the behavior yet.

## What is included

| Area | Commits | Summary |
|---|---|---|
| Bulk operations | `feat(app): refuse bulk statements on scoped tables` | `ScopedBulkOperationsTrait` throws `LogicException` (naming the table) from the four bulk methods. The ORM builds the statement of a single-row `save()` and `delete()` through `updateQuery()`/`deleteQuery()`, so those two let through only a direct call from `Cake\ORM\Table::_update` / `_processDelete`. A dependent `hasMany` with `cascadeCallbacks` false cascades with `deleteAll()` and is refused (tested); scoped dependents must set `cascadeCallbacks` true, which deletes row by row under the operation scope (tested). |
| Architecture tests | `test(app): guard bulk trait, association entries and raw SQL on scoped tables` | `ScopedTableAudit` (test support) plus `ScopedTableArchitectureTest`, with fixture tables and source snippets proving each check fails on a violation. |
| Review round 1 | `fix(app): keep single-row scoped statements inside the scope and guard insertQuery`, `test(app): harden the scope audits (behavior alias, overridden guards, raw SQL gaps, discovery)` | The statements the ORM builds for a single-row `save()`/`delete()` carry `<development column> IN (scope ids)` (skipped for an all-development scope and for through-mode tables, which have no column); `DevelopmentScopedBehavior` rejects an existing entity whose primary key is dirty (save and delete); `insertQuery()` is refused like the other entry points; the ORM-caller check also requires the call to come from this very table (`DEBUG_BACKTRACE_PROVIDE_OBJECT`); a test pins that `Cake\ORM\Table` still declares `_update`, `_processDelete` and `_insert`. Audits: the behavior is detected by class under any alias; `updateAll`, `deleteAll`, `insertQuery`, `updateQuery`, `deleteQuery` must resolve to the trait's own code (an override fails); the raw-SQL scan also covers `->into/table/update/delete/insert('<name>'` and connection helpers, and scans `src`, `config` and `plugins` (not `config/Migrations`, not `src/Database`); `load()` fails when the locator holds another class for the alias; every `*Table.php` under `src/Model/Table` (subdirectories included) must resolve to a class. |
| Docs | `docs: record developments-units PR 1a-ii` | This description, change-log, tasks, progress, test-results, openspec tasks. |

## How to review

1. `app/src/Model/Table/ScopedBulkOperationsTrait.php`: the only production code (105 lines, mostly docblocks). Check `calledByTheOrm` (backtrace frame 2 must be `Cake\ORM\Table::_update` or `_processDelete`).
2. `app/tests/TestCase/Model/Table/ScopedBulkOperationsTest.php`.
3. `app/tests/Support/Architecture/ScopedTableAudit.php` and `app/tests/TestCase/Architecture/ScopedTableArchitectureTest.php`. The association whitelist lives in the test, each entry with a reason; it must be consistent with the runtime rule of AD2 (an unscoped parent's access to a scoped table throws unless a scope is passed explicitly).
4. Test-support tables now use the trait; `RealmsTable` declares `bindingKey => 'id'` explicitly so the audit does not need schema reflection.

## Verification

- `vendor/bin/phpunit` (run alone, real PostgreSQL): `OK (505 tests, 1783 assertions)` after review round 1; new tests (initial and review) were seen failing first.
- `vendor/bin/phpcs`: clean (exit 0)
- Migrations: none
- Manual checks: the raw-SQL scanner run against the real `src` with `developments` reported the one existing query (`RoleAssignmentManager`), so the pass with real scoped names is not a broken scanner.
- Not verified: behavior under real RLS (lands with PR 3a).

## Database and row-level security

No database change.

## Deployment and setup notes

None.

## Known limits and follow-ups

- Size: 1,279 changed lines in `app` (about 210 in `app/src`, the rest tests and test support) plus docs. Over the 400-line budget (estimate was ~200): needs `size:exception`; about 83 percent is tests and fixtures that prove each guard fails.
- Residual risk: string-built SQL that assembles a scoped table name from pieces or interpolates it (`FROM {$table}`) escapes the scan; it is covered only by row-level security.
- Known limit of the audits: associations added to a table at runtime, after `initialize()`, are not seen.
- Review round 1 also changed `DevelopmentScopedBehavior` (primary-key guard, `guardedColumn()`), since 1a-i is already merged into the stacked branches.
- `updateQuery()`/`deleteQuery()` are guarded by inspecting the direct caller; if a future CakePHP version changes how `save()`/`delete()` build their statements, single-row writes fail loudly (never silently open), and the guard must be updated.
- The scan is dynamic: it uses the table names of scoped Table classes found in `src/Model/Table`, so it passes vacuously today. When PR 1b scopes `DevelopmentsTable`, it will flag `SELECT 1 FROM developments` in `src/Authorization/RoleAssignmentManager.php`; PR 1b must replace that query or add a reasoned exception.
- `UsersTable hasMany RoleAssignments` needs no whitelist entry because `RoleAssignmentsTable` is not scoped (AD3); a test pins that, so scoping it later makes the association fail as unlisted.
- Other bulk-capable ORM paths that call `updateAll` on the target (for example `CounterCacheBehavior`) are refused on scoped targets too.

## Chain context

Start: scope behavior, stack and save/delete trait available, unused by production. End: bulk refused and the scope guarded by architecture tests, still unused by production tables. Prior dependency: PR 1a-i. Follow-up: PR 1b (scope component and `DevelopmentsTable` retrofit). Out of scope: component, any production table.

```
feat/units-sdd -> 1a-i-1 -> 1a-i-2 -> 1a-i-3 -> [1a-ii (here)] -> 1b -> ... -> 5c
```

## Checklist

- [x] Tests written first and passing
- [x] Coding standard clean
- [x] `docs/change-log.md`, `docs/tasks.md`, `docs/progress.md`, `docs/test-results.md` updated (AGENTS.md)
- [x] No secrets committed (`app/config/app_local.php` stays ignored)
- [x] Legacy application at the repository root unchanged
- [ ] GitHub Actions green (not pushed)
