# feat(db): let people.manage create and edit people through permission scope

Base branch: `develop`. Depends on PR 3b-ii (`feat/du-3b-ii-units-ui`, tip `20b85bb`, itself stacked on 3b-i, 3a-ii and 3a-i): this branch is cut from that tip, so retarget to `develop` once the earlier PRs of the chain merge. Branch: `feat/du-4a-people-db`.

## Summary

PR 4a of `developments-units` (tasks 10.1 to 10.8; spec people: Create person through definer function, Editing people, Database enforcement; design AD8, S-3, S-4). The database now decides who creates and edits people from the permission catalogue instead of "administrator only": `data_entry` can create and edit, an accountant, a supervisor and a read-only assignment cannot, and a person shared with a development the editor does not manage (or that is archived) cannot be edited. No table or column changes. No PHP application code changes (the model and screens come in PR 4b).

## What is included

| Area | Commits | Summary |
|---|---|---|
| Migration `20261123000001_PeoplePermissionScope` | `feat(db): let people.manage create and edit people through permission scope` | Seeds `people.view` and `people.manage` first (S-4); adds the definer function `person_in_permission_scope(person, code)`; replaces the `person_create` body (same signature) to check `permission_dev_ids('people.manage')`; replaces the people UPDATE policy. `down()` restores the previous body and policy and deletes only its own codes. |
| Tests | same commit | `RlsPeopleTest` (42 tests), role matrix and write-implies-read pair in `AccessControlTest`, rollback test extended. |
| Docs | `docs: record developments-units PR 4a` | ADR-0005 notes 6 and 7, openspec tasks 10.1 to 10.8, change-log, tasks, progress, test-results, this description. |

## How to review

1. ADR-0005 notes 6 and 7 (what changes and why), then the class docblock of the migration.
2. `app/config/Migrations/20261123000001_PeoplePermissionScope.php`: the seed block, `person_in_permission_scope`, `definePersonCreate()`, the policy swap and `down()`.
3. `app/tests/TestCase/Database/Rls/RlsPeopleTest.php`: create per actor, the no-oracle comparison, the archived cases, the scope function, UPDATE per actor.

## Verification

- `vendor/bin/phpunit` (run alone, real PostgreSQL): `OK (1584 tests, 9688 assertions)` after review round 1 (1579 and 9631 before it; 1529 and 8497 on the 3b-ii tip). The new tests were seen failing first (8 in `AccessControlTest`, 14 of 42 in `RlsPeopleTest` before the migration).
- `vendor/bin/phpcs`: clean on every touched file.
- Migrations: rolled back and re-applied by `PermissionMigrationsRollbackTest` (matrix, codes, which helper `person_create` and the UPDATE policy call, presence of the new function) and by `bin/cake migrations rollback -c test_migrations` on the test database. Review round 1: the rollback test now compares the exact function definition, owner, config and privileges and the policy USING and WITH CHECK before and after `down()` and after the second `up()`. The developer database was not migrated.
- Security review: self-review only so far. **A fresh-context security review of the two definer functions and the policy is requested before merge.** Hand mutations, each seen red and restored: ANY instead of EVERY linked development in `person_in_permission_scope`; `person_create` on `dev_ids()`; EXECUTE on `person_create` granted to PUBLIC; the UPDATE policy left on `person_in_admin_scope`; the write code read as the view code (an archived linked development no longer blocks).
- Manual checks: none in a browser (no screen changes).
- Not verified: GitHub Actions (not pushed); the application layer (arrives in 4b); `bin/provision_db_roles.php` is not needed and was not re-run; the people SELECT policy (unchanged, see below).

## Database and row-level security

Database change: two permission seeds, one new definer function, one replaced definer function body, one replaced policy.

