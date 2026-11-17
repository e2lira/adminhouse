# Security Hardening Plan

- Status: IN_PROGRESS
- Project Mode: brownfield
- Execution Mode: gated
- Research Mode: false
- Primary Agent: Codex
- Fallback Agent: Claude

## Quick Path

1. Complete and approve planning and brownfield baseline artifacts.
2. Use the approved design-only G2 architecture as the boundary for G3 runtime work.
3. Execute one verified G3 task at a time, protecting login and payments.

## Gate Plan

| Gate | Outcome | State |
|---|---|---|
| G0 | Intake: mission, scope, journeys, and mode captured. | PASS |
| G1 | User-approved requirements/specs, acceptance tests, risks, and traceability. | PASS |
| G2 | Design-only brownfield architecture baseline and ADR-backed FILE-001 design, including a comprehensive direct-public-file inventory, classification, migration, compatibility, and rollback plan. | PASS |
| G3 | First verified slice: SEC-001 characterization baseline. | PENDING |

## Rollback Principle

Every implementation slice must be reversible without changing payment behavior or using normal data for tests.


