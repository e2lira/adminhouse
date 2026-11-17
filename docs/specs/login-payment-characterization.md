# Feature: Isolated Login and Payment Authorization Characterization

**Status:** APPROVED — G1 PASS; G3 is IN_PROGRESS, blocked on compatible PHPUnit execution and HTTP/static non-reachability evidence.

## Overview

Establish an isolated, fail-closed test boundary and document current login and payment-authorization behavior before security changes.

## User Story

As a maintainer, I want regression evidence for login and payment authorization so that hardening work does not interrupt critical journeys.

## Acceptance Criteria

- AC-1: The test harness aborts before fixtures or controller tests when a dedicated test datasource is absent, malformed, or not identified as test-only.
- AC-2: Login tests cover the existing form, invalid credentials, and profile-specific successful-login redirect behavior against an isolated datasource.
- AC-3: Payment-policy tests prove profiles 1, 2, and 5 are allowed and an unauthorized profile is denied without dispatching payment routes.
- AC-4: Test setup and cleanup are deterministic. Current payment-policy coverage proves no `PagoController::index()` or `add()` dispatch; collaborator-level mail, PDF, spreadsheet, and payment-write instrumentation remains required before G3 can claim that evidence.
- AC-5: Evidence records the test command, isolated environment/datasource name without credentials, expected behavior, actual behavior, and result.

## Technical Constraints

- Follow the detailed design in `docs/security-hardening-task-1.md`.
- Do not invoke `PagoController::add()` or `PagoController::index()`.
- Do not use `config/app_local.php` or normal application data as the test datasource.

## Out of Scope

- TOTP implementation, private-file implementation, and authorization-policy changes.
- Payment creation, PDF generation, email, spreadsheet output, or payment business-rule changes.
