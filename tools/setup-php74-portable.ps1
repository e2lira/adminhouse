[CmdletBinding()]
param(
    # What to run after provisioning: nothing, the suite-list smoke check, or the focused Users/Pago tests.
    [ValidateSet('None', 'Smoke', 'Focused')]
    [string]$Run = 'Smoke',

    [string]$Version = '7.4.33',

    # Re-download and re-extract even if tools\php74\php.exe already exists.
    [switch]$Force
)

# Provisions a portable PHP 7.4 (official windows.php.net NTS x64 build) under tools\php74.
# It does not touch PATH, the registry, or the system PHP. Remove it by deleting tools\php74.

$ErrorActionPreference = 'Stop'
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

$repositoryRoot = Split-Path -Parent $PSScriptRoot
$phpDir = Join-Path $PSScriptRoot 'php74'
$phpExe = Join-Path $phpDir 'php.exe'
$zipName = "php-$Version-nts-Win32-vc15-x64.zip"
$zipPath = Join-Path $PSScriptRoot $zipName
$urls = @(
    "https://windows.php.net/downloads/releases/archives/$zipName",
    "https://windows.php.net/downloads/releases/$zipName"
)

if ($Force -or -not (Test-Path -LiteralPath $phpExe -PathType Leaf)) {
    $downloaded = $false
    foreach ($url in $urls) {
        try {
            Write-Host "Downloading $url"
            Invoke-WebRequest -Uri $url -OutFile $zipPath -UseBasicParsing -UserAgent 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
            $downloaded = $true
            break
        }
        catch {
            Write-Warning "Download failed from ${url}: $($_.Exception.Message)"
        }
    }
    if (-not $downloaded) {
        throw "Could not download $zipName from windows.php.net. Nothing was installed."
    }

    $hash = (Get-FileHash -LiteralPath $zipPath -Algorithm SHA256).Hash
    Write-Host "SHA256 ($zipName): $hash"

    if (Test-Path -LiteralPath $phpDir) {
        $backup = "$phpDir.old-$(Get-Date -Format 'yyyyMMddHHmmss')"
        Write-Host "Existing tools\php74 moved to $backup"
        Move-Item -LiteralPath $phpDir -Destination $backup
    }
    Expand-Archive -LiteralPath $zipPath -DestinationPath $phpDir
    if (-not (Test-Path -LiteralPath $phpExe -PathType Leaf)) {
        throw "php.exe was not found after extracting $zipName."
    }
}
else {
    Write-Host "Portable PHP already present: $phpExe (use -Force to reinstall)."
}

# php.ini: development defaults plus the extensions CakePHP 3.9 and this application need.
$iniPath = Join-Path $phpDir 'php.ini'
$iniTemplate = Join-Path $phpDir 'php.ini-development'
if (-not (Test-Path -LiteralPath $iniTemplate -PathType Leaf)) {
    throw "php.ini-development is missing in $phpDir."
}
$extDir = Join-Path $phpDir 'ext'
$extensions = @('curl', 'fileinfo', 'gd2', 'intl', 'mbstring', 'mysqli', 'openssl', 'pdo_mysql', 'pdo_pgsql', 'pdo_sqlite', 'pgsql', 'sqlite3')
$iniLines = @(Get-Content -LiteralPath $iniTemplate)
$iniLines += ''
$iniLines += '; --- Added by tools/setup-php74-portable.ps1 ---'
$iniLines += "extension_dir = `"$extDir`""
foreach ($extension in $extensions) {
    $iniLines += "extension=$extension"
}
$iniLines += 'date.timezone = America/Mexico_City'
$iniLines += 'memory_limit = 1024M'
Set-Content -LiteralPath $iniPath -Value $iniLines -Encoding ASCII

# Verify the runtime before handing it to the PHPUnit wrapper.
$versionText = & $phpExe -r 'echo PHP_VERSION;'
if ($LASTEXITCODE -ne 0) {
    throw 'php.exe failed to start. Install the Microsoft Visual C++ 2015-2019 x64 Redistributable and run this script again.'
}
Write-Host "PHP version: $versionText"

$loaded = @(& $phpExe -m | ForEach-Object { $_.Trim().ToLowerInvariant() })
$required = @('intl', 'pdo_sqlite', 'mbstring', 'openssl', 'gd', 'fileinfo')
$missing = @($required | Where-Object { $_ -notin $loaded })
if ($missing.Count -gt 0) {
    throw "Portable PHP is missing extensions: $($missing -join ', ')."
}
Write-Host "Extensions OK: $($required -join ', ')"

$env:KUALITET_PHP74 = $phpExe
Write-Host ''
Write-Host 'To reuse this runtime in another PowerShell session:'
Write-Host "  `$env:KUALITET_PHP74 = '$phpExe'"

if ($Run -ne 'None') {
    Write-Host ''
    Write-Host "Running tools\run-php74-phpunit.ps1 -Mode $Run"
    & (Join-Path $PSScriptRoot 'run-php74-phpunit.ps1') -Mode $Run
}
