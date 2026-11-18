# ADR-0006: Rebuild on CakePHP 5 Instead of Upgrading the Legacy Application

- Status: Accepted (user decision, 2026-11-17)
- Supersedes: ADR-0004 (gradual CakePHP upgrade)
- Amends: ADR-0005 section 5 (sequencing)
- Related: RH-005 and RH-006 (G1 PASS), ADR-0005 (G2 PASS with condition RLS-SEC-001), `docs/authorization-inventory-results.md`, PG-PROD-001

## Context

ADR-0004 planned a staged upgrade (3.9, 3.10, 4.x, 5.x) of the running application, followed by the multi-development and RBAC remodel (ADR-0005). Its main justification was protecting a production system: small, attributable, reversible steps.

On 2026-11-17 the user confirmed that there is no active production server; the last known dump holds only catalogs and three users. The legacy code would have to be ported twice: once to CakePHP 5 on the current schema, and again to the new model. Much of it is repetitive (reports with near-identical queries, controllers granting whole profiles) and would be discarded by the remodel.

## Decision

1. Build a new CakePHP 5 application that implements the approved requirements (RH-005, RH-006) and ADR-0005 from the start: development scoping, RBAC with Authorization policies, PostgreSQL 16 or later as the only database, English schema names, Spanish user interface.
2. The legacy CakePHP 3.9 application becomes a **functional reference**, not code to migrate. Its behaviour is captured in a feature inventory (parity checklist) built from the code, the table mapping in ADR-0005, the authorization inventory and the page sweeps done on 2026-11-17.
3. Delivery is module by module, each with its own tests written first, in an order driven by the primary path (billing cycle): identity and RBAC, developments and units, fees and charges, payments and receipts, reports, then communications, gate and the rest.
4. RLS-SEC-001 (security review and policy test suite against a real database) must pass before the first real deployment.
5. PHP 8.2 or later for the new application. The portable PHP 7.4 runtime remains only to run the legacy reference and its characterization tests.

## Alternatives

1. **Staged upgrade (ADR-0004)** - superseded: without production its risk control buys little, and it doubles the porting work.
2. **Upgrade the framework only, keep the current model** - rejected: the approved requirements need the new model anyway.

## Consequences

- ADR-0004 stages 1 to 3 are cancelled. The PHP 7.4 runner and the SEC-001 characterization tests stay as tools for reading legacy behaviour.
- Fixes made to the legacy application on 2026-11-17 (AZ-1, AZ-2, report and page fixes) remain improvements to the reference, not work to carry over; the new application meets the same goals through RBAC and development scoping.
- The legacy G3 track (SEC-001) no longer leads to a production release; its open item (HTTP check of the removed `.DS_Store` paths) applies only if the legacy application is ever served again.
- `SqlDialect` and the local PostgreSQL compatibility helpers are not needed in the new application.
- Risk: a legacy behaviour is forgotten. Mitigation: the feature inventory is the parity checklist and every item is either implemented, replaced by a documented design, or explicitly retired.
- Location (user decision, 2026-11-17): the new application lives in the same repository, in the directory `app/`, with its own `composer.json`, `config/`, `src/`, `templates/` and `tests/`. The legacy application stays at the repository root as the reference until the feature inventory is covered, and is then removed. Project documentation stays in the root `docs/`.
