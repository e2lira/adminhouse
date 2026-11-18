# ADR-0004: Gradual CakePHP Upgrade (3.9 -> 3.10 -> 4.x -> 5.x)

- Status: Proposed - user direction recorded 2026-11-16; stage specs and gate approval pending
- Date: 2026-11-16
- Related: `docs/migration-plan.md`, `docs/requirements.md` (framework migration is a separate workstream), `docs/php74-phpunit-runner.md`

## Context

The application runs CakePHP 3.9.10 with PHPUnit 6.5.14. The user stated that the project goal is to migrate to a newer CakePHP, that the version jump must be gradual, and that the application must pass through CakePHP 4 before CakePHP 5. CakePHP 3.x is end of life. CakePHP 4.x stopped receiving security fixes in September 2026, so it is a transit stage, not a release target.

## Decision

Upgrade in stages, each with its own approved spec, characterization evidence, and rollback point. No stage starts before the previous one is verified.

| Stage | Framework | PHP runtime | Exit criteria |
|---|---|---|---|
| 0 | 3.9.10 (current) | Portable 7.4 (`tools/php74`) | G3 characterization baseline for login/payment executes and passes |
| 1 | 3.10.x | Portable 7.4 | Baseline passes; deprecation warnings triaged and cleared |
| 2 | 4.0 -> latest 4.x | 7.4, then 8.x once 4.x and plugins allow | Baseline passes on latest 4.x with no 4.x deprecation warnings |
| 3 | 5.x | 8.2+ | Baseline passes; staging validation; release checklist; first production release of the upgrade |
| 4 | 5.x | 8.2+ | TOTP implemented on the 5.x authentication layer against a revised, approved spec |

## Known work per stage (from a static scan on 2026-11-16, not executed)

- Stage 1/2 code: 22 files reference `Cake\Network\*`; 32 use `TableRegistry::get()`; 21 use `loadModel()`; 8 use `Cake\Mailer\Email`; 1 legacy `Shell`; `AuthComponent` is used in 4 files; 278 `.ctp` templates under `src/Template`.
- Stage 2 tests: PHPUnit 6.5 must be replaced by the PHPUnit version required by CakePHP 4; 92 existing test files and fixtures need review.
- Stage 2 dependencies whose locked versions declare CakePHP 3 only and need an upgrade or a replacement: `backstageel/cakephp-gentelella-theme`, `dakota/cake-excel`, `falco442/cakephp-3-sweet-alert-helper`, `wyrihaximus/twig-view`, `friendsofcake/bootstrap-ui`, `friendsofcake/cakepdf`, `friendsofcake/cakephp-csvview`, `cakephp/bake`, `cakephp/debug_kit`. Unpinned or undeclared: `akkaweb/cakephp-ckeditor` (dev-master), `robotusers/cakephp-excel`, `josegonzalez/cakephp-upload`.
- PHP 8 blocker: `phpoffice/phpexcel` 1.8.2 (declares PHP ^5.2|^7.0).
- Stage 3: everything deprecated during 4.x is removed in 5.x, including `AuthComponent`.

## User decisions (2026-11-16)

1. TOTP (`docs/specs/auth-totp.md`) is implemented after the migration, once the application runs on CakePHP 5.x. It is not built on `AuthComponent` and not on an intermediate 4.x build. The spec must be revised for the 5.x authentication layer before implementation.
2. No CakePHP 4.x build is deployed to production. 4.x is a development/staging transit stage only; production moves from 3.9 directly to the verified 5.x release.

## Open decisions

1. Order of the remaining hardening slices (FILE-001 private-file containment, roles/permissions inventory) relative to the migration stages. Until decided, production stays on 3.9 with public `webroot/files/**`.
2. Replacement choices for dependencies without a CakePHP 4/5 release.

## Alternatives

1. Direct 3.9 -> 5.x jump - rejected by the user: too many simultaneous breaking changes and no intermediate verification.
2. Stop at 4.x - rejected: 4.x no longer receives security fixes.
3. Staged 3.9 -> 3.10 -> 4.x -> 5.x - proposed.

## Consequences

The portable PHP 7.4 runtime serves stages 0 to 2. The hardening scope (SEC-001, FILE-001, TOTP, roles inventory) stays a separate workstream; this ADR authorizes no code or dependency change by itself.


## Follow-up (2026-11-17)
- The local PostgreSQL environment relies on compatibility shims for MySQL-specific report SQL: `AppDatabaseSqlDialect` (including `relaxGroupBy()` registered on `Model.beforeFind`) and the local `month()`, `year()` and `sum(text)` helpers. The CakePHP 5 stage should replace them by rewriting the report and email queries (task CAKE5-REPORTS-001 in `docs/tasks.md`).


## Follow-up (2026-11-17, database engine)
- User decision: production will run on PostgreSQL. The engine change must be planned as its own step with data migration, backup/restore and rollback evidence; it should not share a release with a framework stage. Once production is on PostgreSQL and reports are rewritten, `SqlDialect` and the local compatibility helpers can be removed.


## Follow-up (2026-11-17, multi-development and RBAC)
- User decision recorded in ADR-0005: the framework upgrade (stages 0 to 3) runs first on the current schema. Multi-development and RBAC (RH-005, RH-006) are introduced afterwards as a separate stage on 5.x with its own data migration and the rebuilt reports. The PostgreSQL production move never shares a release with a framework stage.


## Superseded (2026-11-17)
- Superseded by ADR-0006: there is no production server, so the user chose to build a new CakePHP 5 application instead of upgrading this one in stages. Stages 1 to 3 are cancelled; the PHP 7.4 runner remains for the legacy reference.
