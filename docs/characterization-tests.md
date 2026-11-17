# Characterization Baseline

Safe bootstrap and fail-closed guard checks have executed successfully. Focused PHPUnit controller tests have not executed because the legacy runner is incompatible with the installed PHP runtime.

| ID | Behavior | Evidence | Safety boundary |
|---|---|---|---|
| CTB-001 | Login form at `/` and `/users/login` | routes and `UsersController` | Isolated datasource |
| CTB-002 | Invalid login leaves no Auth session/redirect | `UsersController` | Isolated datasource |
| CTB-003 | Profiles 1, 6, default retain redirect behavior | `UsersController` | Capture real Location values |
| CTB-004 | Payment policy allows 1/2/5, denies other profile | `PagoController::isAuthorized()` | Direct method only |
| CTB-005 | Payment policy coverage dispatches neither `index()` nor `add()` | G3 guarded controller test | No payment route dispatch; collaborator instrumentation deferred |

## G3 Entry
- G2 PASS.
- Dedicated datasource design approved.
- Fail-fast bootstrap assertion, deterministic fixtures, and guards are implemented together.
- Mail, PDF, spreadsheet, and payment-write collaborator instrumentation remains a deferred G3 evidence task; it is not currently claimed as executed.
