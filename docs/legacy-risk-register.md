# Legacy Risk Register

| ID | Risk | Severity | Mitigation | Owner |
|---|---|---|---|---|
| LR-001 | Legacy CakePHP/PHP baseline | High | Separate phased modernization and compatibility plan. | Codex / Claude |
| LR-002 | Tests use normal datasource | Critical | G3 fail-closed dedicated datasource. | Codex / Claude |
| LR-003 | Payment test triggers writes/PDF/mail/spreadsheet | Critical | Policy-only test scope and guards. | Codex / Claude |
| LR-004 | Controller authorization omits tenant checks | High | Inventory first; owner/same-condominium-admin policy. | Codex / Claude |
| LR-005 | Direct public file exposure across attachments, Excel files, logos, and area images | Critical | Inventory six confirmed functional `webroot/files/**` categories plus database/production candidates; classify protected binary content versus presentation assets; migrate protected content to private storage; update consumers to authorized delivery; intentionally deny legacy direct URLs with 404 only after verified migration. Two `.DS_Store` artifacts were removed; G3 must verify static non-reachability and prevent restoration. | Codex / Claude |
| LR-006 | No Git provenance | Medium | Read-back evidence now; establish source control before release. | Project owner |
