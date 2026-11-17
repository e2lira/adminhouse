# Change Impact


## 2026-11-06T18:06:50.759796+00:00
Request: Initiate gated brownfield security hardening: TOTP planning, private authorized files, roles and permissions inventory, and login/payment characterization baseline.
Impacted docs (review/update):
- docs/requirements.md
- docs/architecture.md
- docs/test-plan.md
- docs/test-results.md
- docs/tasks.md
- docs/progress.md
Validation actions:
- Re-run impacted unit/integration/e2e tests
- Re-run manual scenarios tied to changed behavior

## 2026-11-08 — Comprehensive FILE-001 public-file inventory
Request: Correct the G2 design baseline with every confirmed direct public-file category and consumer under `webroot/files/**`.
Impacted docs (review/update):
- docs/as-is-architecture.md
- docs/system-inventory.md
- docs/dependency-map.md
- docs/legacy-risk-register.md
- docs/compatibility-matrix.md
- docs/migration-plan.md
- docs/traceability.md
Validation actions:
- Inventory record, path, checksum, consumer, database reference, and production-storage ownership before FILE-001 implementation.
- Classify protected binary content and presentation assets before changing delivery behavior.
- Test authorized, denied, and cross-condominium delivery before legacy public paths are disabled.

## 2026-11-08 — FILE-001 metadata-artifact correction
Request: Classify the two public `.DS_Store` artifacts separately from functional file categories, remove them under user authorization, and plan non-reachability verification.
Impacted docs (review/update):
- docs/as-is-architecture.md
- docs/system-inventory.md
- docs/dependency-map.md
- docs/legacy-risk-register.md
- docs/compatibility-matrix.md
- docs/migration-plan.md
- docs/adr/ADR-0003-private-file-containment.md
Validation actions:
- Verify both `.DS_Store` artifacts are excluded from migration manifests and have no application consumer.
- Verify filesystem removal, static non-reachability, and no restoration before FILE-001 cutover.

## 2026-11-06 — Public attachment containment
Request: Correct the G2 design baseline for direct public attachments under `webroot/files/**`.
Impacted docs (review/update):
- docs/as-is-architecture.md
- docs/system-inventory.md
- docs/dependency-map.md
- docs/legacy-risk-register.md
- docs/compatibility-matrix.md
- docs/migration-plan.md
- docs/traceability.md
Validation actions:
- Inventory record/path/checksum ownership before FILE-001 implementation.
- Test authorized, denied, and cross-condominium delivery before legacy public paths are disabled.

## 2026-11-06T23:41:51.516903+00:00
Request: Design-only G2 baseline for security hardening: characterize login and payment authorization before TOTP, private-file access, and roles inventory changes.
Impacted docs (review/update):
- docs/requirements.md
- docs/architecture.md
- docs/test-plan.md
- docs/test-results.md
- docs/tasks.md
- docs/progress.md
Validation actions:
- Re-run impacted unit/integration/e2e tests
- Re-run manual scenarios tied to changed behavior
