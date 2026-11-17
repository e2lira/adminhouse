# Test Plan

## Test Levels
- Unit
- Integration
- E2E
- Security Baseline

## Critical Journeys
1. Login: form rendering, invalid credentials, and existing profile redirects.
2. Payment authorization: policy-level allow/deny behavior only; payment POST and index actions are excluded from SEC-001.

## G1 Draft Acceptance Evidence

| Requirement | Evidence required before implementation | Current state |
|---|---|---|
| RH-001 | Fail-closed test-datasource design, deterministic fixtures, focused login/payment-policy tests, and zero-side-effect assertions. | G3 IN_PROGRESS; blocked on compatible PHPUnit execution and HTTP/static non-reachability evidence. |
| RH-002 | Approved TOTP spec plus protected enrollment, challenge denial, 10 hashed one-time backup-code generation/display/consumption/regeneration tests, rate-limit evidence, and login-regression tests. | Spec review |
| RH-003 | Approved file-access spec plus owner and same-condominium-administrator allow tests, other-profile and cross-condominium 404/no-metadata tests, and safe-storage/upload-validation evidence. | Spec review |
| RH-004 | Read-only source-evidence inventory and critical action/profile matrix. | Spec review |

## Assumptions and Risks

- Existing redirect destinations are characterized from isolated test execution; HTTP `Location` values are not guessed from controller strings.
- Tests must fail before queries when a dedicated test datasource is unavailable.
- No test in SEC-001 may invoke payment persistence, mail, PDF, or spreadsheet behavior.
- TOTP backup-code assertions must verify codes cannot be retrieved after initial display, are consumed atomically, and are invalidated by regeneration.
- File denial assertions must verify HTTP 404 and the absence of file, resource, tenant, and storage metadata.
