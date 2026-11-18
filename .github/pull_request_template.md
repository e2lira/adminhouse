<!--
Title: conventional commit style, for example `feat(app): add unit fees per development`.
Keep the PR small and focused. Delete the hints in comments before submitting.
-->

## Summary

<!-- What this PR changes and why, in two or three sentences. Link the task ids (docs/tasks.md), ADRs and specs it implements. -->

## What is included

<!-- One row per area or commit group. -->

| Area | Commits | Summary |
|---|---|---|
|  |  |  |

## How to review

<!-- Suggested reading order: decision docs first, then migrations, then domain code, then tests. Call out generated or vendored files that only need a skim. -->

1.
2.

## Verification

<!-- Paste exact results. Say plainly what was NOT verified. -->

- `vendor/bin/phpunit` (run alone, real PostgreSQL): `OK (... tests, ... assertions)`
- `vendor/bin/phpcs`: clean
- Migrations: applied, rolled back and re-applied locally (if any)
- Security review: <!-- required for auth, authorization, RLS, payments, or diffs over 400 lines; summarize findings and how they were resolved -->
- Manual checks: <!-- what was tried by hand, and where -->
- Not verified:

## Database and row-level security

<!-- Required when the PR adds or changes tables, policies or definer functions. Otherwise write "No database change". -->

- [ ] New tables use `App\Database\RowLevelSecurity` helpers (ENABLE + FORCE RLS, one policy per command)
- [ ] Policy tests added under `app/tests/TestCase/Database/Rls` (legitimate and forbidden operations per actor)
- [ ] New `SECURITY DEFINER` functions owned by `kualitet_definer`, `search_path` pinned, no `PUBLIC` execute

## Deployment and setup notes

<!-- New environment variables, migrations (`bin/cake migrations migrate -c migrations`), provisioning steps, manual actions. Write "None" if there are none. -->

## Known limits and follow-ups

<!-- Accepted risks, task ids created for later work. -->

## Checklist

- [ ] Tests written first and passing
- [ ] Coding standard clean
- [ ] `docs/change-log.md`, `docs/tasks.md`, `docs/progress.md`, `docs/test-results.md` updated (AGENTS.md)
- [ ] No secrets committed (`app/config/app_local.php` stays ignored)
- [ ] Legacy application at the repository root unchanged, or the change is explained above
- [ ] GitHub Actions green
