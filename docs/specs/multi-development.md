# Multi-Development Support (Condominiums and Residential Subdivisions)

**Status:** APPROVED - G1 PASS for RH-005 by explicit user approval on 2026-11-17. Planning only; implementation still requires the G2 design (data model, tenant isolation, data migration) and belongs to the CakePHP 5.x stage.
**Change:** MD-001. Requested by the user on 2026-11-17.
**Delivery:** Implemented in the CakePHP 5.x stage of ADR-0004. Design runs in parallel with the migration.

## Why

Kualitet manages condominiums today. The business wants the same software to manage residential subdivisions (fraccionamientos), and to run several condominiums and/or subdivisions side by side, each isolated from the others.

Building this on the CakePHP 3.9 code would mean rewriting it during the migration. Designing it now lets the CakePHP 5 rewrite, the report rebuild (CAKE5-REPORTS-001), and the authorization work (APP-DEFECTS-001) target the final model once.

## Current State (evidence)

Taken from the schema converted from the 2026-10-29 production dump (`config/schema/kualitet-postgresql.sql`) and the controllers.

| Finding | Evidence | Consequence for this change |
|---|---|---|
| The condominium is the de facto tenant key. | 35 of 45 tables have `id_condominio`; 25 of them allow NULL. | Isolation depends on every query filtering by it. There is no central enforcement. |
| NULL `id_condominio` is used for shared catalogs. | `tipo_pago`, `tipo_cuota`, `tipo_gasto`, `categoria` have rows with and without a condominium. | "Global" versus "per development" catalogs must become an explicit rule. |
| Unit and resident are the same record. | `condomino` holds `numero_departamento` plus resident, contact, and vehicle fields. | A house with several owners or a tenant cannot be modelled; history is lost when a resident changes. |
| Users reach data through two links. | `users_condominio` (staff to condominium) and `asigna` (user to unit). | Two membership concepts to unify. |
| Roles are global. | `perfil` has six rows (Superuser, Admin, Supervisor, Contador, Capturista, Cliente) and no condominium. | A person cannot be admin of one development and resident of another. |
| Authorization is per controller and incomplete. | 14 controllers without `isAuthorized()` (APP-DEFECTS-001). | Tenant isolation cannot rely on the current authorization code. |
| Type inconsistencies on keys. | `amenidad_condominio.id_condominio` and `indiviso.id_condomino` are `varchar`. | To be normalized in the new schema. |
| Ten tables have no condominium link. | `asigna`, `cargo`, `cierre_condomino`, `destinatarios`, `excel`, `perfil`, `recordatorio`, `rubro`, `tipo_visita`, `users`. | Each needs an explicit owner (global, development, or derived through another key). |

## Goals

- One installation manages condominiums and residential subdivisions.
- Each development's data is isolated: users only see developments they belong to.
- Units can be apartments, houses, or lots, grouped by tower, street, block, or section.
- Fees, payments, debts, reports, amenities, visits, and tickets work the same way for every development type.
- Reports are rebuilt once on the new model (CAKE5-REPORTS-001), per development and also consolidated across several developments (question 7). A consolidated report only includes developments where the viewer holds the report permission; the super-administrator sees all of them.

## Non-Goals (for now)

- No change to the CakePHP 3.9 code for this feature.
- No production data migration before the CakePHP 5 stage is approved.
- Self-service sign-up for new clients and billing of the software itself, unless the business answers say otherwise (see open questions).

## Draft Concepts (to validate; not decisions)

