# Kualitet system overview

Kualitet is a server-rendered CakePHP 3.9 monolith for condominium operations. A browser request is routed through CakePHP middleware and controller actions, then uses Cake ORM to persist condominium, resident, financial, and operational data in MySQL.

## Quick path

1. Start with the Runtime path and Main domains sections below for the request and data path. (The source-backed Archify candidate was generated, but HTML publication failed because the local filesystem identity check failed; no diagram artifact was delivered.)
2. Read [module map](module-map.md) to locate a feature area and its high-risk dependencies.
3. Use the detailed baselines only when planning changes: [as-is architecture](as-is-architecture.md), [system inventory](system-inventory.md), [dependency map](dependency-map.md), and [legacy risk register](legacy-risk-register.md).

## Runtime path

| Step | Responsibility | Evidence |
|---|---|---|
| Entry | index.php boots the CakePHP application. | index.php |
| HTTP pipeline | Application registers error, asset, and routing middleware. | src/Application.php |
| Routing | / targets Users::login; condominium-scoped routes target domain controllers. | config/routes.php |
| Session security | AppController loads Auth, CSRF, and Security components. | src/Controller/AppController.php |
| Domain work | Controllers coordinate views, ORM calls, uploads, reports, and payment workflows. | src/Controller/** |
| Persistence | Cake table classes provide ORM access to the MySQL datasource. | src/Model/Table/**, config/app.php |

## Main domains

- **Condominium and residents:** condominium, resident, membership, blocks, and ownership participation.
- **Financial operations:** payments, expenses, quotas, debts, banking, balances, and closings.
- **Operations and communication:** tickets, communications, messages, visits, amenities, supervision, and calendars.
- **Reporting and exports:** reports plus PDF, email, and spreadsheet output.

See [module map](module-map.md) for the controller-level entry points and boundaries.

## Boundaries that matter

| Boundary | Current behavior | Why a maintainer should care |
|---|---|---|
| Authentication | CakePHP session authentication; successful login redirects by profile. | Access policy is not centralized. |
| Authorization | Controllers implement isAuthorized() independently. | Tenant and role logic is duplicated across many controllers. |
| Payments | PagoController persists related records and can generate PDF/email/Excel outputs. | A test or change can have multiple side effects. |
| Files | Templates link to files below webroot/files/**. | Direct public delivery is a known exposure; private delivery is planned, not implemented. |

## Limits and risks

This is an evidence-backed high-level map, not a complete data model or authorization proof. Existing tests are largely incomplete, payment actions have side effects, and direct public file URLs remain a security boundary under remediation planning.

## Next step

For a feature change, trace from its route through its controller and table classes, then check the dependencies and risks in the linked detailed documents before editing.

