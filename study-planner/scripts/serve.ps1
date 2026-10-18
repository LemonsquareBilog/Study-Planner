param(
    [string]$HostAddress = '127.0.0.1',
    [int]$Port = 8000,
    [switch]$CheckOnly,
    [string]$PhpPath = ''
)

$ErrorActionPreference = 'Stop'
$projectPath = Split-Path $PSScriptRoot -Parent
$requiredExtensions = @('pdo_sqlite', 'mbstring', 'openssl', 'fileinfo', 'curl')
$candidates = if ($PhpPath) { @($PhpPath) } else {
    @((Get-Command php -All -ErrorAction SilentlyContinue).Source) +
    @(Get-ChildItem -Path (Join-Path $env:LOCALAPPDATA 'Microsoft/WinGet/Packages/PHP.PHP.*/php.exe') -ErrorAction SilentlyContinue | Select-Object -ExpandProperty FullName)
}
$phpExecutable = $null
foreach ($candidate in ($candidates | Select-Object -Unique)) {
    if (!$candidate -or !(Test-Path -LiteralPath $candidate)) { continue }
    $candidateExtensions = Join-Path (Split-Path $candidate -Parent) 'ext'
    $candidateModules = (& $candidate -n -r "echo implode(',', get_loaded_extensions());") -join ''
    $missing = @($requiredExtensions | Where-Object {
        $candidateModules -notmatch "(^|,)$_(,|$)" -and
        !(Test-Path -LiteralPath (Join-Path $candidateExtensions "php_$_.dll"))
    })
    if ($LASTEXITCODE -eq 0 -and $missing.Count -eq 0) {
        $phpExecutable = $candidate
        $extensionPath = $candidateExtensions
        $loadedExtensions = $candidateModules
        break
    }
}
if (!$phpExecutable) { throw 'No installed PHP runtime has the required local SQLite/extensions. No database was changed.' }
$phpArguments = @('-n', '-d', "extension_dir=$extensionPath", '-d', 'memory_limit=256M', '-d', 'display_errors=0')

if ($HostAddress -notin @('127.0.0.1', 'localhost', '::1')) {
    throw 'The local demo server must bind to loopback. Use the deployed HTTPS backend for remote/mobile access.'
}
# Use a project-specific runtime instead of inheriting Herd's broken PostgreSQL
# extension paths or a CLI installation with no php.ini. These same flags reach
# the actual HTTP worker; artisan serve can otherwise spawn a driver-less child.
foreach ($extension in $requiredExtensions) {
    if ($loadedExtensions -notmatch "(^|,)$extension(,|$)") {
        if (!(Test-Path -LiteralPath (Join-Path $extensionPath "php_$extension.dll"))) {
            throw "The selected PHP runtime is missing $extension. Install a compatible PHP runtime; no database was changed."
        }
        $phpArguments += @('-d', "extension=$extension")
    }
}

& $phpExecutable @phpArguments (Join-Path $projectPath 'scripts/check-local-runtime.php')
if ($LASTEXITCODE -ne 0) { throw 'Local runtime preflight failed. The web server was not started.' }
if ($CheckOnly) { exit 0 }

Push-Location (Join-Path $projectPath 'public')
try {
    $routerPath = Join-Path $projectPath 'scripts/local-router.php'
    Write-Output "Local demo web: http://${HostAddress}:$Port (separate development database; no seeding or reset)"
    & $phpExecutable @phpArguments -S "${HostAddress}:$Port" -t . $routerPath
    exit $LASTEXITCODE
} finally {
    Pop-Location
}
