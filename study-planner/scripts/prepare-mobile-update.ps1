param(
    [Parameter(Mandatory = $true)]
    [string]$ApiBaseUrl
)

$ErrorActionPreference = 'Stop'
$taskRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$previousApiUrl = $env:VITE_API_BASE_URL
Push-Location $taskRoot
try {
    & node scripts/check-production-api.mjs $ApiBaseUrl
    if ($LASTEXITCODE -ne 0) { throw 'Backend checks failed. Mobile assets were not rebuilt.' }

    $env:VITE_API_BASE_URL = $ApiBaseUrl
    & npm.cmd run mobile:build
    if ($LASTEXITCODE -ne 0) { throw 'Mobile build or Capacitor sync failed.' }

    Write-Host 'Verified endpoint embedded; Android assets synchronized.'
    Write-Host 'Next: generate an APK in Android Studio with the same signing key as the installed app.'
    Write-Host 'This command does not sign, install, or claim successful phone login.'
} finally {
    $env:VITE_API_BASE_URL = $previousApiUrl
    Pop-Location
}