- **Development**: replaces "condominium" as the isolation unit. Has a type (condominium or subdivision) and its own settings.
- **Section**: one grouping level inside a development. In subdivisions a unit is identified by section plus number, and each development chooses the labels: "Street" and "Number", or "Block" (manzana) and "Lot" (lote). The data is the same; only the labels change. Condominiums can use it for towers or leave it empty. Assumption: no higher level (phases, private streets) is needed; revisit if the business mentions one.
- **Unit**: apartment, house, or lot, with its own identifier, the lot area in square metres for subdivisions, and indiviso where applicable. The type belongs to each unit, not to the development (question 3): one development can mix houses, apartments, and lots. The development type (condominium or subdivision) becomes a default and a label, not a restriction.
- **Open consequence**: a per-square-metre fee applied to a unit without a lot area (for example an apartment) needs a rule: exclude the unit, charge zero, or block the fee until the area is captured. To confirm in the requirements.
- **Fee rule**: how a fee is charged to each unit. Methods: fixed amount per unit; rate per square metre of lot (rate x lot area); indiviso percentage (current condominium behaviour). The method is chosen on each fee (question 2b), so one development can mix methods: for example maintenance by square metre and an extraordinary fee as a fixed amount.
- **Person and occupancy**: owners and tenants are people linked to a unit for a period, separate from the unit record. Both can have their own login (question 5a) and each sees what belongs to their occupancy. Each unit records who is responsible for paying, owner or tenant, and it can change when the lease changes (question 5b). Owner and tenant both receive the unit's notices, reminders and statements (question 5c), so an owner learns about debts accumulated by a tenant.
- **Open consequence**: when the responsible payer changes, what happens to unpaid charges from before the change. Proposal to confirm in the requirements: each charge keeps the payer it had when it was generated; only new charges follow the new setting.
- **Membership**: a user's role in a specific development (admin, accountant, guard, resident, ...), replacing global `perfil` plus `users_condominio`/`asigna`. Roles and permissions follow the RBAC design in `docs/specs/rbac.md` (RBAC-001, user decision 2026-11-17).
- **Gate access** (question 2, gate): beyond today's QR visit registration, any development can use an access log where the guard records every entry and exit with date and time (visitors, deliveries, services, even without a prior QR); registered resident vehicles (plates, tag or sticker); frequent service staff with standing authorisation per unit and schedule; and restrictions for units with debts. The debt restriction is legally sensitive: its scope (which services, never access to the resident's own home) must be validated by the business with legal advice before it is specified.
- **Catalogs** (payment types, fee types, expense categories, suppliers): a global base shared by every development, plus items each development adds for itself (question 6). A development sees the global items and its own, never another development's. Proposals to confirm in the requirements: only the super-administrator edits global items; when migrating, today's duplicates (for example "Mantenimiento" both global and per condominium, or the same supplier such as "Comisión Federal de Electricidad" registered 14 times; in the 2026-10-29 dump 126 supplier rows hold 73 distinct names and 174 expense categories hold 67) are merged into one global item.
- **Tenant isolation**: enforced centrally (CakePHP 5 Authorization policies and a scoped query layer), not per query.
- **Organization**: not needed. A single administration company operates the system (question 1), so the development is the only isolation level. Revisit only if the system is later offered to other companies.

## Open Questions (business)

1. ~~Who are the clients: one administration company managing many developments, or several administration companies each with their own developments?~~ Answered 2026-11-17: one administration company.
2. What changes in a subdivision compared to a condominium: fee rules (per lot area, per house, flat), street/block structure, gate access, common services? Fee rules answered 2026-11-17: by lot square metres or a fixed amount. Structure answered 2026-11-17: street and number, or block and lot (same two fields, labels per development). Gate answered 2026-11-17: access log, resident vehicles, frequent service staff, debt restrictions (legal validation pending). Common services were not raised; assumption: they are handled as ordinary fees and expenses.
   - 2b. ~~Is the fee method chosen once per development, or per fee?~~ Answered 2026-11-17: per fee.
3. ~~Can one development mix unit types (houses and apartments)?~~ Answered 2026-11-17: yes.
4. ~~Can a person belong to several developments, with different roles in each?~~ Answered 2026-11-17: yes, roles are assigned per development (RBAC question 1).
5. Are owners and tenants both users? Who pays, who receives notices? Answered 2026-11-17: both have their own login; the payer is configured per unit (owner or tenant); both receive notices.
6. ~~Which catalogs are global and which are per development?~~ Answered 2026-11-17: global base plus per-development items.
7. ~~Do reports ever need to combine several developments?~~ Answered 2026-11-17: yes, consolidated reports are needed.
8. ~~Are there legal or fiscal differences between condominium and subdivision documents?~~ Answered 2026-11-17: no; the same documents are used and only the data changes (unit identification, fee method).

## Requirements (RH-005)

Each requirement traces to the decisions log. Items marked **[G1 decision]** carry a proposed default that the user confirms or changes at G1 review.

