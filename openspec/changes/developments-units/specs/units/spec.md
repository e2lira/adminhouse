# Units Specification

## Purpose

Sections and units inside a development, managed by administrator and data_entry (D6).

## Requirements

### Requirement: Sections

A development MAY have sections (tower, street, block). A section belongs to exactly one development and SHALL have a name unique within its development.

#### Scenario: Create section
- GIVEN an administrator of development A
- WHEN they create section "Tower 1"
- THEN it is stored with `development_id` of A

#### Scenario: Cross-development section
- GIVEN a user of A and a section of B
- WHEN the user requests it
- THEN the response is 404 without metadata

### Requirement: Unit identity

A unit SHALL have an optional section, a number and a type (apartment, house or lot). The combination (development, section, number) MUST be unique, treating a missing section as equal to another missing section (NULLS NOT DISTINCT). A unit's section MUST belong to the same development.

#### Scenario: Duplicate number without section
- GIVEN unit "12" with no section in development A
- WHEN another unit "12" with no section is created in A
- THEN it is rejected

#### Scenario: Same number in different sections or developments
- GIVEN unit "12" in section "Tower 1" of A
- WHEN unit "12" is created in section "Tower 2" of A, or in development B
- THEN both are accepted

#### Scenario: Section from another development
- GIVEN a unit being created in A
- WHEN it references a section of B
- THEN it is rejected

### Requirement: Mixed unit types

One development SHALL hold units of different types at the same time (AC-MD-4, units part).

#### Scenario: House, apartment and lot
- GIVEN development A
- WHEN a house, an apartment and a lot are created
- THEN all three exist in A

### Requirement: Measurements

A unit MAY have `lot_area_m2` and `indiviso_pct`, both optional. Non-positive values (zero or negative) MUST be rejected, and `indiviso_pct` MUST NOT exceed 100.

#### Scenario: Zero area
- GIVEN a unit form
- WHEN `lot_area_m2` is 0
- THEN it is rejected

#### Scenario: Apartment without area
- GIVEN a new apartment
- WHEN it is saved with no `lot_area_m2`
- THEN it is accepted

#### Scenario: Invalid indiviso
- GIVEN a unit form
- WHEN `indiviso_pct` is 120
- THEN it is rejected

### Requirement: Active flag

A unit SHALL carry an active/inactive flag, active by default. Inactive units remain readable and keep their history.

#### Scenario: Deactivate
- GIVEN an active unit
- WHEN an administrator marks it inactive
- THEN it stays stored and is shown as inactive

### Requirement: Units without a payer

A unit MAY exist with no owner and no payer (for example an unsold lot). The UI SHALL flag units with no payer, meaning no current primary occupancy of the unit's payer role (see occupancies). The flag lands with occupancies (slice 5); before that, units are created without it.

#### Scenario: Flagged unit
- GIVEN a unit with no current primary occupancy of its payer role
- WHEN the unit list is shown
- THEN the unit carries a visible "no payer" flag

#### Scenario: Creating an unowned unit
- GIVEN a data_entry user
- WHEN they create a unit without any occupancy
- THEN it is accepted

### Requirement: Labels on screens

Unit screens SHALL use the development's section and number labels (AC-MD-3, screens part).

#### Scenario: Labels
- GIVEN a development labelled "Block"/"Lot"
- WHEN a unit screen is rendered
- THEN those labels are used

### Requirement: Who may read and write

Reading SHALL require `units.view`. Creating, editing and (de)activating units SHALL require `units.manage`, held by administrator and data_entry. Other roles, and read-only assignments, MUST be refused writes. Units and sections are never deleted through the application; at the database DELETE is allowed only to the super-administrator. The resident role loses `units.view` in this change (AD13) and is refused unit reads until the resident lifecycle (D7).

#### Scenario: Resident read
- GIVEN a user whose only role is resident of A
- WHEN they list or open units of A
- THEN the action is refused

#### Scenario: data_entry writes
- GIVEN a data_entry user of A
- WHEN they create or edit a unit of A
- THEN it succeeds

#### Scenario: Refused roles
- GIVEN an accountant, supervisor, resident, or a read-only assignment of A
- WHEN they try to create or edit a unit
- THEN the action is refused

#### Scenario: Foreign unit write
- GIVEN an administrator of A
- WHEN they try to edit a unit of B
- THEN the response is 404 without metadata

### Requirement: Database enforcement

Sections and units SHALL have RLS policies matching the above and tests per role, including cross-development reads and writes.

#### Scenario: Direct SQL
- GIVEN a database session as a user of A
- WHEN it selects or updates units of B
- THEN no rows are visible or changed

#### Scenario: Direct delete
- GIVEN a database session as an administrator or data_entry user of A
- WHEN it deletes a unit or section of A
- THEN no row is removed
