# feat(app): add scope exceptions and the shared operation scope stack

Base branch: `develop`. Stacked on `feat/units-sdd` (the openspec artifacts), which merges first. Branch: `feat/du-1a-i-1-scope-stack`.

## Summary

PR 1 of 3 of `developments-units` PR 1a-i (tasks 1.1, 1.2, and the stack part of 1.11). Adds the two scope exceptions and `DevelopmentScopeStack`, the shared, exception-safe stack that publishes the scope of the operation that is running (AD4b, reviewer items W-A and W-B). Nothing uses it yet; the behavior that consumes it lands in the next PR.

## What is included

| Area | Commits | Summary |
|---|---|---|
| Exceptions | `feat(app): add scope exceptions and the shared operation scope stack` | `MissingDevelopmentScopeException` (LogicException, names the table) and `DevelopmentScopeViolation` (fixed message, no row data). |
| Scope stack | same commit | `DevelopmentScopeStack::run()` pushes and pops in `try/finally`, so a rule or listener that throws can never leave a scope behind for the next, unrelated find (W-A). |

## How to review

1. `app/src/Authorization/DevelopmentScopeStack.php` (small; the `finally` is the W-A guarantee).
2. `app/src/Model/Exception/*`.
3. Tests: `app/tests/TestCase/Authorization/DevelopmentScopeStackTest.php`, `app/tests/TestCase/Model/Exception/DevelopmentScopeExceptionsTest.php`.

## Verification

- `vendor/bin/phpunit` (run alone, real PostgreSQL): green on this branch when it was written (new tests were seen failing first). Not re-run at this tip after the review fixes, which only touch PRs 2 and 3.
- `vendor/bin/phpcs`: clean
- Migrations: none
- Security review: authorization code. The fresh-context security review of the whole slice found nothing in the stack itself; its findings are fixed in PRs 2 and 3.
- Manual checks: none
- Not verified: behavior under real RLS (lands with PR 3a).

## Database and row-level security

No database change.

## Deployment and setup notes

None.

## Known limits and follow-ups

- Size: 207 changed lines in 5 files (about 100 in `app/src`, 106 in tests). Within the 400-line budget.
- The stack is ambient: a scoped find with no explicit scope that runs while an operation is in progress uses the operation's scope. This is intended (AD4b) and is why the stack is popped in `finally`.
- The stack accepts any scope an operation passes. The rule that an inner scope may only narrow the running one is enforced in PR 3 (`ScopedOperationTrait`), where nested operations originate.
- Bulk `updateAll`/`deleteAll`/`query()->update()/delete()` bypass the entity-level guards; they are covered in PR 1a-ii (W-C).

## Chain context

Start: `feat/units-sdd`. End: exceptions and stack available, unused by production code. Prior dependencies: none. Follow-up: PR 2. Out of scope: the behavior, writes, bulk operations, any production table.

```
feat/units-sdd -> [PR 1 (here)] -> PR 2 scoped reads -> PR 3 scoped writes -> 1a-ii -> 1b -> ... -> 5c
```

## Checklist

- [x] Tests written first and passing
- [x] Coding standard clean
- [x] `docs/change-log.md`, `docs/tasks.md`, `docs/progress.md`, `docs/test-results.md` updated (AGENTS.md, in PR 3)
- [x] No secrets committed (`app/config/app_local.php` stays ignored)
- [x] Legacy application at the repository root unchanged
- [ ] GitHub Actions green (not pushed)
