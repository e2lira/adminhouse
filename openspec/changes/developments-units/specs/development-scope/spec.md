# Development Scope Specification

## Purpose

Layer 1 (ADR-0005) application scope for every development-owned table. Guarantees that a user reaches records only of developments where they hold a role for the permission being exercised, independently of database RLS (layer 2).

## Requirements

### Requirement: Scoped queries

Every query on a development-owned table SHALL be restricted by `DevelopmentScopedBehavior` to the developments allowed for the acting user and the permission being exercised (`AccessControl::allowedFor`). Read-only assignments SHALL contribute only to `.view` permissions. A contained (association) query SHALL either receive an explicit scope or come from a scoped table through a composite `(development_id, x_id)` foreign key; an association MUST NOT enter a scoped table from an unscoped one.

#### Scenario: User sees only their development
- GIVEN a user with a role only in development A
- WHEN the user lists a scoped table (developments, sections, units, people, occupancies)
- THEN only rows of development A are returned

#### Scenario: Super-administrator sees everything
- GIVEN a super-administrator and a development created after their account
- WHEN the super-administrator lists or opens records of that development
- THEN the records are returned (AC-MD-2)

#### Scenario: Unscoped association
- GIVEN a new association from an unscoped table into a scoped table
- WHEN the architecture test runs
- THEN it fails

### Requirement: Cross-development access answers not found

A record of a development outside the user's scope SHALL be reported as not found (HTTP 404) with no data and no metadata about its existence, for every record type (AC-MD-1).

#### Scenario: Reading another development's record
- GIVEN a user with a role only in development A and a unit of development B
- WHEN the user requests that unit by id
- THEN the response is 404 and is indistinguishable from a non-existent id

#### Scenario: Every type is covered
- GIVEN automated tests per record type
- WHEN each of developments, sections, units, people and occupancies of B is requested by a user of A
- THEN each answers 404

### Requirement: Scope on writes

On create, `development_id` SHALL be set from the active scope target. A request body carrying a `development_id` different from the scope target MUST throw `DevelopmentScopeViolation` before marshalling (answered as 404). A write targeting a development outside the scope MUST be refused, and an existing record's `development_id` MUST NOT be changed through the application. Validation rules that query the database SHALL run under the scope of the save. Bulk `updateAll`/`deleteAll` on scoped tables MUST throw.

#### Scenario: Write into a foreign development
- GIVEN a data_entry user of development A
- WHEN the user submits a unit for A whose body carries `development_id` of B
- THEN `DevelopmentScopeViolation` is thrown and nothing is stored

#### Scenario: Bulk update
- GIVEN code calling `updateAll` on a scoped table
- WHEN it runs
- THEN a LogicException is thrown

### Requirement: Fail closed without scope

Querying a scoped table with no active scope SHALL throw; it MUST NOT return all rows.

#### Scenario: No active scope
- GIVEN a scoped table query executed with no scope established
- WHEN the query runs
- THEN an exception is thrown and no rows are returned

#### Scenario: Empty scope at the ORM layer
- GIVEN a query given an empty scope (the user holds the permission nowhere)
- WHEN it runs
- THEN the ORM returns no rows; the controller still answers 403 through the table policy's `canIndex` (as `DevelopmentsTablePolicy` does)

### Requirement: Resident data protection at the application layer

Because database RLS cannot sub-scope residents (`dev_ids()` includes their developments), the application layer SHALL refuse a user who holds only a resident role any read of units, occupancies or people in this change: the resident role loses `units.view` (AD13, user-confirmed 2026-11-18) and holds neither `people.view` nor `occupancies.manage`. The resident sub-scope returns with the deferred resident lifecycle (D7). Tests SHALL document the coarse database behaviour.

#### Scenario: Resident-only user
- GIVEN a user whose only role is resident of development A
- WHEN they request the unit list, a unit, an occupancy or a person record (including RFC) of A
- THEN the application refuses it

### Requirement: Retrofit of existing scoped tables

Existing Table classes over development-scoped data SHALL use the behaviour; today this is `DevelopmentsTable` (field `id`). `RoleAssignmentsTable` is excluded because it is the source from which the scope is computed and super-administrator rows have no development; `person_developments` has no Table class (any future one MUST use the behaviour). Default deny SHALL remain active.

#### Scenario: Existing table is scoped
- GIVEN `DevelopmentsTable` using the behaviour
- WHEN a user of development A queries it with the `developments.view` scope
- THEN rows of other developments are excluded

### Requirement: Permission seeds

New permission codes (`developments.manage`, `units.manage`, `people.view`, `people.manage`, `occupancies.manage`) SHALL be seeded in the same slice as the code that checks them, with a `down()` that removes only the rows it added, and the role matrix test SHALL be updated in that slice.
