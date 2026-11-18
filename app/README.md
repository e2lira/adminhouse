# CakePHP Application Skeleton

![Build Status](https://github.com/cakephp/app/actions/workflows/ci.yml/badge.svg?branch=5.x)
[![Total Downloads](https://img.shields.io/packagist/dt/cakephp/app.svg?style=flat-square)](https://packagist.org/packages/cakephp/app)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

A skeleton for creating applications with [CakePHP](https://cakephp.org) 5.x.

The framework source code can be found here: [cakephp/cakephp](https://github.com/cakephp/cakephp).

## Installation

1. Download [Composer](https://getcomposer.org/doc/00-intro.md) or update `composer self-update`.
2. Run `php composer.phar create-project --prefer-dist cakephp/app [app_name]`.

If Composer is installed globally, run

```bash
composer create-project --prefer-dist cakephp/app
```

In case you want to use a custom app dir name (e.g. `/myapp/`):

```bash
composer create-project --prefer-dist cakephp/app myapp
```

You can now either use your machine's webserver to view the default home page, or start
up the built-in webserver with:

```bash
bin/cake server -p 8765
```

Then visit `http://localhost:8765` to see the welcome page.

## Demo app

Check out the [5.x-demo branch](https://github.com/cakephp/app/tree/5.x-demo), which contains demo migrations and a seeder.
See the [README](https://github.com/cakephp/app/blob/5.x-demo/README.md) on how to get it running.

## Update

Since this skeleton is a starting point for your application and various files
would have been modified as per your needs, there isn't a way to provide
automated upgrades, so you have to do any updates manually.

## Configuration

Read and edit the environment specific `config/app_local.php` and set up the
`'Datasources'` and any other configuration relevant for your application.
Other environment agnostic settings can be changed in `config/app.php`.

## Layout

The app skeleton uses [Milligram](https://milligram.io/) (v1.3) minimalist CSS
framework by default. You can, however, replace it with any other library or
custom styles.

## Database roles and row-level security (RLS-SEC-001)

The application connects as `kualitet_app`, a login that owns no table and has no `BYPASSRLS`; every table has
`FORCE ROW LEVEL SECURITY` (ADR-0005 section 1, layer 2). Migrations run as `kualitet_owner` (owns the tables,
`BYPASSRLS`). The `SECURITY DEFINER` functions belong to `kualitet_definer` (`NOLOGIN`, `BYPASSRLS`).

```bash
# once per cluster and database, as a PostgreSQL superuser (PG* variables or ~/.pgpass); idempotent, prints no password
php bin/provision_db_roles.php --write-local      # generates the owner password into config/app_local.php

# migrations always use the owner connection
bin/cake migrations migrate -c migrations
```

In production set `ALTER ROLE kualitet_owner NOLOGIN` after the migration window. Re-running the provisioning script
KEEPS that state (it never re-enables `LOGIN` by itself); pass `--owner-login` to turn `LOGIN` back on before the next
migration window or before creating an account. The script warns on stderr when it creates `kualitet_app` without a
password (set `KUALITET_APP_PASSWORD` or `Datasources.default.password` and run it again).

Safeguards around the runtime role:

- `bin/cake create_user` is a bootstrap: it runs `user_create()` through the `migrations` (owner) connection, because
  `kualitet_app` has no EXECUTE on that function. It therefore needs the owner login to be enabled.
- The sign-in audit trail and the spending of TOTP steps and backup codes go through the `auth_side` connection (same role
  as `default`, autocommit; copied from `default` in `config/bootstrap.php`, `test_auth_side` from `test`), so they persist
  when the request transaction rolls back. Override it in `config/app_local.php` only if it must differ from `default`.
- The first request or job of a process checks that `default` is not the table owner, `BYPASSRLS` or a superuser
  (`App\Database\RuntimeRoleGuard`) and fails otherwise. `DevelopmentContext::commit()` raises when the transaction had
  been aborted instead of reporting a silent rollback as success.
The test suite migrates through `test_migrations` and runs as the runtime role, so the policies are exercised for real.
New scoped tables get their policies from `App\Database\RowLevelSecurity` (see its class comment). A request or job tells
PostgreSQL who acts only through `App\Database\DevelopmentContext` (`app.user_id`, `app.context`, `app.job_dev_ids`).
