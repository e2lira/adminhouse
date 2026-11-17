# ADR-0003: Replace Direct Public Attachment Delivery

- Status: Accepted
- Date: 2026-11-06
- Specs: `docs/specs/private-file-access.md`, `docs/architecture.md`

## Context

The application stores 34 observed functional files below `webroot/files/**` across Areas, Comunicado, Condominio, Excel, Mensaje, and Ticket categories. Templates and controllers consume direct public paths for attachments, Excel imports/downloads, condominium logos, and area images, bypassing an application authorization check. Two `.DS_Store` filesystem-metadata artifacts (root and `Mensaje/adjunto`) were also publicly reachable before user-authorized removal. This conflicts with the approved owner-or-same-condominium-administrator policy and no-disclosure 404 response for protected content.

## Decision

Treat direct public file paths as an exposure, not a supported API. FILE-001 will inventory file-to-record ownership, consumers, categories, and checksums; classify protected binary content separately from presentation assets; migrate protected files to private storage; deliver files through an authorization endpoint; and deny legacy direct paths with 404 only after migration and removed-metadata non-reachability verification. The two `.DS_Store` artifacts are already removed; G3 must verify both paths remain absent at the web-server boundary. Model-backed but unconfirmed categories require database/production discovery before cutover.

## Alternatives

1. Keep public files and hide links - rejected: URL guessing still bypasses authorization.
2. Authorize only the current templates - rejected: files remain directly reachable outside the template.
3. Move files behind authorized delivery - accepted: enforces tenant and role policy at the serving boundary.

## Consequences

Legacy direct URLs intentionally break after verified migration. Rollback restores the authorized delivery mapping and verified private copy; it does not re-publish protected files under `webroot` or restore either removed `.DS_Store` artifact. Presentation assets require their own approved visibility decision before any compatibility change.
