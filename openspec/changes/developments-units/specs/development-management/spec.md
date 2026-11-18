# Development Management Specification

## Purpose

Lifecycle and profile fields of developments. Only the super-administrator edits developments (D1); developments are archived, never deleted (D2).

## Requirements

### Requirement: Super-administrator only for development edits

Create, edit, archive and unarchive of a development SHALL be allowed only to the super-administrator (permission `developments.manage`, seeded with no role rows). Administrators, data_entry and every other role MUST be refused. RLS on `developments` SHALL remain unchanged (writes already super-administrator only).

#### Scenario: Super-administrator creates a development
- GIVEN a super-administrator
- WHEN they submit a valid development (type, name, section and number labels, timezone)
- THEN the development is created and visible in their scope

#### Scenario: Administrator tries to edit
- GIVEN an administrator of development A
- WHEN they attempt to edit A, including bank details
- THEN the action is refused by policy and by the database

#### Scenario: data_entry or read-only role tries to edit
- GIVEN a data_entry user or a read-only assignment on development A
- WHEN they attempt to create, edit or archive a development
- THEN the action is refused

#### Scenario: Edit of another development
- GIVEN a non-super user with a role only in A
- WHEN they attempt to open the edit screen of development B
- THEN the response is 404 without metadata

### Requirement: Archive instead of delete

A development SHALL be archived, never deleted. No delete route or action SHALL exist in the application, and the archive state SHALL be stored. Archived developments MUST keep all related data. RLS on `developments` is unchanged, so the super-administrator could still delete at SQL level; a development with sections or units is blocked by foreign keys. Writes to units, sections, people and occupancies of an archived development SHALL be refused by policy; only the super-administrator may unarchive.

#### Scenario: Archiving
- GIVEN a super-administrator and a development with units
- WHEN they archive it
- THEN it is marked archived, its units and history remain, and no row is removed

#### Scenario: Delete attempt
- GIVEN any user, including the super-administrator
- WHEN a delete of a development is attempted through the application
- THEN no delete route exists

#### Scenario: Writes in an archived development
- GIVEN an archived development A and its administrator or data_entry user
- WHEN they try to create or edit a unit, person or occupancy of A
- THEN the action is refused

### Requirement: Profile fields

A development SHALL accept nullable `address`, `phone`, `rfc`, `bank_name` and `bank_account` in addition to its existing fields. All five MAY be empty. No settings jsonb and no logo are part of this change.

#### Scenario: Create without optional fields
- GIVEN a super-administrator
- WHEN they create a development leaving the five fields empty
- THEN it is accepted

#### Scenario: Fields persisted
- GIVEN a super-administrator editing a development
- WHEN they set bank_name and bank_account
- THEN the values are stored and shown to users who can view the development

### Requirement: Type and labels

The development type (condominium or subdivision) SHALL set only defaults and labels, never restrict features (MD-R1). Section and number labels are per development.

#### Scenario: Block and Lot labels
- GIVEN a development with section label "Block" and number label "Lot"
- WHEN unit screens are shown
- THEN "Block" and "Lot" appear while stored data matches "Street"/"Number" semantics (AC-MD-3, screens part)

### Requirement: Visibility

Users SHALL see developments according to `developments.view` and their scope; the super-administrator sees all (AC-MD-2).

#### Scenario: Listing
- GIVEN a user with `developments.view` only in A
- WHEN they list developments
- THEN only A is listed
