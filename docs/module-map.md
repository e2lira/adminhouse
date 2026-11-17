# Kualitet module map

Use this map to identify the owner, dependencies, and risk profile of a feature before changing it.

## Core modules

| Domain | Typical entry points | Key dependencies | Watch-outs |
|---|---|---|---|
| Condominium and residents | Condominio*, Condomino*, Usuario*, UsersCondominio* controllers | User/condominium membership tables, server-rendered views | Tenant decisions are controller-local. |
| Financial operations | Pago*, Gasto*, Cuotas*, Adeudos*, Banco*, Bancaria*, Saldo*, Cierre* | ORM tables, folios, balances, exports | Payment writes can cascade and emit PDF/email/Excel effects. |
| Operations and communication | Ticket*, Comunicado*, Mensaje*, Visitas*, Amenidad*, Supervision*, Calendario* | Templates, attachments, condominium context | Multiple areas link directly to webroot/files/**. |
| Reporting | ReportesController and export-oriented actions | Financial and condominium records, PDF/Excel plugins | Large controller surface; treat changes as high-risk. |
| Platform | Application, AppController, routes, Cake table classes | CakePHP middleware, Auth, CSRF, Security, MySQL | Framework and authorization conventions shape every module. |

## Request ownership

1. config/routes.php determines which controller/action receives a URL.
2. src/Controller/AppController.php establishes global authentication and request-security components.
3. Each controller can add its own authorization rule and domain behavior.
4. src/Model/Table/** owns ORM persistence concerns.
5. src/Template/** renders HTML and currently contains some direct attachment links.

## Hotspots

| Area | Evidence | Reason to inspect first |
|---|---|---|
| Payments | src/Controller/PagoController.php | Multi-record writes, exports, PDF, and email side effects. |
| Reports | src/Controller/ReportesController.php | Large controller and broad data access. |
| Resident UI | src/Controller/UsuarioController.php | Large controller with user-facing behavior. |
| Attachments | webroot/files/**, src/Template/** | Direct public delivery bypasses a dedicated authorization endpoint. |
| Authorization | Controller isAuthorized() methods | Policy is duplicated rather than centrally enforced. |

## Reading order

- Feature behavior: route -> controller -> table -> template.
- Security-sensitive behavior: also inspect AppController, membership tables, and attachment delivery.
- Financial behavior: inspect PagoController before exercising routes because of side effects.

## Detailed references

- [As-is architecture](as-is-architecture.md)
- [System inventory](system-inventory.md)
- [Dependency map](dependency-map.md)
- [Legacy risk register](legacy-risk-register.md)
