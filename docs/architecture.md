# Security Hardening Architecture Baseline

## Decision
G2 is design-only. It approves architecture, threats, compatibility, rollback, ADRs, and test strategy. G3 alone may add the isolated datasource or execute tests.

## As-Is Layers
| Layer | Evidence | Responsibility |
|---|---|---|
| HTTP | `config/routes.php` | `/` routes to `Users::login`; payment routes target `Pago`. |
| Controllers | 56 files under `src/Controller/` | Transport, policy, data access, uploads, reports, and side effects. |
| Data | 47 table classes under `src/Model/Table/` | Cake ORM access to condominium, resident, payment, and accounting data. |
| Auth | `AppController` | Cake Auth, CSRF, Security, controller-level `isAuthorized()`. |
| Tests | `tests/`, `phpunit.xml.dist` | PHPUnit/Cake fixtures; login and payment controller tests are incomplete. |

## Security Boundaries
1. SEC-001 characterizes existing login and payment policy only.
2. G3 must use a dedicated test datasource and reject normal-data fallback.
3. G3 never dispatches `PagoController::index()` or `add()`.
4. Future file delivery authorizes resource owner or same-condominium administrator; denial is 404 with no metadata.
5. Future TOTP follows primary credential validation and precedes finalized session creation.
6. Existing `webroot/files/**` delivery is an exposure, not an approved interface. FILE-001 inventories six functional categories and their consumers, classifies protected binary records separately from presentation assets, migrates protected files, and serves content through an authorization boundary before legacy public paths are denied. Two `.DS_Store` artifacts were removed under user authorization; G3 must prove they are not reachable or restored.

## Gate Split
| Gate | Required evidence | Prohibited |
|---|---|---|
| G2 | Architecture, threat model, ADRs, compatibility, rollback, test strategy | Bootstrap/configuration changes and test execution |
| G3 | Isolated datasource, fail-fast assertion, deterministic fixtures, focused tests | Payment actions, production config, normal data |

## Threat Baseline
| Asset | Threat | Design control |
|---|---|---|
| Sessions | Credential compromise | Characterize first; TOTP is a later specified slice. |
| Files | Tenant/resource enumeration | Authorized delivery and no-disclosure 404 contract. |
| Public attachments | Direct URL retrieval bypasses controller authorization | Inventory, private storage migration, authorized endpoint, and deliberate legacy-path shutdown. |
| Payments | Test mutation and side effects | Policy-only tests; no route dispatch. |
| Test data | Normal datasource use | Fail-closed G3 boundary. |
