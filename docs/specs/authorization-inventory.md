# Feature: Roles, Privileges, and Permissions Inventory

**Status:** APPROVED — G1 PASS; implementation remains blocked by G2.

## Overview

Produce verified evidence of the current roles, controller-level authorization rules, privileges, and condominium/tenant boundaries before changing access control.

## User Story

As a system owner, I want a verifiable inventory of access rules so that authorization changes are based on current behavior rather than assumptions.

## Acceptance Criteria

- AC-1: The inventory identifies every observed profile/role value, its source location, and the controllers/actions it can access.
- AC-2: The inventory records authorization rules as source evidence, distinguishing observed behavior from unresolved assumptions.
- AC-3: The inventory identifies tenant/condominium checks and flags missing or duplicated checks as risks rather than silently normalizing them.
- AC-4: The inventory includes a reviewable matrix mapping each critical login, payment, and file-access action to allowed/denied profiles.
- AC-5: No production role, permission, or data is changed while collecting the inventory.

## Technical Constraints

- This is a read-only analysis and documentation feature.
- Findings must cite concrete repository paths and be reproducible from source review.
- Any authorization redesign requires a separate approved specification and migration plan.

## Out of Scope

- Changing roles, permissions, or authorization behavior.
- Creating a new RBAC framework during the inventory work.

## Open Questions

- Are profile numbers the complete role model or are there database-level grants not represented in source?
