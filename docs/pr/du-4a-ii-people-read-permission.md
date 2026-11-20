# feat(db): read people through people.view instead of any assignment

Base branch: `develop`. Stacked on PR 4a (`feat/du-4a-people-db`, tip `271ff89`, itself on 3b-ii, 3b-i, 3a-ii and 3a-i): this branch is cut from that tip, so retarget to `develop` once the earlier PRs of the chain merge. Branch: `feat/du-4a-ii-people-read-permission`.

## Summary

New slice 4a-ii of `developments-units`, inserted by a user decision (2026-11-19) that PR 4a left open (design amendment A2, tasks 10.9 to 10.12). The people SELECT policy and the `person_developments` SELECT policy now follow `permission_dev_ids('people.view')` instead of `dev_ids()`. Before, any assignment in a development (a resident, a guard) could read every person linked to it, RFC, email and phone included, and every person-development link. Now only holders of `people.view` (administrator, accountant, data_entry, supervisor, a read-only assignment included) read them; everyone else reads only their own person and its links. No table, column, grant or function changes. No PHP application code changes.

## What is included

| Area | Commits | Summary |
|---|---|---|
| Migration `20261123000002_PeopleReadByPermission` | `feat(db): read people through people.view instead of any assignment` | Replaces `people_select` and `person_developments_select`. `down()` restores the `dev_ids()` policies verbatim. |
| Tests | same commit | New `RlsPeopleReadTest` (25 tests); job and guard/resident expectations updated in `RlsTenancyPoliciesTest` and `RlsPeopleTest`; exact policy snapshot (qual, with_check, cmd, roles) in `PermissionMigrationsRollbackTest`. |
| Docs | `docs: record developments-units PR 4a-ii` | ADR-0005 notes 6 and 8, design amendment A2, openspec tasks 10.9 to 10.12, change-log (scope change), tasks, progress, test-results, this description. |

## How to review

1. ADR-0005 note 8 and design A2, then the docblock of the migration.
2. `app/config/Migrations/20261123000002_PeopleReadByPermission.php` (one private method, called with the two id lists).
3. `app/tests/TestCase/Database/Rls/RlsPeopleReadTest.php`: per-actor reads, each with a person of another development that must stay hidden.

## Verification

- `vendor/bin/phpunit` (run alone, real PostgreSQL): `OK (1609 tests, 9759 assertions)` (1584 and 9688 on the 4a tip). The new tests were seen failing first (8 of the 600 `Database/Rls` tests before the migration).
- `vendor/bin/phpcs`: clean on every touched file.
- Migrations: `PermissionMigrationsRollbackTest` rolls back and re-applies, comparing the exact USING, WITH CHECK, command and roles of both policies before and after `down()` and after the second `up()`; `bin/cake migrations rollback -c test_migrations` was also run by hand. The developer database was not migrated.
- Hand mutations, each seen red and restored: both policies back on `dev_ids()` (9 red); `person_developments` alone on `dev_ids()` (3 red); own-row branch dropped from `people` (6 red); own-row branch dropped from `person_developments` (4 red); `down()` leaving the new policies (rollback test red). One equivalent mutant, on purpose: only `people_select` back on `dev_ids()` stays green, because PostgreSQL applies the `person_developments` policy inside the people policy's EXISTS, so the link policy is the effective gate and the people predicate is defence in depth.
- Not verified: GitHub Actions (not pushed); any browser flow (no screen reads people yet, that is PR 4b); a security review (requested before merge, together with 4a).

## Database and row-level security

Database change: policy replacement (two SELECT policies). No new table, function or grant.

- [x] New tables use `App\Database\RowLevelSecurity` helpers: no new table; the policies are built with `Rls::policy`, `Rls::permissionDevIds`, `Rls::DEV_IDS`, `Rls::IS_SUPER` and `Rls::PERSON_ID`.
- [x] Policy tests added under `app/tests/TestCase/Database/Rls` (`RlsPeopleReadTest`: administrator, data_entry, accountant, supervisor, read-only holder, guard, resident, disabled, ended and future assignments, a user with no assignment, administrator of another development, shared person, archived development, a job, the super-administrator, no session).
- [x] No new `SECURITY DEFINER` function.

Details worth a reviewer's attention:

- Write implies read: `person_create`'s RETURNING and the UPDATE policy's WHERE run under the new SELECT policy. `people.manage` is only seeded together with `people.view` (`AccessControlTest`), and the full 4a people tests still pass.
- View codes keep archived developments (`permission_dev_ids`), so people of an archived development stay readable; writes there are still refused.
- Existing definer functions (`auth_*`, `person_create`, `person_in_permission_scope`, `person_in_admin_scope`, `person_id()`) run as the owner and bypass RLS, so they behave as before. `users`, `role_assignments` and `auth_events` policies do not read `people` and are unchanged.

## Job impact

`permission_dev_ids` is request-only, so a job context now reads no person at all (before: the people of its developments). The only reader of people in a job context was `RlsTenancyPoliciesTest` (expectation updated). No production code (commands, jobs, services) reads `people` today; PR 4b will add request-time screens only. A future job that needs people needs its own mechanism (a definer function or an explicit job policy), decided with that job (ADR-0005 note 8).

## Deployment and setup notes

Run `bin/cake migrations migrate -c migrations`. No provisioning change.

## Known limits and follow-ups

- The layer-1 people policies and screens (PR 4b) must use `people.view`; the database now agrees with them.
- Own-row and staff edits of contact data stay as in ADR-0005 note 9.
- Size: about 50 lines in the migration, no other non-test code. About 380 test lines (new test file plus snapshot). Within budget.

## Checklist

- [x] Tests written first and passing
- [x] Coding standard clean
- [x] `docs/change-log.md`, `docs/tasks.md`, `docs/progress.md`, `docs/test-results.md` updated (AGENTS.md)
- [x] No secrets committed (`app/config/app_local.php` stays ignored)
- [x] Legacy application at the repository root unchanged
- [ ] GitHub Actions green (not pushed)
