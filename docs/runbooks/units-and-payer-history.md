# Runbook: units, payer history and their migration

Applies to `units.payer`, `unit_payer_changes` and migration `20261125000001_AddUnitPayerAndHistory` (change `developments-units`, PR 5b, design A6).

## Why a unit cannot be deleted

Every unit has at least one `unit_payer_changes` row (written when the unit is created) and the foreign key from the history to `units` is `ON DELETE RESTRICT`. A `DELETE FROM units` fails with SQLSTATE 23001 (`unit_payer_changes_unit_fkey`) for every login, the super-administrator included. The application never deletes units: it deactivates them (`is_active`). The history is append-only: a trigger rejects UPDATE and DELETE on it for every login.

## Removing a mistaken unit (controlled change)

Only for a unit created by mistake that has no occupancies and no charges. Prefer deactivating it. If it must go:

1. Get approval from a second person and open a change-log entry that names the unit, the development and the reason.
2. Back up first: dump `units`, `unit_payer_changes` and `occupancies` rows of that unit (`pg_dump --table` or a `COPY (SELECT ...)`), and keep the file with the change record.
3. As the owner login (`kualitet_owner`, the `migrations` connection, never the application login), in ONE transaction:
   - check there is nothing else pointing at the unit (`occupancies`, and any later table that references `units (development_id, id)`); stop if there is;
   - `ALTER TABLE unit_payer_changes DISABLE TRIGGER unit_payer_changes_append_only;`
   - `DELETE FROM unit_payer_changes WHERE unit_id = :id;`
   - `DELETE FROM units WHERE id = :id;`
   - `ALTER TABLE unit_payer_changes ENABLE TRIGGER unit_payer_changes_append_only;`
   - verify `SELECT tgenabled FROM pg_trigger WHERE tgname = 'unit_payer_changes_append_only'` is `O`, then COMMIT.
4. Record the result in the change record. The deleted history rows exist only in the backup.

The owner login can also TRUNCATE the history or drop the trigger. That is accepted: the owner login is outside the threat model and must stay out of application reach (it is the migration login only).

## Rolling the migration back

`down()` drops `units.payer`, the history table, the functions and the triggers. It is lossy once any payer was changed: stored payers and the whole history are gone, and going forward again re-creates one history row per unit with payer `owner`, no user and the unit's `created` time. Take a backup of `units` and `unit_payer_changes` before rolling back, and restore the payers by hand if they matter. Roll back only before anything depends on the payer.
