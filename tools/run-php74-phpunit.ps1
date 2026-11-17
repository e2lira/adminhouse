[CmdletBinding()]
param(
    [ValidateSet('Smoke', 'Focused')]
    [string]$Mode = 'Smoke'
)

$ErrorActionPreference = 'Stop'

$repositoryRoot = Split-Path -Parent $PSScriptRoot
$phpUnit = Join-Path $repositoryRoot 'vendor/phpunit/phpunit/phpunit'
$configuration = Join-Path $repositoryRoot 'phpunit.xml.dist'

if ([string]::IsNullOrWhiteSpace($env:KUALITET_PHP74)) {
    throw 'KUALITET_PHP74 must point to a PHP 7.4 executable. No tests were run.'
}

if (-not (Test-Path -LiteralPath $env:KUALITET_PHP74 -PathType Leaf)) {
    throw "KUALITET_PHP74 does not exist: $($env:KUALITET_PHP74). No tests were run."
}

foreach ($requiredPath in @($phpUnit, $configuration)) {
    if (-not (Test-Path -LiteralPath $requiredPath -PathType Leaf)) {
        throw "Required PHPUnit runner file is missing: $requiredPath"
    }
}

$versionId = (& $env:KUALITET_PHP74 -r 'echo PHP_VERSION_ID;' 2>$null).Trim()
if ($LASTEXITCODE -ne 0 -or $versionId -notmatch '^\d+$') {
    throw 'Unable to determine the PHP version from KUALITET_PHP74. No tests were run.'
}

[int]$versionNumber = $versionId
if ($versionNumber -lt 70400 -or $versionNumber -ge 80000) {
    throw "PHP 7.4.x is required for locked PHPUnit 6.5.14; detected PHP_VERSION_ID=$versionNumber. No tests were run."
}

$extensions = & $env:KUALITET_PHP74 -m 2>$null
if ($LASTEXITCODE -ne 0) {
    throw 'Unable to list PHP extensions from KUALITET_PHP74. No tests were run.'
}

$extensionNames = @($extensions | ForEach-Object { $_.Trim().ToLowerInvariant() })
$missingExtensions = @('intl', 'pdo_sqlite') | Where-Object { $_ -notin $extensionNames }
if ($missingExtensions.Count -gt 0) {
    throw "PHP 7.4 runner is missing required extensions: $($missingExtensions -join ', '). No tests were run."
}

# The existing TestDatabaseBootstrap validates this signal before CakePHP uses ORM tables.
$env:KUALITET_TEST_MODE = '1'
$env:KUALITET_TEST_DATABASE_URL = 'sqlite:///:memory:'

Push-Location $repositoryRoot
try {
    Write-Host 'Running PHPUnit suite-list smoke check with the existing phpunit.xml.dist.'
    & $env:KUALITET_PHP74 $phpUnit '--configuration' $configuration '--list-suites'
    if ($LASTEXITCODE -ne 0) {
        throw "PHPUnit suite-list smoke check failed with exit code $LASTEXITCODE. Focused tests were not run."
    }

    if ($Mode -eq 'Focused') {
        $focusedTests = @(
            'tests/TestCase/Controller/UsersControllerTest.php',
            'tests/TestCase/Controller/PagoControllerTest.php'
        )

        Write-Host 'Smoke check passed. Running only UsersControllerTest and PagoControllerTest.'

        # PHPUnit 6.5 accepts a single positional test path, so each file needs its own invocation.
        $failedTests = @()
        foreach ($focusedTest in $focusedTests) {
            Write-Host "Running focused test file: $focusedTest"
            & $env:KUALITET_PHP74 $phpUnit '--configuration' $configuration $focusedTest
            if ($LASTEXITCODE -ne 0) {
                Write-Host "Focused test file failed with exit code ${LASTEXITCODE}: $focusedTest"
                $failedTests += $focusedTest
            }
        }

        if ($failedTests.Count -gt 0) {
            throw "Focused Users/Pago PHPUnit run failed for: $($failedTests -join ', ')."
        }
    }
}
finally {
    Pop-Location
}
