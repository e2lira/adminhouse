# As-Is Architecture

## Overview
This is a server-rendered CakePHP 3.9 condominium-administration application. Controllers own routing-adjacent behavior, authorization, ORM queries, uploads, reporting, and integrations. The workspace has no Git metadata, so provenance relies on read-back evidence.

## Critical Flows
| Flow | Observed behavior | Boundary |
|---|---|---|
| Login | `/` reaches `Users::login`; Cake Auth establishes session; profiles 1 and 6 redirect specially. | SEC-001 preserves and characterizes it. |
| Payment policy | `PagoController::isAuthorized()` allows profiles 1, 2, 5. | SEC-001 tests only this method. |
| Payment actions | Payment controller can persist data and produce PDF, email, and spreadsheet effects. | Out of SEC-001. |
| Uploads | Condominium, resident, and expense controllers handle uploads. | Private-file enforcement is later work. |
| File delivery | 34 functional files across six categories currently reside below `webroot/files/**`; templates/controllers directly consume communication, ticket, message, Excel, condominium-logo, and area-image paths. Two public `.DS_Store` metadata artifacts were removed under a user-authorized cleanup, but web-server non-reachability has not yet been exercised. | This remains an active public-addressability exposure; FILE-001 must move delivery behind authorization. |

## Constraints
- CakePHP 3.9 and PHP `>=5.6` are legacy constraints.
- Authorization is duplicated across controllers.
- `tests/bootstrap.php` loads normal application bootstrap; no isolated datasource exists yet.
- `webroot/.htaccess` serves existing files directly and no nested `.htaccess` restricts `webroot/files/**`; those paths bypass application authorization.

## FILE-001 Public-File Inventory

| Category | Observed contents | Direct consumers / evidence | Delivery concern |
|---|---:|---|---|
| `Areas/imagen` | 2 images | `src/Template/Supervision/view.ctp` | Static area-image URL bypasses resource authorization. |
| `Comunicado/adjunto` | 2 PDFs | `src/Template/Comunicado/index.ctp` | Direct communication attachment URL. |
| `Condominio/logo` | 4 images | `src/Template/Condominio/{menu,view}.ctp`, `Usuario/**`, `Reportes/**` | Presentation asset is public today; ownership/migration policy is still required. |
| `Excel/archivo` | 23 XLSX/Numbers/ZIP files | `Cuotas/excel.ctp`, `Pago/excel.ctp`, `CuotasController.php`, `PagoController.php` | Both UI exposure and controller reads use public storage. |
| `Mensaje/adjunto` | 2 PDFs | `Usuario/profile.ctp`, `Usuario/mensajes.ctp` | Direct message attachment URL. |
| `Ticket/adjunto` | 1 PDF | `src/Template/Ticket/index.ctp` | Direct ticket attachment URL. |
| Root and `Mensaje/adjunto` `.DS_Store` | 2 filesystem-metadata artifacts, removed | No application consumer confirmed; both paths were previously statically served | User-authorized filesystem removal was verified; G3 must still verify web-server non-reachability and ensure neither is restored. |

`Respuesta.adjunto`, `Recordatorio.adjunto`, and Formulario image fields are model-backed discovery candidates only: no current public directory or direct consumer was confirmed. Database and production-storage discovery are required before FILE-001 implementation.
