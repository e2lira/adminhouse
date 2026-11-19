# feat(app): scope DevelopmentsTable by id and add DevelopmentScopeComponent

Base branch: `develop`. Depends on PR 1a-ii (`feat/du-1a-ii-bulk-guards`, itself stacked on the 1a-i chain): this branch is cut from its tip, so retarget to `develop` once that chain merges. Branch: `feat/du-1b-scope-component`.

## Summary

PR 1b of `developments-units` (tasks 3.1 to 3.7, AD3 and AD5). It adds `DevelopmentScopeComponent`, the controller-side way to obtain the scope of the signed-in user, and it makes `DevelopmentsTable` the first production table to use `DevelopmentScopedBehavior` (`field => id`, the development's own id is the scope column). A development outside the user's scope and one that does not exist now answer the same 404 (AC-MD-1); the existing developments list keeps working (AC-MD-2).

## What is included

| Area | Commits | Summary |
|---|---|---|
| Scope component | `feat(app): add DevelopmentScopeComponent to resolve the scope and answer one 404` | `for(permission)` asks `AccessControl::allowedFor` once per permission and request (memoized) and is empty without an identity. `development(id, permission)` reads the id through the scoped `Developments` table under that scope and throws `NotFoundException` otherwise, so out-of-scope and nonexistent ids, super-administrators included, get the same exception. |
| Retrofit | `feat(app): scope DevelopmentsTable by id and route role-assignment lookups through the scope` | `DevelopmentsTable` adds the behavior (`field => id`) and `ScopedBulkOperationsTrait`. `DevelopmentsTablePolicy::scopeIndex` now sets the `developmentScope` option instead of adding its own WHERE, so the table enforces it. `RoleAssignmentsController::managedDevelopment` resolves the id through the component and reads the row under the same scope. `RoleAssignmentManager::requireExistingDevelopment` no longer runs `SELECT 1 FROM developments`: it reads through the scoped table with the scope the actor already proved in `requireManage`. |
| Docs | `docs: record developments-units PR 1b` | This description, change-log, tasks, progress, test-results, openspec tasks. |

## How to review

1. `app/src/Controller/Component/DevelopmentScopeComponent.php` and its test.
2. `app/src/Model/Table/DevelopmentsTable.php` and `app/src/Policy/DevelopmentsTablePolicy.php`; `app/tests/TestCase/Model/Table/DevelopmentsTableTest.php` runs inside a super-administrator session so row-level security hides nothing and every filtered row is the scope's doing.
3. `app/src/Controller/RoleAssignmentsController.php::managedDevelopment` and `app/src/Authorization/RoleAssignmentManager.php::requireExistingDevelopment`.
4. The architecture test now also pins that `DevelopmentsTable` is scoped, uses the bulk trait and that no association enters it.

## Verification

- `vendor/bin/phpunit` (run alone, real PostgreSQL): `OK (526 tests, 1819 assertions)`; the new tests were seen failing first, and the existing role-assignment controller tests and the raw-SQL architecture test failed once the table was scoped, until the callers were updated.
- `vendor/bin/phpcs`: clean (exit 0)
- Migrations: none
- Not verified: no manual check in a browser; behavior under real RLS beyond what the integration tests already exercise (RLS stays unchanged and still bounds every query).

## Database and row-level security

No database change.

## Deployment and setup notes

None.

## Known limits and follow-ups

- Size: 539 changed lines in `app` (147 in `app/src`, the rest tests) against a ~250 estimate: needs `size:exception`.
- Raw-SQL audit: the 1a-ii scan flagged `SELECT 1 FROM developments` in `RoleAssignmentManager`. It is fixed in code (the query now goes through the scoped table), not whitelisted, and the audit is unchanged.
- `RoleAssignmentsTable` is deliberately not scoped (AD3): it is the source `AccessControl` reads to compute the scope, and super-administrator rows have a NULL development. No association enters `developments`, so the association whitelist did not change.
- `RoleAssignmentManager` builds a `DevelopmentsTable` directly on its own connection so an injected connection is honored; the controller uses the table locator.
- `development()` costs one query per call; `managedDevelopment` then reads the entity (a second query). Acceptable for these low-traffic screens; a later change can return the entity if it matters.
- Saving a development through the ORM now requires an all-development operation scope (behavior rule for `field => id`); nothing saves developments through the ORM yet (PR 2a/2b will).

## Chain context

Start: bulk guards and architecture tests in place, no production table scoped. End: `DevelopmentsTable` scoped and the component available for every later controller. Prior dependency: PR 1a-ii. Follow-up: PR 2a (development profile fields, archive state, policies).

```
feat/units-sdd -> 1a-i-1 -> 1a-i-2 -> 1a-i-3 -> 1a-ii -> [1b (here)] -> 2a -> ... -> 5c
```

## Checklist

- [x] Tests written first and passing
- [x] Coding standard clean
- [x] `docs/change-log.md`, `docs/tasks.md`, `docs/progress.md`, `docs/test-results.md` updated (AGENTS.md)
- [x] No secrets committed (`app/config/app_local.php` stays ignored)
- [x] Legacy application at the repository root unchanged
- [ ] GitHub Actions green (not pushed)
