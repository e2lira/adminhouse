# Security Hardening Requirements

**Status:** APPROVED — G1 PASS; implementation remains blocked by G2.  
**Change:** SEC-001 and follow-on security hardening work.  
**Execution:** Brownfield / gated. Primary agent: Codex. Fallback: Claude.

## Mission

Protect resident data, payments, documents, and operations without interrupting service.

## Critical Journeys

1. A user signs in and reaches the existing destination for their profile.
2. Authorized users access existing payment functions without a behavioral regression.

## Initial Scope

- Add authenticator-app (TOTP) two-factor authentication in a later approved slice.
- Make uploaded files accessible only through authorization checks.
- Inventory existing roles, privileges, and permissions.
- Establish an isolated characterization-test baseline for login and payment authorization before behavioral changes.

## Constraints and Non-Goals

- Keep CakePHP for this delivery; framework/PHP migration is a separate workstream.
- Target a future VPS deployment; no production deployment is included now.
- Do not change payment business behavior in this delivery.
- Do not dispatch payment POST or index actions during SEC-001 characterization.

## Definition of Done

- TOTP, private-file authorization, and the role/privilege/permission inventory have approved feature specs before implementation.
- Login and payment regression evidence exists before a hardening change is promoted.
- No unauthorized file access is accepted by the verified authorization tests.

## Initial Acceptance Requirements

| ID | Requirement | Verification target | Status |
|---|---|---|---|
| RH-001 | Login and payment authorization behavior is characterized against an isolated datasource. | `docs/specs/login-payment-characterization.md`; SEC-001 tests and recorded results | Approved — implementation blocked by G2 |
| RH-002 | TOTP uses an authenticator application, not email codes; recovery uses 10 one-time, hashed backup codes shown only on generation/regeneration, with atomic consumption and full invalidation on regeneration. | `docs/specs/auth-totp.md` and TOTP acceptance tests | Approved — implementation blocked by G2 |
| RH-003 | Files require authorization before they are served; only the resource owner or an administrator of the same condominium may access them. Denied and cross-condominium access returns HTTP 404 without metadata disclosure. | `docs/specs/private-file-access.md` and authorization tests | Approved — implementation blocked by G2 |
| RH-004 | Existing roles, privileges, and permissions are inventoried with evidence. | `docs/specs/authorization-inventory.md` and reviewable matrix | Approved — implementation blocked by G2 |

## Risks and Assumptions

- Normal application data must never be used by the test suite.
- Payment actions have persistence, PDF, email, and spreadsheet side effects; SEC-001 does not invoke them.
- The current roles and tenant rules must be observed and documented before they are changed.

## Multi-Development and RBAC Requirements (added 2026-11-17, REVIEW - not approved)

These come from the scope changes MD-001 and RBAC-001. They are planning artifacts for G1 review; implementation belongs to the CakePHP 5.x stage of ADR-0004.

| ID | Requirement | Acceptance evidence |
|---|---|---|
| RH-005 | Kualitet manages condominiums and residential subdivisions as isolated developments: units identified by section and number with per-development labels, mixed unit types, owners and tenants as separate people with their own logins, payer per unit, fees with a method per fee (fixed, per square metre, indiviso), global plus per-development catalogs, per-development and consolidated reports, one document format, gate access (access log, vehicles, frequent service staff), and a reconciled data migration. Debt restrictions are deferred pending legal validation. | `docs/specs/multi-development.md` requirements MD-R1 to MD-R14 and AC-MD-1 to AC-MD-12; four G1 decisions listed in the spec. |
| RH-006 | Access control moves to RBAC: named permissions, a fixed role catalogue, role assignments per development, a global super-administrator with safeguards, default deny on every action, guards with their own login, suppliers without access, and a baseline that reproduces today's access before any change. | `docs/specs/rbac.md` requirements RB-R1 to RB-R11 and AC-RB-1 to AC-RB-8; prerequisite RH-004 inventory; two G1 decisions listed in the spec. |

**Update 2026-11-17:** RH-005 and RH-006 are APPROVED (G1 PASS by explicit user approval). Implementation remains gated by G2 design and the CakePHP 5.x stage of ADR-0004.
