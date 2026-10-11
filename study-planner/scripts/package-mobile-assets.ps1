param(
    [string]$BaseApk = 'android/app/build/outputs/apk/debug/app-debug.apk',
    [string]$OutputApk = 'android/app/build/outputs/apk/debug/study-planner-updated.apk',
    [string]$BuildTools = 'C:/Users/Acer/AppData/Local/Android/Sdk/build-tools/36.0.0',
    [string]$DebugKeystore = 'C:/Users/Acer/.android/debug.keystore'
)

# Asset-only fallback for an existing debug APK when Gradle is unavailable.
# Native Java, resources and the compiled manifest are retained from BaseApk.
$ErrorActionPreference = 'Stop'
$projectPath = Split-Path $PSScriptRoot -Parent
$basePath = (Resolve-Path (Join-Path $projectPath $BaseApk)).Path
$outputPath = [IO.Path]::GetFullPath((Join-Path $projectPath $OutputApk))
if (!$outputPath.StartsWith($projectPath + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) {
    throw 'Output APK must be inside the project.'
}
if ($basePath -eq $outputPath) { throw 'Keep the original APK as a separate file.' }
$assetsPath = Join-Path $projectPath 'android/app/src/main/assets'
$config = Get-Content (Join-Path $assetsPath 'capacitor.config.json') -Raw | ConvertFrom-Json
if ($config.server.url) { throw 'Sync the bundled application before packaging; server.url must be absent.' }

Add-Type -AssemblyName System.IO.Compression.FileSystem
$source = [IO.Compression.ZipFile]::OpenRead($basePath)
$unsignedPath = $outputPath + '.unsigned'
$alignedPath = $outputPath + '.aligned'
try {
    $pluginReader = [IO.StreamReader]::new($source.GetEntry('assets/capacitor.plugins.json').Open())
    try { $oldPlugins = $pluginReader.ReadToEnd() | ConvertFrom-Json } finally { $pluginReader.Dispose() }
    $newPlugins = Get-Content (Join-Path $assetsPath 'capacitor.plugins.json') -Raw | ConvertFrom-Json
    if (Compare-Object ($oldPlugins.classpath | Sort-Object) ($newPlugins.classpath | Sort-Object)) {
        throw 'Native plugins changed; a full Gradle build is required.'
    }
    foreach ($temporaryPath in @($unsignedPath, $alignedPath)) {
        if (Test-Path -LiteralPath $temporaryPath) { Remove-Item -LiteralPath $temporaryPath }
    }
} finally { $source.Dispose() }

& python (Join-Path $PSScriptRoot 'package-mobile-assets.py') $basePath $assetsPath $unsignedPath
if ($LASTEXITCODE -ne 0) { throw 'APK asset packaging failed.' }

& (Join-Path $BuildTools 'zipalign.exe') -P 16 -f 4 $unsignedPath $alignedPath
if ($LASTEXITCODE -ne 0) { throw 'APK alignment failed.' }
& (Join-Path $BuildTools 'apksigner.bat') sign --ks $DebugKeystore --ks-key-alias androiddebugkey --ks-pass pass:android --key-pass pass:android --out $outputPath $alignedPath
if ($LASTEXITCODE -ne 0) { throw 'APK signing failed.' }
& (Join-Path $BuildTools 'apksigner.bat') verify --print-certs $outputPath
if ($LASTEXITCODE -ne 0) { throw 'APK signature verification failed.' }
& (Join-Path $BuildTools 'zipalign.exe') -c -P 16 4 $outputPath
if ($LASTEXITCODE -ne 0) { throw 'Signed APK alignment verification failed.' }
foreach ($temporaryPath in @($unsignedPath, $alignedPath)) { Remove-Item -LiteralPath $temporaryPath }
Get-Item -LiteralPath $outputPath | Select-Object FullName, Length, LastWriteTime
