# Target System Description (input for the architecture diagram)

**Status:** DRAFT - primary path, database engine, payment gateway and guard access confirmed by the user on 2026-11-17; remaining unknowns are marked below.
**Diagram:** `.archify/architecture-kualitet-target-20261117-112858/kualitet-target.html` (Archify, generated from this description).
**Describes:** Kualitet after the CakePHP 5 migration (ADR-0004) with multi-development (MD-001), RBAC (RBAC-001), TOTP (RH-002) and private files (FILE-001 / ADR-0003).
**Rule:** facts come from approved docs or user answers. Anything not yet decided is marked `UNKNOWN` instead of invented.

## Users

| Actor | What they do | Source |
|---|---|---|
| Administration staff | Run one or more developments: units, fees, payments, expenses, reports, notices. Their role is assigned per development (administrator, accountant, data entry, supervisor). | MD-001 Q1, RBAC-001 Q1, current profiles |
| Residents (owners and tenants) | See their statement and receipts, message the administration, open tickets, book amenities, register visits. | Current resident pages (`UsuarioController`) |
| Guards | Sign in with their own account and check registered visits (QR list) at the gate of their development. | Current `vigilante` and QR actions; own login confirmed by the user 2026-11-17 (RBAC Q4) |
| Super-administrator | Sees every development, including new ones, without per-development assignments. | Confirmed by the user 2026-11-17 (RBAC Q3) |

## Core Components (target)

1. **Web UI** - server-rendered CakePHP 5 pages for staff and residents.
2. **Authentication** - password sign-in plus TOTP authenticator app and backup codes (RH-002).
3. **Authorization (RBAC)** - CakePHP 5 Authorization policies; permissions checked per development (RBAC-001).
4. **Development scope** - every request and query is limited to the development being accessed (MD-001).
5. **Units and occupancy** - developments, sections (street/block or tower), units (apartment, house, lot; lot area; indiviso), owners and tenants.
6. **Fees and billing** - fee definitions with a method per fee (fixed, per square metre, indiviso) that generate each unit's charges.
7. **Payments and receipts** - payments, discounts, credit balances, folios, receipt PDFs.
8. **Expenses and suppliers** - expenses, categories, petty cash, suppliers, bank movements.
9. **Reports** - monthly close, half-year and annual reports, unit statements; rebuilt on the new model (CAKE5-REPORTS-001).
10. **Communications and services** - notices, messages, tickets, reminders, amenity bookings.
10b. **Gate access** - QR visits, access log of entries and exits, resident vehicles, frequent service staff, debt restrictions (legal validation pending). Used by guards.
11. **Private file storage** - uploaded documents outside the public web root, delivered only after an authorization check (FILE-001).
12. **Database** - single PostgreSQL database (user decision 2026-11-17); every development-owned table is keyed by development.

## Primary Path

Billing cycle (confirmed by the user, 2026-11-17): staff signs in (password + TOTP) -> RBAC check in the selected development -> fees generate unit charges -> payment recorded (by staff, or automatically when the gateway confirms an online payment) -> receipt PDF -> receipt emailed to the resident -> reports.

## External Dependencies

| Dependency | Use | Source |
|---|---|---|
| SMTP mail server | Receipts, reminders, password reset. | `config/app.php` email transports |
| Authenticator app (resident or staff phone) | TOTP codes. | RH-002 |
| PDF libraries (Dompdf, TCPDF, FPDF via CakePdf) | Receipts and reports. | `composer.json` |
| Spreadsheet library (PhpSpreadsheet) | Excel import and export. | `composer.json` |
| Online payment gateway | Residents pay their charges online; the confirmed payment is recorded automatically. Provider `UNKNOWN`. Card data should stay on the provider's hosted checkout, never on Kualitet's server. | User decision 2026-11-17 (PAY-001) |

## Boundaries

- **Internet / browser**: staff and residents.
- **Application host**: target is a VPS (`docs/requirements.md`); provider and layout `UNKNOWN`.
- **Development isolation**: logical boundary enforced by authorization and query scoping; staff cross it only through role assignments in several developments.
- **Private storage**: files reachable only through the application.
- **Data store**: database and backups.

## Open Facts That Change the Diagram

1. ~~Primary path to draw.~~ Answered: billing cycle.
2. ~~Production database engine after the migration.~~ Answered: PostgreSQL.
3. ~~Online payment gateway: planned or not.~~ Answered: planned; provider still `UNKNOWN`.
4. Guards: answered, own login. Super-administrator: answered, exists. Suppliers: answered, no access (data records only).
