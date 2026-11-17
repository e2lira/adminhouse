# PHP 7.4 PHPUnit Runner

## Purpose

`vendor/phpunit/phpunit/phpunit` is locked at PHPUnit 6.5.14. This project runner uses a caller-supplied PHP 7.4 executable without changing Composer, CakePHP, PHPUnit, `phpunit.xml.dist`, or production PHP requirements.

The Docker CLI is installed in the development environment, but its daemon was unavailable when this runner was prepared. Therefore this repository provides an explicit local-PHP wrapper rather than an unverified Docker image.

## Preconditions

1. Provide a PHP executable with `PHP_VERSION_ID` from `70400` through `79999`.
2. Enable the `intl` and `pdo_sqlite` extensions in that PHP installation.
3. Keep the existing `vendor/` directory and `phpunit.xml.dist` present.

The wrapper exports `KUALITET_TEST_MODE=1` and `KUALITET_TEST_DATABASE_URL=sqlite:///:memory:`. `tests/TestDatabaseBootstrap.php` remains the fail-closed authority: it rejects a missing URL and non-test datasource names before CakePHP can use ORM tables.

## Commands

Run the smoke check first. It only lists the existing PHPUnit suites:

```powershell
$env:KUALITET_PHP74 = 'C:\path\to\php74\php.exe'
.\tools\run-php74-phpunit.ps1 -Mode Smoke
```

Only after that command succeeds, run the bounded focused suite:

```powershell
.\tools\run-php74-phpunit.ps1 -Mode Focused
```

`Focused` runs the smoke check again and stops if it fails, then executes only:

- `tests/TestCase/Controller/UsersControllerTest.php`
- `tests/TestCase/Controller/PagoControllerTest.php`

It does not execute a full suite and does not dispatch `PagoController::index()` or `PagoController::add()`; the focused payment test enforces that policy internally.

## Evidence discipline

Record the executable path/version (not secrets), extension output, the exact command, and exit code in `docs/test-results.md`. Do not mark G3 as passed until the focused command actually exits successfully. The login redirect assertions intentionally remain incomplete until a successful isolated run records CakePHP's exact `Location` values.

## Portable PHP 7.4 provisioning (2026-11-16)

`tools/setup-php74-portable.ps1` downloads the official `php-7.4.33-nts-Win32-vc15-x64.zip` from windows.php.net into `tools/php74`, writes a `php.ini` with the required extensions, verifies the runtime, sets `KUALITET_PHP74` for that process, and then calls this wrapper.

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\setup-php74-portable.ps1 -Run Smoke
powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\setup-php74-portable.ps1 -Run Focused
```

It does not modify PATH, the registry, or the system PHP. `tools/php74` is a local development runtime only and must not be deployed.

Update 2026-11-16: `Focused` runs one PHPUnit invocation per file because PHPUnit 6.5.14 accepts a single positional test path. The login redirect assertions are now complete; see `docs/test-results.md`.
