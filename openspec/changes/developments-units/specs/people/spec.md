# People Specification

## Purpose

Staff capture (create and edit) of people linked to a development. People are separate from units (MD-R4). Creation goes through the `person_create` definer function because `people` has no INSERT policy. Linking an existing person to another development is out of scope; people shared between developments come from the migration stage (D8).

## Requirements

### Requirement: Create person through definer function

A person SHALL be created only through `person_create(development, data)`, which also creates the `person_developments` link in one atomic step. The function MUST be restricted to holders of `people.manage` (administrator and data_entry, D6) with a writable assignment in the target, non-archived development, and to the super-administrator. It MUST have owner `kualitet_definer`, a fixed `search_path`, and `EXECUTE` granted only to `kualitet_app`. The application MUST NOT insert people through the ORM (`PeopleTable` refuses to save new entities).

#### Scenario: Administrator creates
- GIVEN an administrator of A
- WHEN they create a person for A
- THEN the person and the link to A exist

#### Scenario: data_entry creates
- GIVEN a data_entry user of A
- WHEN they create a person for A
- THEN the person and the link to A exist

#### Scenario: Refused roles
- GIVEN an accountant, supervisor, resident, or a read-only assignment of A
- WHEN they call `person_create` for A
- THEN the call is refused and nothing is stored

#### Scenario: Foreign development
- GIVEN an administrator of A
- WHEN they call `person_create` for B
- THEN the call is refused with no information about B

#### Scenario: Direct insert
- GIVEN the application database role
- WHEN it inserts into `people` directly
- THEN the insert is denied

#### Scenario: ORM insert
- GIVEN application code
- WHEN it calls `PeopleTable::save()` with a new entity
- THEN a LogicException is thrown

### Requirement: Person fields

A person SHALL have a name, and optionally email, phone and RFC. A name MUST be required. No resident login is created (D7).

#### Scenario: Missing name
- GIVEN the create form
- WHEN the name is empty
- THEN it is rejected

#### Scenario: No login side effect
- GIVEN a person created by staff
- WHEN creation completes
- THEN no user account or Resident role assignment is created

### Requirement: No existence oracle

Creating a person MUST NOT reveal whether a person (by email, RFC or name) already exists in another development. Responses for matching and non-matching data SHALL be indistinguishable to the caller.

#### Scenario: Same email in another development
- GIVEN a person with email x in development B
- WHEN a data_entry user of A creates a person with email x
- THEN the outcome gives no hint that the email exists in B

### Requirement: Read scope

Reading people SHALL require `people.view` (administrator, accountant, data_entry, supervisor). People SHALL be listed only for developments in the user's scope; people of other developments answer 404 without metadata (AC-MD-1). A resident-only user is refused people reads in this change.

#### Scenario: Foreign person
- GIVEN a user of A and a person linked only to B
- WHEN the user requests that person
- THEN the response is 404

#### Scenario: Resident
- GIVEN a user whose only role is resident of A
- WHEN they request a person of A, including RFC
- THEN the action is refused

### Requirement: Editing people

Editing a person's data SHALL be allowed to the person themself, the super-administrator, and holders of `people.manage` (administrator, data_entry) when the person has at least one development link and EVERY linked development is one where the editor holds `people.manage` (`person_in_permission_scope`, mirroring `person_in_admin_scope`). RLS tests SHALL cover allow and deny per role.

#### Scenario: Administrator edit
- GIVEN an administrator of A and a person linked only to A
- WHEN they update the phone
- THEN it is saved

#### Scenario: data_entry edit
- GIVEN a data_entry user of A and a person linked only to A
- WHEN they update the phone
- THEN it is saved

#### Scenario: Shared person
- GIVEN a data_entry user (or administrator) of A only, and a person linked to A and B
- WHEN they try to update that person
- THEN the update is refused

#### Scenario: Refused roles
- GIVEN an accountant, supervisor or a read-only assignment of A and a person linked only to A
- WHEN they try to update that person
- THEN the update is refused

#### Scenario: Unlinked editor
- GIVEN an administrator of A and a person linked only to B
- WHEN they try to update that person
- THEN the response is 404

### Requirement: Database enforcement

`person_create`, `person_in_permission_scope` and the people policies SHALL have RLS tests for every role and for cross-development calls.
