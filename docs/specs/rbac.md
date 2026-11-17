# Role-Based Access Control (RBAC)

**Status:** APPROVED - G1 PASS for RH-006 by explicit user approval on 2026-11-17. Planning only; implementation still requires the RH-004 inventory baseline and the G2 design, and belongs to the CakePHP 5.x stage with MD-001.
**Change:** RBAC-001. Requested by the user on 2026-11-17.
**Delivery:** Implemented in the CakePHP 5.x stage of ADR-0004, together with multi-development support (MD-001).

## Why

`docs/specs/authorization-inventory.md` (RH-004) requires a separate approved specification for any authorization redesign. This is that specification. The user wants to move from today's fixed profiles to RBAC, and the multi-development design (`docs/specs/multi-development.md`) needs permissions that can differ per development.

## Current State (evidence)

| Finding | Evidence |
|---|---|
| Six fixed profiles, one per user, global to the whole system. | Table `perfil` (Superuser, Admin, Supervisor, Contador, Capturista, Cliente); `users.id_perfil`. |
| Permissions are hardcoded per controller. | 39 controllers define `isAuthorized()` with literal checks such as `$user['id_perfil'] === 1`. |
| Some controllers have no rule at all. | 14 controllers without `isAuthorized()` answer every request with a 500 (APP-DEFECTS-001). |
| Templates do not decide access by profile (corrected 2026-11-17 by the RH-004 inventory; the first draft said they did). | The 7 templates that reference `id_perfil` only show it in forms or the profile CRUD (`docs/authorization-inventory-results.md`). |
| No per-condominium permission check is centralized. | Isolation relies on each query filtering `id_condominio` (see the multi-development spec). |

## Goals

- Roles are named sets of permissions; permissions are checked by name, not by profile number.
- Changing what a role can do does not require code changes in every controller.
- Every controller action is covered by an explicit rule; nothing is allowed by omission.
- Role assignments work together with development isolation (pending question 1 below).

## Draft Concepts (to validate; not decisions)

- **Permission**: an allowed operation, for example `payments.create` or `reports.view`.
- **Role**: a named set of permissions, for example Administrator, Accountant, Data entry, Supervisor, Guard, Resident. The first release ships a fixed role catalogue defined by the system; the administration only assigns roles (question 2). Roles and permissions are still stored as data, so editable roles can be added later without changing the model, only by adding a management screen.
- **Role assignment**: a user holds a role in a specific development (question 1). One user can hold different roles in different developments, and permissions are always evaluated for the development being accessed.
- **Super-administrator**: a global role that sees every development, including new ones, without per-development assignments (question 3). It is the only exception to per-development assignments.
- **Enforcement**: CakePHP 5 Authorization plugin policies that ask "does this user have permission X in this development?" instead of comparing profile numbers.
- **Migration**: map each current profile to an initial role whose permissions reproduce today's behaviour, verified against the RH-004 inventory matrix before any change in behaviour.

## Requirements (RH-006)

Each requirement traces to the decisions log. Items marked **[G1 decision]** carry a proposed default that the user confirms or changes at G1 review.

| ID | Requirement | Source |
|---|---|---|
| RB-R1 | Access is granted by named **permissions** (for example `payments.create`, `reports.view`, `gate.log`). A **role** is a set of permissions. Roles and permissions are stored as data. | Goals |
| RB-R2 | The first release ships a **fixed role catalogue**: Administrator, Accountant, Data entry, Supervisor, Guard, Resident (owner or tenant), and Super-administrator. There is no screen to create or edit roles (deferred to RBAC-002). | Q2 |
| RB-R3 | A **role assignment** links a user, a development and a role. A user may hold several assignments, with different roles in different developments. Every permission check is made for the development being accessed. | Q1 |
| RB-R4 | The **super-administrator** holds a global role that grants every permission in every development, including developments created later, without per-development assignments. | Q3 |
| RB-R5 | Super-administrator safeguards: TOTP is mandatory for the role; at most 2 accounts hold it (one responsible person and one backup); every action it performs is written to an audit log (who, what, which development, when). **Confirmed by the user 2026-11-17.** | Q3 |
| RB-R6 | **Default deny:** every controller action has an explicit authorization policy; an action without a policy is refused. No template decides access by comparing profile numbers. | Current state |
| RB-R7 | **Guards** sign in with their own account and reach only gate functions of the developments where they are assigned. | Q4 |
| RB-R8 | **Residents** (owners and tenants) reach only the data of their own units and occupancies. | MD-R4 |
| RB-R9 | **Suppliers** have no login; they remain catalog records linked to expenses. | Q4 |
| RB-R10 | **Baseline first:** before any behaviour change, the initial roles reproduce today's access, verified against the RH-004 inventory matrix by automated tests. | Prerequisites |
| RB-R11 | **User migration:** each current profile maps to a catalogue role; `users_condominio` rows become role assignments; `asigna` rows become resident assignments; of the 3 current Superuser accounts in the 2026-10-29 dump, `dante` and `edgar` become super-administrators and `Alan` receives per-development roles, to be named before the migration runs. **Confirmed by the user 2026-11-17.** | Current state |

