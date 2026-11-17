# Feature: Private Authorized File Access

**Status:** APPROVED — G1 PASS; implementation remains blocked by G2.

## Overview

Replace direct public access to uploaded files with an authorization-controlled access path that protects resident and condominium data.

## User Story

As an authorized user, I want to access only documents I am permitted to see so that another resident or unauthenticated visitor cannot retrieve private files by guessing a URL.

## Acceptance Criteria

- AC-1: Uploaded files are not directly retrievable from a public webroot path.
- AC-2: Every file retrieval request requires an authenticated user and server-side authorization; access is allowed only to the resource owner or an administrator of the same condominium.
- AC-3: An unauthorized or cross-condominium request returns HTTP 404 and does not reveal file, resource, tenant, or storage metadata.
- AC-4: Authorized requests stream only the requested file with a safe server-generated storage name; client-supplied original filenames are not used as storage identifiers.
- AC-5: Upload validation accepts only approved file content/types and records sufficient audit information without exposing sensitive content.

## Technical Constraints

- Preserve existing business behavior unless explicitly changed by an approved follow-up task.
- Enforce authorization server-side; UI hiding is not authorization.
- Use test-only fixtures and never normal data when validating authorization behavior.

## Out of Scope

- Bulk migration of all historic files until a migration and rollback task is approved.
- Changes to payment rules or CakePHP migration.

## Open Questions

- Which file categories have retention or legal-record requirements?
