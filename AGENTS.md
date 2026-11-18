# AGENTS.md (Project Master Workflow)

## Branching
- Prefer trunk-based flow with short-lived branches.

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