| ID | Requirement | Source |
|---|---|---|
| MD-R1 | A **development** has a type (condominium or subdivision), a name, and the labels used for its section and unit number ("Street"/"Number" or "Block"/"Lot", or a tower label for condominiums). The type only sets defaults and labels; it never restricts features. | Q2, Q3 |
| MD-R2 | Every development-owned record belongs to exactly one development. A user reads or changes data only in developments where they hold a role, except the super-administrator (RH-006). | Q1, RBAC Q1, Q3 |
| MD-R3 | A **unit** is identified by an optional section plus a number, unique within its development. Each unit has its own type (apartment, house, lot), an optional lot area in square metres, and an optional indiviso percentage. | Q2, Q3 |
| MD-R4 | **People** are separate from units. An occupancy links a person to a unit as owner or tenant with a start date and an optional end date; ending an occupancy keeps its history. Owners and tenants can each have their own login. | Q5a |
| MD-R5 | Each unit records its **responsible payer** (owner or tenant), changeable at any time. Every charge stores the payer it had when it was generated; changing the setting affects only charges generated afterwards. **Confirmed by the user 2026-11-17.** | Q5b |
| MD-R6 | Notices, reminders and statements of a unit go to both its current owner and its current tenant. | Q5c |
| MD-R7 | A **fee** stores its own method: fixed amount per unit, rate per square metre (rate x lot area), or indiviso percentage. A development can mix methods across fees. | Q2, Q2b |
| MD-R8 | A per-square-metre fee cannot be generated while any targeted unit lacks a lot area; the system lists those units so staff can complete them or exclude them explicitly. No unit is charged zero silently. **Confirmed by the user 2026-11-17.** | Q3 consequence |
| MD-R9 | **Catalogs** (payment types, fee types, expense categories, suppliers) combine a global base with items owned by one development. A development sees global items and its own only. Only the super-administrator edits global items. **Confirmed by the user 2026-11-17.** | Q6 |
| MD-R10 | **Reports** exist per development and consolidated across several developments. A consolidated report includes only developments where the viewer holds the report permission. | Q7 |
| MD-R11 | Receipts, statements, reports and regulations use one format for every development type; labels and fee details adapt to the development's data. | Q8 |
| MD-R12 | **Gate access** for any development: an access log of entries and exits recorded by the guard (with or without a prior QR), registered resident vehicles (plates, tag or sticker), and frequent service staff with standing authorisation per unit and schedule. Today's QR visit registration keeps working. | Q2 gate |
| MD-R13 | **Deferred:** restrictions for units with debts are not specified until the business validates their scope with legal advice. They must never prevent a person from reaching their own home. | Q2 gate |
| MD-R14 | **Data migration:** each current condominium becomes a development of type condominium; each `condomino` row becomes a unit, a person and an owner occupancy; catalog duplicates are merged into one global item through a reviewed list: the migration proposes groups (same name ignoring case and spacing), a person from the business approves or corrects them (for example "CFE" and "Comisión Federal de Electricidad", or two different people with the same name), and existing records are reassigned to the merged item. Row counts and money totals per development match before and after. **Confirmed by the user 2026-11-17.** | Q6, current state |

### Acceptance Criteria

- AC-MD-1: A user with a role only in development A gets "not found" (no data, no metadata) for any record of development B, for every record type, verified by automated tests that try each type across developments.
- AC-MD-2: A super-administrator can open records of every development, including one created after their account.
- AC-MD-3: A development using "Block"/"Lot" labels shows those labels on screens, receipts and statements; the stored data is the same as for "Street"/"Number".
- AC-MD-4: One development holds a house, an apartment and a lot at the same time, and fees apply to each according to their method.
- AC-MD-5: For a fee of 10 per square metre and a 250 m² lot, the unit's charge is 2,500; a fixed fee of 500 charges 500 to every targeted unit; an indiviso fee charges each unit its percentage of the total, and the sum equals the total.
- AC-MD-6: Generating a per-square-metre fee when two targeted units have no lot area is blocked and both units are listed (MD-R8).
- AC-MD-7: After changing a unit's payer from owner to tenant, charges generated earlier still show the owner as payer and new charges show the tenant (MD-R5).
- AC-MD-8: A reminder for a unit with both owner and tenant is delivered to both.
- AC-MD-9: A development admin can add a payment type for their development but cannot edit a global payment type; the new item is not visible in other developments.
- AC-MD-10: A consolidated report requested by a user with the report permission in developments A and B, and a resident role in C, includes A and B only.
- AC-MD-11: A guard records an entry without a prior QR and the log shows date, time, unit and guard; a frequent service person is admitted only inside the authorised schedule.
- AC-MD-13: The migration produces a merge proposal for suppliers and catalog items; nothing is merged until the list is approved, and every expense or payment linked to a merged item points to the surviving item afterwards.
- AC-MD-12: After migrating the 2026-10-29 dump, every condominium exists as a development, every `condomino` row exists as a unit with an owner occupancy, and per-development counts and money totals match the source.

### Decisions needed at G1 review

