# System Inventory

| Area | Current State | Evidence |
|---|---|---|
| Framework | CakePHP 3.9 line | `composer.json` |
| Runtime | PHP `>=5.6` | `composer.json` |
| HTTP | 56 controllers | `src/Controller/` |
| ORM | 47 table classes | `src/Model/Table/` |
| Authentication | Cake Auth + controller authorization | `AppController.php` |
| Tests | PHPUnit/Cake fixtures | `phpunit.xml.dist`, `tests/` |
| Integrations | PDF, spreadsheet, email | composer and payment/email controllers |
| File storage | 34 functional files in Areas, Comunicado, Condominio, Excel, Mensaje, and Ticket categories below public webroot; two `.DS_Store` metadata artifacts were removed and need later web-server non-reachability verification | `webroot/files/**`; direct template/controller consumers documented in `as-is-architecture.md` |
| Deployment | Future VPS | User-approved target; no deployment work yet |
| Provenance | No Git metadata | Workspace inspection |