- [x] New tables use `App\Database\RowLevelSecurity` helpers: no new table. The existing `people` table keeps its INSERT-less policies; only `people_update` is replaced.
- [x] Policy tests added under `app/tests/TestCase/Database/Rls` (`RlsPeopleTest`: administrator, data_entry, accountant, supervisor, guard, resident, read-only, disabled, ended and future assignments, a user with no assignment, no session, a job, a user of another development, the super-administrator).
- [x] New `SECURITY DEFINER` functions owned by `kualitet_definer`, `search_path` pinned, no `PUBLIC` execute (asserted from `pg_proc` for both functions).

Details worth a reviewer's attention:

- `person_in_permission_scope(person, code)` is true only when the person has at least one link and every linked development is in `permission_dev_ids(code)`. For a write code an archived development is not in that list, so a person shared with an archived development cannot be edited by `administrator` or `data_entry` (S-3, documented in ADR-0005 note 7). Zero links, an unknown person and an unknown code are false. The super-administrator is not listed (the policy adds `is_super()`); a job gets false (request-only).
- `person_create` keeps its signature. A development the caller does not manage, one that does not exist and a NULL id raise the same `insufficient_privilege` error with the same message (tested by comparing them), so the call is not an existence oracle. A NULL name fails the NOT NULL check and leaves nothing behind (person and link are one statement).
- The function can be called with any person id; its answer for a person the caller cannot see is the same `false` as for an unknown id, and a `true` implies the caller already sees the person.

## Deployment and setup notes

Run `bin/cake migrations migrate -c migrations`. No provisioning change.

## Review round 1

No criticals. Reliability gaps fixed test-first (all behavior was already correct): exact rollback-state comparison, a real mid-function atomicity failure, visibility asserted before every denied UPDATE, 0-row, SQLSTATE and message assertions, super-administrator cases, cross-development refusals for data_entry and adminB. `person_in_permission_scope` now calls `permission_dev_ids` once per call. Mutations red and restored are in `docs/test-results.md`. The people SELECT question below is untouched, waiting for the user.

## Known limits and follow-ups

- **Decision for the user: people reads still follow `dev_ids()`, not `people.view`.** The people SELECT policy (migration `20261119000003`) is unchanged: any person linked to a development where the session has any assignment is readable at the database level, including by a guard or a resident, and including the RFC, email and phone columns. The design (A1) moved only managed-domain tables to permission-based reads, and tasks 10.x do not touch the people SELECT policy, so this PR does not either. The spec says a resident-only user must be refused people reads, which today is enforced in layer 1 only (4b policies), not in the database. Recommendation: a small follow-up migration (before 4b merges, or as 4a-ii) that sets the people SELECT policy to `is_super OR id = person_id() OR EXISTS (link to a development in permission_dev_ids('people.view'))`. It needs care because the UPDATE policy's WHERE and `person_create`'s `RETURNING` run under SELECT (the seeds already pair `people.manage` with `people.view`, tested), jobs would stop reading people (`permission_dev_ids` is request-only; the existing tenancy test reads people from a job context and would have to change), and `users`/`role_assignments` screens that join people would need checking.
- `people.view` is seeded for supervisor and accountant as the spec says; until the SELECT decision above, the seed only matters to layer 1.
- Own-row and staff edits of contact data: a person edits their own row, RFC included, and `people.manage` (data_entry too) edits the person record of an administrator linked only to its developments. Neither grants any login (authentication uses `users`), but a future email-based reset must not trust `people.email` (ADR-0005 note 9).
- `people.view` has no database effect yet (ADR-0005 note 8): same open decision as above.
- The super-administrator can still create people in an archived development at the database level (the spec is silent; layer 1 `canManagePeople` refuses it). Pinned by a test.
- Size: about 160 lines in the migration, no other non-test code. About 650 test lines. Within budget.

## Checklist

- [x] Tests written first and passing
- [x] Coding standard clean
- [x] `docs/change-log.md`, `docs/tasks.md`, `docs/progress.md`, `docs/test-results.md` updated (AGENTS.md)
- [x] No secrets committed (`app/config/app_local.php` stays ignored)
- [x] Legacy application at the repository root unchanged
- [ ] GitHub Actions green (not pushed)
