# Brownfield Migration and Rollback Strategy

## Strategy
Hardening is separate from modernization. The future progression is CakePHP 3.9 -> latest 3.x -> latest 4.x -> CakePHP 5.x, with characterization and compatibility evidence per boundary. G2 and SEC-001 do not authorize an upgrade.

## Compatibility Boundaries
- Preserve login and payment policy through characterization before behavior changes.
- Do not couple TOTP/file hardening with a framework upgrade.
- Runtime changes require separate approved staging evidence.

## Rollback Points
| Slice | Rollback | Preconditions |
|---|---|---|
| G3 test support | Revert test-only bootstrap/config/fixtures | No production config or normal data changed |
| TOTP | Controlled rollout/feature boundary | Login regression evidence |
| File authorization | Revert delivery policy without data loss | Backup/migration plan |
| Framework upgrade | Redeploy prior release | Tagged release and staging validation |
| File containment | Retain source backup plus file-to-record manifest; restore the authorized delivery mapping if migration fails, not public URLs | Checksum-verified copy, reference inventory, and access tests |

## Public File Containment Plan

`webroot/files/**` is currently public-addressable and is not compliant with the approved private-file contract. FILE-001 is a later implementation slice, after SEC-001, with this order:

1. Inventory every confirmed functional category (`Areas/imagen`, `Comunicado/adjunto`, `Condominio/logo`, `Excel/archivo`, `Mensaje/adjunto`, and `Ticket/adjunto`) with business record, owner/condominium, path, checksum, and consumer. The two non-migrated `.DS_Store` metadata artifacts (root and `Mensaje/adjunto`) were removed under user authorization; retain their removal record and verify web-server non-reachability. Query database and production storage for model-backed discovery candidates (`Respuesta`, `Recordatorio`, and Formulario images) before cutover.
2. Classify each item as protected binary content or presentation asset. Protected binary content uses private storage and authorized delivery; presentation assets require an explicit visibility decision rather than inheriting attachment access rules silently.
3. Add an authorized delivery endpoint and private storage abstraction; no endpoint may expose a raw filesystem path. Preserve an import/read path for Excel only after its authorization and migration requirements are designed.
4. Copy and verify functional files into private storage, update record references and every identified consumer, retain a checksum-backed manifest plus read-only source backup for migratable records, and verify that neither removed `.DS_Store` path is restored or statically reachable.
5. Exercise owner, same-condominium-admin, other-profile, and cross-condominium scenarios for protected files; denied requests return 404 without metadata. Verify each presentation-asset decision separately.
6. Disable direct web-server access to legacy public paths only after the manifest is complete, all consumers are migrated, removed-metadata non-reachability is verified, and authorized delivery is verified.

Existing direct URLs are intentionally not a compatibility contract: preserving them would preserve the exposure. Rollback restores verified authorized delivery and mappings; it does not re-publish files under `webroot` or restore either removed `.DS_Store` artifact.

No production cutover is authorized without source provenance, backup/restore, and deployment-runbook evidence.
