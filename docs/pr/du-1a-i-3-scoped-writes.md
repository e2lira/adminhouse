# feat(app): guard scoped writes and keep rules under the operation scope

Base branch: `develop`. Stacked on PR 2 (`feat/du-1a-i-2-scoped-reads`, itself on PR 1); retarget to `develop` once PR 2 merges. Branch: `feat/du-1a-i-3-scoped-writes`.

## Summary

PR 3 of 3 of `developments-units` PR 1a-i (tasks 1.7 to 1.14). Adds the write side of the layer-1 scope: the development of a new row comes from the scope, never from the request body; updates and deletes must stay inside the scope; the rules phase (`IsUnique`, `ExistsIn`) and nested association saves run under the save's scope. Includes the fixes from the security review for constraint-violation handling and nested scopes. No production table uses the behavior yet.

## What is included

| Area | Commits | Summary |
|---|---|---|
| Scoped writes | `feat(app): guard scoped writes and keep rules under the operation scope` | `beforeMarshal`, `beforeRules`, `beforeSave`, `beforeDelete` guards; `ScopedOperationTrait` overrides `save()`/`delete()` to publish the scope; `ConstraintViolation` maps DB `23505`/`23503` to a field error. |
| Review round 2 | `fix(app): convert a violation only when it names a column of the saving table` | A violation is converted only when the violated column exists in the saving table's schema; a violation of another table (for example from an `afterSave` hook) or one that names no column (expression index) is rethrown. The request-like test uses `DevelopmentContext`'s own session (open transaction plus savepoints). |
| Docs | `docs: record developments-units PR 1a-i` | Three PR descriptions, change-log, tasks, progress, test-results, openspec tasks and the design note. |
| Review fixes | `fix(app): convert constraint violations only where the transaction and the entity are safe` | Violations are converted only by the outermost (`ScopedSaveDepth`), atomic save that saves no associated entities, and only when no transaction is open or savepoints are enabled. saveMany without savepoints, nested child saves and caller transactions without savepoints rethrow, so the PostgreSQL transaction is never left aborted and a child's violation is never blamed on the parent. An operation scope passed to a nested operation is narrowed by the running one (`DevelopmentScope::intersect`), never widened. |

## How to review

1. `app/src/Model/Table/ScopedOperationTrait.php` (`canConvertViolations` and `operationScope` carry the fixes) and `ScopedSaveDepth.php`.
2. `app/src/Model/Behavior/DevelopmentScopedBehavior.php`: the write guards (`beforeMarshal`, `guardWrite`).
3. `app/src/Database/ConstraintViolation.php`.
4. Tests: `DevelopmentScopedBehaviorWriteTest.php` (nested child violation, saveMany with and without savepoints, caller transaction with and without savepoints, wider inner scope, expression index) and `tests/TestCase/Database/ConstraintViolationTest.php` (English and Spanish messages, expression index, previous chain, values never exposed).

## Verification

- `vendor/bin/phpunit` (run alone, real PostgreSQL): `OK (474 tests, 1688 assertions)`; the review-fix tests were seen failing first.
- `vendor/bin/phpcs`: clean (exit 0)
- Migrations: none
- Security review: a fresh-context review warned that a converted violation inside saveMany, a nested save or a caller transaction left an aborted PostgreSQL transaction and blamed a child's violation on the parent; fixed here with tests. A second fresh review approved with fixes (the request-like test did not mirror `DevelopmentContext`; a violation of another table could be attributed to the saved entity); both fixed here with tests. A third review has not been run.
- Manual checks: none
- Not verified: behavior under real RLS (lands with PR 3a); the savepoint path inside a real request (`DevelopmentContext` enables savepoints; covered here by tests that enable them explicitly).

## Database and row-level security

No database change. Same per-process test schema as PR 2.

## Deployment and setup notes

None.

## Known limits and follow-ups

- Size: 842 changed lines in `app` (about 336 in `app/src`, about 506 in tests) plus docs. Over the 400-line budget: this needs `size:exception`. Roughly 60 percent is tests; the guards, the trait and the fixes are one behavior.
- A violation inside saveMany, a nested save, an entity with associated entities, or a caller transaction without savepoints surfaces as the driver exception (so does a violation that names no column of the saved table, such as an expression index) (a 500 unless the caller handles it): that is the price of never poisoning the transaction. Request code under `DevelopmentContext` has savepoints, so a plain `save()` there still returns a field error.
- Through-mode tables (people) have no write guard because they have no development column; their creation path must read the entity under a scope first (documented in the behavior docblock and in design AD2).
- Bulk `updateAll`/`deleteAll`/`query()->update()/delete()` bypass the entity-level guards; they land in PR 1a-ii (W-C).
- Follow-up: PR 1a-ii (`ScopedBulkOperations`, architecture tests), PR 1b (component, `DevelopmentsTable` retrofit).

## Chain context

Start: PR 2 (scoped reads). End: behavior, stack, trait and exceptions available, unused by production code. Prior dependencies: PR 1 and PR 2. Follow-up: 1a-ii. Out of scope: bulk operations trait, architecture tests, component, any production table.

```
feat/units-sdd -> PR 1 scope stack -> PR 2 scoped reads -> [PR 3 (here)] -> 1a-ii -> 1b -> ... -> 5c
```

## Checklist

- [x] Tests written first and passing
- [x] Coding standard clean
- [x] `docs/change-log.md`, `docs/tasks.md`, `docs/progress.md`, `docs/test-results.md` updated (AGENTS.md)
- [x] No secrets committed (`app/config/app_local.php` stays ignored)
- [x] Legacy application at the repository root unchanged
- [ ] GitHub Actions green (not pushed)
