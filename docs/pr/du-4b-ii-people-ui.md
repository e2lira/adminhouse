# feat(people): list, add and edit people under /developments/{id}/people

Base branch: `develop`. Stacked on PR 4b-i (`feat/du-4b-i-people-model`, tip `205ba5d`, itself on 4a-ii, 4a, 3b-ii, 3b-i, 3a-ii and 3a-i): this branch is cut from that tip, so retarget to `develop` once the earlier PRs of the chain merge. Branch: `feat/du-4b-ii-people-ui`. Second half of PR 4b (tasks 11.5 to 11.8).

## Summary

The people screens. `PeopleController` lists, adds and edits people of one development under `/developments/{developmentId}/people`, with Spanish templates. Reads need `people.view` (administrator, accountant, data_entry, supervisor); a resident or guard is refused with 403 and never sees RFC, email or phone. Writes need `people.manage` (administrator, data_entry) in a development that is not archived. A person is created through `PeopleTable::createFor()` (the `person_create` definer function from PR 4a); an existing person is read first, under the view scope narrowed to the development of the URL, and then saved under the manage scope (task 11.8). A person shared with a development the editor cannot fully manage is refused by the database; the screen says so with one neutral message that reveals nothing about the other development. Creating a person whose email or RFC exists elsewhere gets exactly the same answer as any other creation. There is no delete route and no route to link an existing person (AD10). No database change.

## What is included

| Area | Commits | Summary |
|---|---|---|
| People screens | `feat(people): list, add and edit people under /developments/{id}/people` | `PeopleController`, `templates/People/index.php` and `form.php`, three routes, `ResolvesDevelopmentTrait` (the development lookup `UnitsController` already had, now shared), `PeopleControllerTest` (47 tests), seven people walks in `DefaultDenyTest`. |
| Docs | `docs: record developments-units PR 4b-ii` | openspec tasks 11.5 to 11.8 with the implementation note, change-log, tasks, progress, test-results, this description. |

## How to review

1. `app/src/Controller/PeopleController.php`: the class docblock, then `add()`, `edit()`, `scopeOf()` and `personOf()`.
2. `app/tests/TestCase/Controller/PeopleControllerTest.php`: the tests with users who hold the permission in both developments (they are the ones that need layer 1), the shared-person tests and the no-oracle test.
3. `DefaultDenyTest` people walks (exact status per role and per method).
4. `ResolvesDevelopmentTrait` and the two lines that changed in `UnitsController` (its tests did not change).

## Verification

- `vendor/bin/phpunit` (run alone, real PostgreSQL): `OK (1743 tests, 10313 assertions)` (1689 and 9944 on the 4b-i tip). `PeopleControllerTest` was seen failing first (41 of 46 before the controller existed).
- `vendor/bin/phpcs`: clean on every touched file (the two existing 122-character warnings in `config/routes.php` and the `config/plugins.php` doc block are not from this branch).
- Hand mutations, each seen red and restored: entity `add` authorize skipped (1 red), entity `edit` authorize skipped (5 red), list and edit scope not narrowed to the development of the URL (3 red), table `index` authorize skipped (5 red), table `add` authorize skipped (6 red), index route also accepting POST (7 red). One equivalent mutant, on purpose: handing the view scope instead of the manage scope to `save()` is invisible at controller level because the entity policy refuses first; the model test of 4b-i pins the save-scope re-read.
- Migrations: none.
- Security review: requested before merge, together with 4a, 4a-ii and 4b-i.
- Manual checks: none in a browser. Every screen is exercised through the integration harness with real roles.
- Not verified: GitHub Actions (not pushed); rendering in a browser (layout, pagination links beyond the first page: the list is paginated at 100 like units, tested only for the first page).

## Database and row-level security

No database change. The tests run as real roles, so row-level security answers too: the layer-1 checks that need a user the database alone would let through (a user with `people.view` or `people.manage` in both developments, and a super-administrator, whose scope is every development) are the ones listed under "How to review".

## Deployment and setup notes

None.

## Known limits and follow-ups

- A shared person is shown an edit form (the application cannot see links to developments the user cannot see); the refusal comes on save, as zero rows from the database, with a neutral message. A save that changes nothing is reported as updated, since no statement runs.
- A person with an archived linked development cannot be edited by anyone but the super-administrator, and only after unarchiving (S-3, documented in ADR-0005).
- There is no detail screen and no search yet; the list is ordered by name and paginated at 100.
- The no-oracle property is by construction: people have no unique email or RFC, so there is nothing to differ on. If a uniqueness rule is ever added, this screen must be revisited.
- Size: about 380 non-test lines (controller 261, trait 37, two templates 87, routes 16, minus 21 from `UnitsController`), within budget for a split slice. About 840 test lines.

## Checklist

- [x] Tests written first and passing
- [x] Coding standard clean
- [x] `docs/change-log.md`, `docs/tasks.md`, `docs/progress.md`, `docs/test-results.md` updated (AGENTS.md)
- [x] No secrets committed (`app/config/app_local.php` stays ignored)
- [x] Legacy application at the repository root unchanged
- [ ] GitHub Actions green (not pushed)
