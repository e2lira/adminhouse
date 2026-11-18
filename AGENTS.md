# AGENTS.md (Project Master Workflow)

## Branching
- Promotion flow: short-lived branch -> `develop` -> `qa` -> `main`.
  - Work happens on short-lived branches cut from `develop` (`feat/...`, `fix/...`, `docs/...`, `chore/...`).
  - Every feature or fix PR targets `develop`. Never open a PR from a work branch straight to `qa` or `main`.
  - Promote with a PR from `develop` to `qa`, then a PR from `qa` to `main`.
  - Hotfixes also go through `develop` -> `qa` -> `main` unless the user explicitly approves another route.
  - Never commit directly on `develop`, `qa` or `main`.
- Merge a PR only when its GitHub Actions checks are green.

## Coding Standards
- Keep changes small, testable, and documented.

## Test Expectations
- Unit + integration required for feature logic.
- E2E required for critical user journeys.

## Gate Criteria
- Do not advance gates without recorded evidence.

## Documentation Obligations
- Update docs/progress.md, docs/tasks.md, docs/change-log.md after each meaningful step.

## Change Control
- Any scope change requires change-log entry and affected docs updates.

## Release Readiness
- Must satisfy docs/release-checklist.md.

## Pull Requests
- Every PR description follows `.github/pull_request_template.md` (GitHub fills it in automatically).
- Fill every section; write "None" or "No database change" instead of deleting a section, and state plainly what was not verified.
