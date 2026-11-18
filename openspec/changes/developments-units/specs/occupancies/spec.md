# Occupancies Specification

## Purpose

Owner/tenant periods of people on units, the unit's responsible payer, and the payer change history (MD-R4, MD-R5, MD-R6 data). Writable by administrator and data_entry (D6).

## Requirements

### Requirement: Occupancy records

An occupancy SHALL link one person to one unit of the same development with a role (owner or tenant), a `starts_on` date and an optional `ends_on` date, stored as the half-open period `[starts_on, ends_on)`. It SHALL carry a primary flag. Co-owners and roommates are non-primary.

#### Scenario: Create owner occupancy
- GIVEN a unit and a person of development A
- WHEN an owner occupancy is created from 2026-01-01 with no end
- THEN it is stored as an open-ended period

#### Scenario: Different development
- GIVEN a unit of A and a person linked only to B
- WHEN an occupancy is created
- THEN it is rejected

#### Scenario: Invalid period
- GIVEN an occupancy form
- WHEN `ends_on` is on or before `starts_on`
- THEN it is rejected

### Requirement: Person linked to the development

The occupant MUST have a `person_developments` row for the unit's development; the database SHALL enforce it with a composite foreign key only. A nonexistent person id and a person linked only to another development MUST fail identically (same constraint, same message to the user).

#### Scenario: Unlinked person
- GIVEN a person with no link to A
- WHEN they are made occupant of a unit of A
- THEN it is rejected

#### Scenario: No oracle on person ids
- GIVEN a nonexistent person id and the id of a person linked only to B
- WHEN each is used for an occupancy of a unit of A
- THEN both fail with the same constraint and the same message

### Requirement: No overlapping occupancies of one person

The same person MUST NOT hold overlapping occupancies of the same role on the same unit (exclusion constraint via `btree_gist`).

#### Scenario: Overlap
- GIVEN person P is owner of unit U from 2026-01-01 to 2026-06-01
- WHEN P is made owner of U from 2026-05-01
- THEN it is rejected

#### Scenario: Adjacent periods
- GIVEN P is tenant of U until 2026-06-01 (end exclusive)
- WHEN P is made tenant of U starting 2026-06-01
- THEN it is accepted

### Requirement: One primary owner and one primary tenant at a time

A unit SHALL have at most one primary owner and at most one primary tenant at any time. Non-primary co-owners and roommates are unrestricted by this rule.

#### Scenario: Second primary owner
- GIVEN unit U has a current primary owner
- WHEN another primary owner with an overlapping period is added
- THEN it is rejected

#### Scenario: Co-owner
- GIVEN unit U has a current primary owner
- WHEN a non-primary owner is added for the same period
- THEN it is accepted

#### Scenario: Primary owner and primary tenant together
- GIVEN unit U has a primary owner
- WHEN a primary tenant is added for the same period
- THEN it is accepted

### Requirement: History is kept

Ending an occupancy SHALL set `ends_on`; it is the only change allowed to an existing occupancy (from no end to a date after `starts_on`). Occupancies MUST NOT be deleted through the application; a wrongly captured occupancy is corrected by the super-administrator deleting it and staff capturing it again.

#### Scenario: Other edits refused
- GIVEN an existing occupancy
- WHEN staff try to change its role, primary flag, person or `starts_on`
- THEN the change is refused

#### Scenario: End a tenancy
- GIVEN an active tenant occupancy
- WHEN staff end it on 2026-09-01
- THEN the row remains with `ends_on` 2026-09-01 and appears in history

### Requirement: Current occupants query

The module SHALL answer "current occupants of a unit" (owners and tenants whose period contains today) for later use by communications (MD-R6 data).

#### Scenario: Both present
- GIVEN unit U with a current owner and a current tenant, and a past tenant
- WHEN current occupants are requested
- THEN the owner and the current tenant are returned and the past tenant is not

#### Scenario: Boundary day
- GIVEN a tenant occupancy with `ends_on` equal to today
- WHEN current occupants are requested
- THEN that tenant is not included (end exclusive)

### Requirement: Responsible payer

Each unit SHALL record its payer as a role, `owner` (default) or `tenant`, changeable at any time through the `unit_set_payer` definer function only. The responsible person is the current primary occupancy of that role. A unit has "no payer" when it has no current primary occupancy of its payer role; it MAY stay so (D5). The payer snapshot on charges is out of scope.

#### Scenario: Set payer
- GIVEN unit U with a current primary owner and payer `owner`
- WHEN the payer is shown
- THEN the responsible person is that primary owner

#### Scenario: Payer role switched to tenant
- GIVEN unit U with a current primary tenant
- WHEN staff set the payer to `tenant`
- THEN U records `tenant` and the responsible person is the primary tenant

#### Scenario: Unit without payer
- GIVEN a unit with payer `tenant` and no current primary tenant, or a unit with no occupancy
- WHEN it is listed
- THEN it is flagged "no payer" (D5)

### Requirement: Payer change history

Every change of a unit's payer SHALL append a row to `unit_payer_changes` (unit, payer, changed_at, changed by). The table MUST be append-only: no UPDATE and no DELETE is granted.

#### Scenario: Change logged
- GIVEN unit U with payer `owner`
- WHEN staff change the payer to `tenant`
- THEN U shows `tenant` and a new history row records `tenant`, the time and the acting user (MD-R5 data)

#### Scenario: Tampering
- GIVEN any application role
- WHEN it updates or deletes a `unit_payer_changes` row
- THEN the database denies it

### Requirement: Who may read and write

Reading SHALL follow scope and require `units.view`, with occupant names shown under `people.view`. Creating and ending occupancies and changing the payer SHALL require `occupancies.manage` (administrator and data_entry, D6) or the super-administrator. Other roles, read-only assignments and resident-only users MUST be refused. Background jobs have no write rights on these tables. Records of other developments answer 404 without metadata.

#### Scenario: Resident read
- GIVEN a user whose only role is resident of A
- WHEN they request occupancies of a unit of A
- THEN the action is refused

#### Scenario: data_entry writes
- GIVEN a data_entry user of A
- WHEN they create an occupancy and change the payer on a unit of A
- THEN both succeed

#### Scenario: Refused roles
- GIVEN an accountant, supervisor, resident or a read-only assignment of A
- WHEN they try to create an occupancy or change the payer
- THEN the action is refused

#### Scenario: Foreign occupancy
- GIVEN a user of A and an occupancy of B
- WHEN they request or modify it
- THEN the response is 404 without metadata

### Requirement: No resident login lifecycle

Creating or ending an occupancy MUST NOT create users, send invitations, or create or end Resident role assignments (D7).

#### Scenario: Occupancy without login
- GIVEN a new owner occupancy
- WHEN it is saved
- THEN no user account or role assignment is created

### Requirement: Database enforcement

Occupancies and `unit_payer_changes` SHALL have RLS policies and tests per role, including data_entry allow and deny, cross-development reads and writes, and boundary-day tests for the period conversion. The `btree_gist` extension SHALL be provisioned by the provisioning script and CI; the migration MUST fail loudly if it is absent.

#### Scenario: Extension missing
- GIVEN a database without `btree_gist`
- WHEN the occupancy migration runs
- THEN it fails with a clear error
