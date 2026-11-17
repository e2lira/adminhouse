# ADR-0001: Preserve CakePHP and Isolate Security Hardening

- Status: Accepted
- Date: 2026-11-06
- Specs: `docs/specs/login-payment-characterization.md`, `docs/specs/auth-totp.md`, `docs/specs/private-file-access.md`, `docs/specs/authorization-inventory.md`

## Context
The legacy CakePHP 3.9 application has controller-centric authorization and payment side effects. Login and payments are critical. The first delivery is hardening, not framework migration.

## Decision
Retain CakePHP for hardening. Establish a fail-closed characterization baseline before authentication, file access, or authorization behavior changes. Modernization is a separate phased workstream.

## Alternatives
1. Upgrade framework first — rejected: combines compatibility and security risk.
2. Add TOTP/file policy immediately — rejected: no regression baseline.
3. Characterize critical behavior first — accepted: limits blast radius.

## Consequences
G3 starts with test support and characterization only. G2 requires design evidence, not executable tests.
