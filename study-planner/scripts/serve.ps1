param(
    [string]$HostAddress = '0.0.0.0',
    [int]$Port = 8001
)

$ErrorActionPreference = 'Stop'
$projectPath = Split-Path $PSScriptRoot -Parent
$phpExecutable = (Get-Command php).Source
$extensionPath = Join-Path (Split-Path $phpExecutable -Parent) 'ext'
$phpArguments = @()

# Docker's service name is only resolvable inside its network. A host PHP
# server uses the published PostgreSQL port, while explicit overrides win.
$environmentPath = Join-Path $projectPath '.env'
if (!$env:DB_HOST -and (Select-String -LiteralPath $environmentPath -Pattern '^DB_HOST=postgres\s*$' -Quiet)) {
    $env:DB_HOST = '127.0.0.1'
    if (!$env:DB_PORT) {
        $publishedPort = Select-String -LiteralPath $environmentPath -Pattern '^POSTGRES_PORT=(\d+)\s*$'
        $env:DB_PORT = if ($publishedPort) { $publishedPort.Matches[0].Groups[1].Value } else { '15432' }
    }
}

if ((php --ini) -match 'Loaded Configuration File:\s+\(none\)') {
    $phpArguments += @('-d', "extension_dir=$extensionPath")
    foreach ($extension in @('pdo_pgsql', 'pdo_sqlite', 'mbstring', 'openssl', 'fileinfo', 'curl')) {
        if (Test-Path -LiteralPath (Join-Path $extensionPath "php_$extension.dll")) {
            $phpArguments += @('-d', "extension=$extension")
        }
    }
}

Push-Location (Join-Path $projectPath 'public')
try {
    $routerPath = Join-Path $projectPath 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'
    & $phpExecutable @phpArguments -S "${HostAddress}:$Port" -t . $routerPath
    exit $LASTEXITCODE
} finally {
    Pop-Location
}
