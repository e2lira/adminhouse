# Task 1: Establish a Safe Login and Payment Characterization Baseline

**Status:** APPROVED — G2 PASS / QUEUED_FOR_G3  
**Gate:** G1 planning -> G2 architecture -> G3 Slice-1  
**Owners:** Codex (primary), Claude (fallback)

## Outcome

Create a fail-closed test boundary and characterize the current login and payment-authorization contracts without changing production behavior, normal data, or payment side effects.

## G2 Architecture Decision (Approved)

G2 approved the test datasource design. G3 implementation must:

1. require an explicit test-only connection value and abort when it is absent;
2. reject a datasource name/DSN that does not identify a dedicated test database;
3. prevent `config/app_local.php` from supplying the test connection;
4. create deterministic fixture setup and cleanup for every test; and
5. record the active environment and datasource name in the test output without credentials.

**G2 evidence:** this reviewed design, the brownfield baseline, ADR-0001, ADR-0002, and the documented G3 test strategy. G2 does not create a datasource, modify bootstrap, or run tests. G2 is `PASS`; runtime implementation and tests remain queued for G3.

## Scope

1. Design and implement a test-only datasource that fails closed.
2. Replace incomplete login test placeholders with characterization tests.
3. Characterize `PagoController::isAuthorized()` only; do not dispatch payment actions.

## Observable Contracts

| Scenario | Source fact | Required test observation |
|---|---|---|
| Login form | `/` routes to `Users::login`; the action selects the `login` layout. | `GET /` and `GET /users/login` return HTTP 200 and render the login form. |
| Invalid login | `Auth->identify()` failure does not redirect or set a user. | Invalid `POST /users/login` returns HTTP 200, has no `Auth.User` session value, and has no redirect response. |
| Profile 1 login | Source calls `redirect('pages/bienvenido')`. | Successful login returns a 3xx redirect to the framework-resolved `pages/bienvenido` destination; record the exact `Location` from the first isolated run. |
| Profile 6 login | Source calls `redirect('usuario/profile')`. | Successful login returns a 3xx redirect to the framework-resolved `usuario/profile` destination; record the exact `Location` from the first isolated run. |
| Other profile login | Source calls `Auth->redirectUrl()`; app configuration sets `Pages::bienvenido` as the login redirect. | Successful login returns a 3xx redirect to the configured Auth destination; record the exact `Location` from the first isolated run. |
| Payment access | `PagoController::isAuthorized()` returns `true` only for profiles 1, 2, and 5. | Direct policy tests assert `true` for 1/2/5 and `false` for an unauthorized profile. No payment route is dispatched. |

The destination strings above are source facts. Their final HTTP `Location` is a framework-routing observation and must be captured, not guessed, in the first isolated test run.

## Acceptance Criteria

- [ ] A missing, malformed, or non-test datasource configuration fails before any fixture, query, or controller test runs.
- [ ] Test setup creates known fixtures and cleanup removes them deterministically after every test; no test depends on execution order.
- [ ] Tests prove the login HTTP status/session/redirect contracts in the table above.
- [ ] Direct payment-policy tests prove profiles 1, 2, and 5 are allowed and an unauthorized profile is denied.
- [ ] The test harness installs fail-fast fakes or guards for mail, PDF, spreadsheet, and write-producing payment collaborators; each focused test asserts zero invocations.
- [ ] `composer test`, `vendor/bin/phpunit --filter UsersControllerTest`, and `vendor/bin/phpunit --filter PagoControllerTest` run only after the isolation assertion passes, and results are recorded.

## Non-Goals

- Do not dispatch `PagoController::index()` or `PagoController::add()`.
- Do not create payments, bank records, balances, folios, PDFs, emails, or `test.xlsx`.
- Do not add TOTP, change authorization behavior, secure file serving, change payment behavior, or upgrade CakePHP. The documented `webroot/files/**` exposure—including all confirmed attachment, Excel, logo, and area-image consumers—is deferred to FILE-001 and is not remediated by SEC-001.

## Guardrails

| Area | Decision |
|---|---|
| Data safety | Only a dedicated test datasource may be used; normal application data is an automatic failure. |
| Side effects | SEC-001 proves `index()`/`add()` are not dispatched. Collaborator-level mail, PDF, spreadsheet, and payment-write instrumentation requires explicit production seams and is tracked as a separate follow-up before full action-level characterization. |
| Payment scope | Tests call only `PagoController::isAuthorized()`; POST and index actions remain deferred. |
| Secrets | Logs name the environment/datasource only; they never print DSNs or credentials. |

## Ordered Checklist

- [x] 1.1 Approve the G2 design-only test-boundary architecture and G3 lifecycle.
- [ ] 1.2 Add the isolated datasource, bootstrap assertion, deterministic setup, and cleanup.
- [ ] 1.3 Repair the Users fixture with a valid test-only password hash.
- [ ] 1.4 Write failing login contract tests, then add only the test support needed to make them pass.
- [ ] 1.5 Add direct payment-policy tests and action-dispatch guards; plan collaborator-level instrumentation separately without dispatching payment actions.
- [ ] 1.6 Run focused tests and the suite; record evidence and update the handoff artifacts.

## Verification and Rollback

Run tests only after the isolation assertion passes. Roll back by reverting test-only configuration and test changes; no production configuration, business behavior, or normal data may require restoration.

## Approval Gate

The user approved this specification. G2 is design-only and `PASS`. SEC-001 is queued for G3; implementation begins only when the G3 slice is explicitly started.


