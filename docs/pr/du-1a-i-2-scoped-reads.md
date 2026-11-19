# feat(app): add DevelopmentScopedBehavior with fail-closed scoped reads

Base branch: `develop`. Stacked on PR 1 (`feat/du-1a-i-1-scope-stack`); retarget to `develop` once PR 1 merges. Branch: `feat/du-1a-i-2-scoped-reads`.

## Summary

PR 2 of 3 of `developments-units` PR 1a-i (tasks 1.3 to 1.6). Adds the read side of the layer-1 scope (ADR-0005): a primary find on a scoped table without a scope throws, a scope filters by the development column, and the scope is handed to contained associations explicitly, never inherited implicitly. Includes the fixes from the security review: inheritance is a property of the association actually loaded, not of the target table's declarations. No production table uses the behavior yet.

## What is included

| Area | Commits | Summary |
|---|---|---|
| Scoped reads | `feat(app): add DevelopmentScopedBehavior with fail-closed scoped reads` | `beforeFind` with config `field` and `through`; an all-development scope adds no filter, an empty scope yields `1 = 0`; test-only scoped tables and `ScopedTableTestCase`. |
| Review fixes | `fix(app): make scope inheritance explicit per association and isolate scoped test tables` | A non-primary query always needs an explicit `developmentScope`. A scoped parent hands its scope to the contained associations whose foreign key links the two development columns (`development_id` to `development_id`, or `id` to `development_id`) and whose target is scoped and not through-mode; an explicit scope already on the contained query is kept but narrowed to the parent's (`DevelopmentScope::intersect`). Plain foreign keys, many-to-many, through-mode tables, `matching()`/`joinWith()` and unscoped parents need an explicit scope in their query builder or throw. Through-mode tables with a composite primary key are rejected. Test tables move to a per-process schema dropped in `finally`; aliases get a `Scoped` prefix. |
| Review round 2 | `fix(app): never let an explicit scope widen the running one and link string foreign keys` | An explicit `developmentScope` on a find now narrows the running operation scope (`DevelopmentScopeStack`) instead of replacing it. Foreign and binding keys are normalized to arrays, so a hasMany with the plain string `development_id` (developments to units) is recognized. Architecture test: the all-development scope is only built in `src/Authorization`. |

## How to review

1. `app/src/Model/Behavior/DevelopmentScopedBehavior.php`: `beforeFind`, then `propagateScope`, `collectLinked`, `linksDevelopment`. The class docblock states the whole inheritance contract, including that through-mode tables (people) have no write guard and rely on the scoped read of the entity.
2. `app/src/Authorization/DevelopmentScope.php` (`intersect`, 18 lines).
3. Tests: `app/tests/TestCase/Model/Behavior/DevelopmentScopedBehaviorReadTest.php` (unscoped parent through contain, matching and innerJoinWith; plain foreign key; join and select strategies; hasMany select; nested contain; explicit scope narrowed, never widened, also against a running operation scope; string foreign key; many-to-many), `tests/TestCase/Architecture/DevelopmentScopeConstructionTest.php`, `DevelopmentScopeIntersectionTest.php`.
4. `app/tests/Support/ScopedTableTestCase.php` and `app/tests/Support/Scoped/*` are test-only support and need a skim.

## Verification

- `vendor/bin/phpunit` (run alone, real PostgreSQL): `OK (442 tests, 1591 assertions)`; the new read tests were seen failing first (the unscoped-parent cases and the plain-foreign-key case returned rows from every development before the fix).
- `vendor/bin/phpcs`: clean (exit 0)
- Migrations: none
- Security review: a fresh-context review found a critical hole (an unscoped parent reaching a composite-capable table through contain, matching or innerJoinWith returned every development) and a test-isolation warning; both are fixed here with tests. A second fresh review approved with fixes (string foreign keys not recognized; an explicit scope could widen a running operation scope); both fixed here with tests, and the accepted trust model is pinned by tests (an unscoped parent's contain builder is trusted to pass its own scope, server code only; `DevelopmentScope::all()` is only built in `src/Authorization`). A third review has not been run.
- Manual checks: none
- Not verified: behavior under real RLS (the test tables have none; lands with PR 3a); the behavior against real production associations (none use it yet; the architecture test in PR 1a-ii enforces the whitelist).

## Database and row-level security

No database change. The behavior tests create the `scoped_*` tables per test, with the owner login, in a schema named `scoped_test_<pid>` that is first on the `search_path` of both test connections and is dropped (CASCADE) in a `finally`. Nothing is created in `public`, parallel runs cannot collide, and no production table is touched. A TEMPORARY table is not usable because the runtime role is another connection.

## Deployment and setup notes

None.

## Known limits and follow-ups

- Size: 962 changed lines in 13 files (about 236 in `app/src`, about 726 in tests and test support). Over the 400-line budget: this needs `size:exception`. Roughly three quarters is tests and test-only tables, and the review fixes cannot be separated from the behavior they correct without leaving a known-unsafe commit in the history.
- `matching()`, `innerJoinWith()`, `leftJoinWith()` and many-to-many contains from a scoped parent do not receive the scope automatically; callers pass `developmentScope` in the query builder. This is deliberate (fail closed) and can be relaxed later per association.
- Through-mode tables have no write guard (no development column); their creation path must read the entity under a scope first.
- Bulk `updateAll`/`deleteAll`/`query()->update()/delete()` bypass the entity-level guards; they land in PR 1a-ii (W-C).
- Follow-up: PR 3 (writes), PR 1a-ii, PR 1b (component, `DevelopmentsTable` retrofit).

## Chain context

Start: PR 1 (exceptions and stack). End: scoped reads available, unused by production code. Prior dependencies: PR 1. Follow-up: PR 3. Out of scope: writes, bulk operations, any production table.

```
feat/units-sdd -> PR 1 scope stack -> [PR 2 (here)] -> PR 3 scoped writes -> 1a-ii -> 1b -> ... -> 5c
```

## Checklist

- [x] Tests written first and passing
- [x] Coding standard clean
- [x] `docs/change-log.md`, `docs/tasks.md`, `docs/progress.md`, `docs/test-results.md` updated (AGENTS.md, in PR 3)
- [x] No secrets committed (`app/config/app_local.php` stays ignored)
- [x] Legacy application at the repository root unchanged
- [ ] GitHub Actions green (not pushed)