### Acceptance Criteria

- AC-RB-1: An automated test lists every route and action and fails if any action has no authorization policy.
- AC-RB-2: A user who is Accountant in development A and Resident in development B can view A's reports but not B's, and sees only their own unit in B.
- AC-RB-3: A super-administrator can open a development created after the account was set up, without any assignment.
- AC-RB-4: A super-administrator cannot complete sign-in without TOTP, and each of their changes appears in the audit log with user, action, development and time.
- AC-RB-9: Granting the super-administrator role to a third account is refused while two accounts already hold it.
- AC-RB-5: A guard can record an access log entry in their development and is refused (not found) for payments, reports and other developments.
- AC-RB-6: For every row of the RH-004 inventory matrix, the migrated role produces the same allow or deny result as today's profile before any intentional change.
- AC-RB-7: No template or controller compares `id_perfil` with a number (static check).
- AC-RB-8: After migration, every existing user signs in and reaches the same developments as before, except accounts the business removed during the Superuser review.

### Decisions needed at G1 review

1. ~~RB-R5: confirm the three super-administrator safeguards and the maximum number of accounts.~~ Confirmed 2026-11-17: all three, maximum 2 accounts.
2. ~~RB-R11: review which of the current Superuser accounts keep that role.~~ Confirmed 2026-11-17: `dante` and `edgar` keep it; `Alan` gets per-development roles (which ones is an operational detail to settle before the migration).

## Prerequisites

- RH-004 authorization inventory (approved, not started): the current role/action matrix is the baseline that the initial roles must reproduce.

## Open Questions (business)

1. ~~Are roles assigned per development or globally?~~ Answered 2026-11-17: per development.
2. ~~Can the administration company create and edit roles itself, or is the role catalogue fixed?~~ Answered 2026-11-17: fixed catalogue first; editable roles later.
3. ~~Is there a super-administrator role that sees every development?~~ Answered 2026-11-17: yes. Proposed safeguards to confirm in the requirements: TOTP mandatory for this role, very few accounts, and an audit log of its actions.
4. Do guards and suppliers need their own roles and logins? Guards answered 2026-11-17: yes, own login and role. Suppliers answered 2026-11-17: no access; they remain data records linked to expenses.

## Decisions Log

| Date | Question | Answer | Recorded by |
|---|---|---|---|
| 2026-11-17 | Keep fixed profiles or move to RBAC? | Move to RBAC, specified separately from the inventory as RH-004 requires; implementation in CakePHP 5 with multi-development. | Claude (user decision) |
| 2026-11-17 | Q1: role assignments per development or global? | Per development. A user can be Accountant in one development and Resident in another with a single login; every permission check includes the development. | Claude (user decision) |
| 2026-11-17 | Q2: fixed role catalogue or editable roles? | Fixed catalogue in the first release; editable roles later. Roles and permissions are stored as data so the later screen does not change the model. | Claude (user decision) |
| 2026-11-17 | Q3: super-administrator role? | Yes, a global role that sees every development. Safeguards (mandatory TOTP, few accounts, audit log) proposed for the requirements. | Claude (user decision) |
| 2026-11-17 | Q4: guards and suppliers with their own login? | Guards: yes, own login and role. Suppliers: no access; data records only. All RBAC business questions are answered; next step is requirements and acceptance criteria (RH-006). | Claude (user decision) |
| 2026-11-17 | G1 decision 5 (RB-R5) | Super-administrator safeguards confirmed: mandatory TOTP, at most 2 accounts, audit log of every action. | Claude (user decision) |
| 2026-11-17 | G1 approval | User approved RH-006 (G1 PASS). | Claude (user decision) |
| 2026-11-17 | G1 decision 6 (RB-R11) | `dante` and `edgar` keep the super-administrator role; `Alan` moves to per-development roles. | Claude (user decision) |
