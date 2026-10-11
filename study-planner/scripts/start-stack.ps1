param([string]$BaseUrl)

$ErrorActionPreference = 'Stop'
$projectPath = Split-Path $PSScriptRoot -Parent
$composeExecutable = 'C:/Program Files/Docker/Docker/resources/cli-plugins/docker-compose.exe'
if (!(Test-Path -LiteralPath $composeExecutable)) {
    throw 'Docker Desktop Compose was not found. Install Docker Desktop before starting this stack.'
}

Push-Location $projectPath
try {
    if (!$BaseUrl) {
        $mobileEnvironment = Join-Path $projectPath '.env.mobile'
        $apiUrl = if (Test-Path -LiteralPath $mobileEnvironment) {
            Select-String -LiteralPath $mobileEnvironment -Pattern '^VITE_API_BASE_URL=(https?://[^\s]+?)/api/?\s*$'
        }
        $BaseUrl = if ($apiUrl) { $apiUrl.Matches[0].Groups[1].Value } else { 'http://localhost:8000' }
    }
    $BaseUrl = $BaseUrl.TrimEnd('/')
    & $composeExecutable config --quiet
    if ($LASTEXITCODE -ne 0) { throw 'The Docker configuration is invalid.' }
    & $composeExecutable up -d --build
    if ($LASTEXITCODE -ne 0) {
        throw 'Docker could not start the stack. Check Docker engine access and its startup output.'
    }
    & $composeExecutable exec -T app php artisan migrate:status
    if ($LASTEXITCODE -ne 0) {
        throw 'PostgreSQL is not ready. Check the app logs and database credentials in .env.'
    }

    # An unknown account must return a credential validation error, not HTTP 500.
    $probeEmail = [Guid]::NewGuid().ToString('N') + '@example.invalid'
    $body = @{ email = $probeEmail; password = 'StackProbe123!' } | ConvertTo-Json
    try {
        Invoke-WebRequest -UseBasicParsing -Uri "$BaseUrl/api/auth/login" -Method Post -ContentType 'application/json' -Headers @{ Accept = 'application/json' } -Body $body | Out-Null
        throw 'The unknown-account login probe unexpectedly succeeded.'
    } catch {
        if (!$_.Exception.Response -or [int]$_.Exception.Response.StatusCode -ne 422) { throw }
    }
    Write-Output "PostgreSQL migrations and the login endpoint passed. Web: $BaseUrl"
} finally {
    Pop-Location
}
