# Feature: Authenticator-App Two-Factor Authentication

**Status:** APPROVED — G1 PASS; implementation remains blocked by G2.

## Overview

Add a TOTP second factor to the existing login journey so authenticated users can protect resident and operational data without changing payment behavior.

## User Story

As an authorized user, I want to enroll an authenticator application and verify a time-based code at sign-in so that a stolen password alone cannot access my account.

## Acceptance Criteria

- AC-1: A signed-in user can begin TOTP enrollment only after their existing password/session is verified, receives a secret only through a protected response, and confirms enrollment with a valid current code.
- AC-2: A user with confirmed TOTP must complete password authentication and a valid current TOTP code before an authenticated session is established.
- AC-3: An invalid, expired, reused, or missing TOTP code denies access without disclosing whether the password or second factor failed.
- AC-4: A user with confirmed TOTP can recover with one of 10 one-time backup codes. Codes are shown only when generated or regenerated, stored as hashes, cannot be retrieved after display, and are consumed atomically.
- AC-5: Regenerating backup codes invalidates every previously issued backup code.
- AC-6: Recovery codes and TOTP secrets are never logged or exposed in normal views.
- AC-7: Login regression evidence proves the existing profile redirect contract remains intact after a successful two-factor login.

## Technical Constraints

- Keep CakePHP in place; no framework or PHP upgrade is part of this feature.
- Store TOTP secrets encrypted at rest and backup codes as one-way hashes; never store recovery material in plaintext logs or responses.
- Rate-limit or otherwise throttle repeated second-factor failures and record security-relevant events without secrets.
- Implementation may begin only after SEC-001 supplies the isolated login characterization baseline.

## Out of Scope

- Email or SMS second-factor codes.
- Payment behavior changes.
- Production deployment, VPS provisioning, or a complete role-model redesign.

## Approved Recovery Policy

- Recovery is autonomous through a valid backup code; no administrator-mediated reset is part of this slice.
- Generation or regeneration produces exactly 10 one-time codes and invalidates all prior codes.
