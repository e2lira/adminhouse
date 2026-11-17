# ADR-0002: Make G2 Design-Only

- Status: Accepted
- Date: 2026-11-06
- Spec: `docs/security-hardening-task-1.md`

## Context
The former SEC-001 contract demanded executable test evidence for G2 while forbidding implementation before G2 passed.

## Decision
G2 approves architecture, threat boundaries, compatibility, rollback, and test strategy only. G3 owns bootstrap, datasource configuration, fixtures, and test execution.

## Alternatives
1. Permit G2 implementation — rejected: weakens the gate.
2. Require execution at G2 — rejected: circular.
3. Move execution to G3 — accepted.

## Consequences
G2 can pass without runtime modification; G3 remains blocked until G2 PASS.
