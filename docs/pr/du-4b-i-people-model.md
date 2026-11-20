# feat(people): scope people through their links and authorize them by permission

Base branch: `develop`. Stacked on PR 4a-ii (`feat/du-4a-ii-people-read-permission`, tip `a2c34a5`, itself on 4a, 3b-ii, 3b-i, 3a-ii and 3a-i): this branch is cut from that tip, so retarget to `develop` once the earlier PRs of the chain merge. Branch: `feat/du-4b-i-people-model`. First half of PR 4b (fallback split of the task: 4b-i model and policies, 4b-ii controller and screens).

## Summary

The application layer for people, without a screen yet (tasks 11.1 to 11.4, and the model half of 11.8). `PeopleTable` is scoped through `person_developments`, refuses bulk statements and the ORM insert, and creates people only through `createFor()`, which calls the `person_create` definer function from PR 4a. The behavior of layer 1 cannot guard a write to a through-mode table (a person has no development column), so `PeopleTable::save()` closes the gap itself: an existing person is saved only if that table read it under a scope, its key is unchanged and the scope of the save still reaches it (the S2 carry-over from the PR 1a-i review). `PeopleTablePolicy` and `PersonPolicy` follow the units pattern: reads need `people.view`, writes need `people.manage` and a non-archived development, nothing deletes, a resident-only user is refused. No database change.

## What is included

| Area | Commits | Summary |
|---|---|---|
| People model | `feat(people): scope people through their links and create them only through person_create` | `PeopleTable`, `Person`, `createFor()`, the read-before-write guard, validation, `PeopleTableTest` (26 tests), one architecture test, test-only table renamed `ScopedPeopleTable`, `createPerson()` moved to `DatabaseTestCase`. |
| People policies | `feat(people): authorize people reads with people.view and writes with people.manage` | `PeopleTablePolicy`, `PersonPolicy`, `PeopleAccess`, and the new abstract `DomainAccess` that `UnitsAccess` now extends (same public API); `PeoplePoliciesTest` (47 tests). |
| Docs | `docs: record developments-units PR 4b-i` | openspec tasks 11.1 to 11.4 and the split, change-log (plan split), tasks, progress, test-results, this description. |

## How to review

1. `app/src/Model/Table/PeopleTable.php`: the class docblock, then `createFor()` and `save()`.
2. `app/tests/TestCase/Model/Table/PeopleTableTest.php`: the "writes: read first" section.
3. `app/src/Policy/DomainAccess.php` (moved out of `UnitsAccess`, which keeps only its codes and `developmentOf()`), then `PersonPolicy`.
4. `app/tests/TestCase/Policy/PeoplePoliciesTest.php`.

## Verification

- `vendor/bin/phpunit` (run alone, real PostgreSQL): `OK (1689 tests, 9944 assertions)` (1615 and 9776 on the 4a-ii tip). Both new test files were seen failing first (classes missing).
- `vendor/bin/phpcs`: clean on `src` and `tests`.
- Hand mutations, each seen red and restored: read check removed (2 red), key-changed and scope re-read removed (2 red), key-changed check alone removed (1 red: a key redirected to a neighbour of the same scope), re-read threshold changed (1 red), new-entity guard removed (1 red), `createFor` scope check removed (1 red); policies: edit judged with the view check, archived check removed, table scope handed as the manage scope, view code replaced by the manage code, development id not normalized (all red).
- Migrations: none.
- Security review: requested before merge, together with 4a and 4a-ii (the guard that replaces the behavior's missing write guard for through-mode tables is the piece to look at).
- Not verified: GitHub Actions (not pushed); any browser flow (no screen yet, that is PR 4b-ii).

## Database and row-level security

No database change. `person_create`, `person_in_permission_scope` and the people policies come from PR 4a and 4a-ii; this PR only calls them (the tests run as real roles, so the database answers too: accountant, supervisor, resident and a read-only administrator are refused by `person_create` and the refusal is the same exception as a development outside the scope; a person shared with a development the editor does not manage matches no row on update).

## Deployment and setup notes

None.

## Known limits and follow-ups

- The "person has no development column" problem is solved with `Person::inDevelopment($id)` (the controller states the development from the route; a person with none stated is refused everything). 4b-ii must call it before authorizing.
- Whether a person may be edited at all (a shared person) is decided by the database; the application cannot see links to developments the user cannot see, and says nothing about them (4b-ii shows a neutral refusal).
- Size: about 580 non-test lines with docblocks (`PeopleTable` 218, `DomainAccess` 123, policies 175, `Person` 57, `UnitsAccess` reduced by 75); well over the 400-line budget, which is why the task was split. About 560 test lines.

## Checklist

- [x] Tests written first and passing
- [x] Coding standard clean
- [x] `docs/change-log.md`, `docs/tasks.md`, `docs/progress.md`, `docs/test-results.md` updated (AGENTS.md)
- [x] No secrets committed (`app/config/app_local.php` stays ignored)
- [x] Legacy application at the repository root unchanged
- [ ] GitHub Actions green (not pushed)
