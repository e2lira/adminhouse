# feat(app): development management screens for the super-administrator

Base branch: `develop`. Depends on PR 2a (`feat/du-2a-development-fields`): this branch is cut from its tip, so retarget to `develop` once that PR merges. Branch: `feat/du-2b-development-management`.

## Summary

PR 2b of `developments-units` (tasks 5.1 to 5.6, AD5 and W-E). The super-administrator can add, edit, archive and restore developments from the application; nobody else can, and nobody can delete one. It also adds the archived-development rule (W-E) as policy methods that later PRs (units, people, occupancies) will call: writes are refused in an archived development, the super-administrator included.

## What is included

| Area | Commits | Summary |
|---|---|---|
| Archived-write policy (W-E) | `feat(app): refuse writes to units, people and occupancies of an archived development` | `DevelopmentPolicy::canManageUnits`, `canManagePeople`, `canManageOccupancies` (permissions `units.manage`, `people.manage`, `occupancies.manage`) return false when the development is archived, for the super-administrator too (they unarchive first); an entity loaded without `archived_at` is refused (fail closed). Tests: archived refuses a permission holder and the super-administrator, unarchived allows a holder, a user without the permission is refused, each ability asks for its own code, a real super-administrator is allowed until the development is archived. |
| Management screens | `feat(app): let the super-administrator add, edit, archive and restore developments` | `DevelopmentsController` `add`, `edit`, `archive`, `unarchive`; explicit routes (`/developments/add`, `/developments/{id}/edit`, `/archive`, `/unarchive`, ids `[0-9]+`, archive and unarchive POST only, no delete route); Spanish form (`form.php`) and list; time zone defaults to `America/Mexico_City` on the add form; `DevelopmentsTablePolicy::canAdd`; list marks archived developments and selects `id, name, type, archived_at` explicitly. |
| Default deny | `test(app): walk the development routes in the default-deny test` | Walks every routed `Developments` action as the super-administrator and fails if one answers 500 (an action that never authorizes), and checks that the add, edit, archive and unarchive routes are walked. |
| Docs | `docs: record developments-units PR 2b` | This description, change-log (W-E decision), tasks, progress, test-results, openspec tasks. |

## How to review

1. `app/src/Controller/DevelopmentsController.php` and `app/config/routes.php`.
2. `app/src/Policy/DevelopmentPolicy.php` (W-E methods) and `DevelopmentsTablePolicy::canAdd`.
3. `app/templates/Developments/form.php` and `index.php`.
4. Tests: `DevelopmentManagementControllerTest`, `DevelopmentArchivedWritesPolicyTest`, the new walk in `DefaultDenyTest`.

## Verification

- `vendor/bin/phpunit` (run alone, real PostgreSQL): `OK (686 tests, 2305 assertions)`; baseline was 624 tests, 2020 assertions. The new tests were seen failing first.
- `vendor/bin/phpcs`: clean (exit 0)
- Migrations: none in this PR.
- Security review: self-review plus mutation checks, each seen red and then restored: no entity authorization in `managedDevelopment` (default-deny walk fails); a `false` save treated as success (5 tests fail); a `DevelopmentScopeViolation` treated as success (2 fail); `canAdd` always true (4 fail); index without explicit fields (1 fails); development looked up under `developments.view` instead of `developments.manage` (8 fail); W-E archived check removed (9 fail). A fresh-context review is still due.
- Manual checks: none in a browser.
- Not verified: no manual check in a browser (layout and flash messages only seen through integration tests); the archived-write refusal at database level (row-level security for units, people and occupancies arrives in PR 3a-i and is coarse by design, so today the refusal is policy only); GitHub Actions (not pushed).

## Database and row-level security

No database change.

## Deployment and setup notes

None.

## Known limits and follow-ups

- Pending product decision: which roles besides the super-administrator may see `bank_name`, `bank_account`, `rfc` and `phone`. They are readable in SQL by every role that sees the development (coarse model), so this PR keeps them off the list and shows them only on the edit form, which only the super-administrator can open. Nothing here exposes them to other roles.
- Edit, archive and restore answer 404 to every non-super user (same body as a missing id, AC-MD-1); add answers 403 because it has no id.
- Archived developments can still be edited by the super-administrator; only the unit, people and occupancy writes are blocked (W-E, policy methods added now, called by the PRs that add those writes).
- Save results are asserted: a `false` save or a refused scoped write shows an error and never a success message. The failure paths are driven in tests by a `Model.beforeSave` listener, since the real save cannot fail for an all-development scope.
- Size: 1101 changed lines in `app` (324 in `app/src`, `app/config` and `app/templates`, the rest tests) against a ~380 estimate; `size:exception`, as in PR 2a. The planned split (2b-i add and edit, 2b-ii archive and policy) was not applied because the controller, routes and tests share one form and one save path.

## Checklist

- [x] Tests written first and passing
- [x] Coding standard clean
- [x] `docs/change-log.md`, `docs/tasks.md`, `docs/progress.md`, `docs/test-results.md` updated (AGENTS.md)
- [x] No secrets committed (`app/config/app_local.php` stays ignored)
- [x] Legacy application at the repository root unchanged
- [ ] GitHub Actions green (not pushed)

## Chain context

Start: schema, permission, validation and policy for development fields (2a). End: management screens and the archived-write policy. Prior dependency: PR 2a. Follow-up: PR 3a-i (`permission_dev_ids`), which adds the database layer.

```
feat/units-sdd -> ... -> 1b -> 2a -> [2b (here)] -> 3a-i -> ... -> 5c
```