1. ~~MD-R5: existing charges keep their original payer when the payer setting changes.~~ Confirmed 2026-11-17.
2. ~~MD-R8: block per-square-metre fees while lot areas are missing.~~ Confirmed 2026-11-17.
3. ~~MD-R9: only the super-administrator edits global catalog items.~~ Confirmed 2026-11-17.
4. ~~MD-R14: merge duplicated catalog items and suppliers into global items during migration.~~ Confirmed 2026-11-17, with a human-reviewed merge list.

## Dependencies

- ADR-0004 (gradual CakePHP upgrade): implementation belongs to the 5.x stage; no 4.x build goes to production.
- CAKE5-REPORTS-001: reports are rebuilt on this model.
- APP-DEFECTS-001: the authorization policy decision becomes part of the membership design.
- `docs/specs/authorization-inventory.md` (RH-004): its role/action matrix is the starting point for memberships.

## Next Steps

1. Answer the open questions, one at a time, and record the answers here.
2. Turn the answers into requirements (RH-005) and acceptance criteria; submit for G1 approval.
3. Design ADR: data model, tenant isolation, data migration from the current schema.
4. Implementation and data migration in the CakePHP 5.x stage.

## Decisions Log

| Date | Question | Answer | Recorded by |
|---|---|---|---|
| 2026-11-17 | Start now or after the CakePHP 5 migration? | Design now in parallel with the migration; implement in CakePHP 5. | Claude (user decision) |
| 2026-11-17 | Q1: one administration company or several? | One administration company manages all developments. No organization level; the development is the isolation unit. Staff may work across developments of the same company. | Claude (user decision) |
| 2026-11-17 | Q2 (fees): how are subdivision fees calculated? | Either by the lot's square metres (rate x area) or as a fixed amount. Units therefore need a lot area; condominiums keep indiviso. | Claude (user decision) |
| 2026-11-17 | Q2b: fee method per development or per fee? | Per fee. Each fee stores its method (fixed, per square metre, indiviso) and its amount or rate; a development can mix methods. | Claude (user decision) |
| 2026-11-17 | Q2 (structure): how are homes identified in a subdivision? | Street and number; the same two fields can be used as block (manzana) and lot (lote). Modelled as section + number with labels configured per development. | Claude (user decision) |
| 2026-11-17 | Q3: can one development mix unit types? | Yes. Unit type is stored per unit; the development type is only a default and a label. | Claude (user decision) |
| 2026-11-17 | Q4 (roles across developments) | User direction: migrate to RBAC. Specified separately in `docs/specs/rbac.md`; whether role assignments are per development is RBAC question 1. | Claude (user decision) |
| 2026-11-17 | Q5a: do owners and tenants both log in? | Yes, both have their own login. | Claude (user decision) |
| 2026-11-17 | Q5b: who pays a unit's charges? | Configured per unit: owner or tenant, changeable when the lease changes. Treatment of earlier unpaid charges proposed, not decided. | Claude (user decision) |
| 2026-11-17 | Q5c: who receives a unit's notices? | Both owner and tenant receive notices, reminders and statements. | Claude (user decision) |
| 2026-11-17 | Q6: how are catalogs shared? | Global base shared by all developments plus each development's own items. Editing rights for global items and merging of current duplicates proposed, not decided. | Claude (user decision) |
| 2026-11-17 | Q7: reports across developments? | Yes, consolidated reports in addition to per-development ones; limited to the developments where the viewer has the report permission. | Claude (user decision) |
| 2026-11-17 | Q8: different documents for subdivisions? | No. Receipts, statements, reports and regulations share one format; only the data differs (unit identification labels, fee method). | Claude (user decision) |
| 2026-11-17 | Q2 (gate): is QR visit registration enough? | No. Add an access log, resident vehicles, frequent service staff, and restrictions for units with debts (scope subject to legal validation). Available to any development. | Claude (user decision) |
| 2026-11-17 | G1 decision 1 (MD-R5) | Earlier charges keep the payer they had when generated; only new charges follow a payer change. | Claude (user decision) |
| 2026-11-17 | G1 decision 2 (MD-R8) | A per-square-metre fee is blocked while any targeted unit lacks a lot area; the units are listed so staff complete them or exclude them explicitly. | Claude (user decision) |
| 2026-11-17 | G1 decision 3 (MD-R9) | Only the super-administrator edits global catalog items; development administrators manage their own items. | Claude (user decision) |
| 2026-11-17 | G1 approval | User approved RH-005 (G1 PASS). | Claude (user decision) |
| 2026-11-17 | G1 decision 4 (MD-R14) | Merge duplicated suppliers and catalog items during migration through a proposed list approved by a person from the business. | Claude (user decision) |
