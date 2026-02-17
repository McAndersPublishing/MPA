param(
    [string]$ThemeSlug = 'mpa-books-2026'
)

$ErrorActionPreference = 'Stop'

$rootDir = Split-Path -Parent $PSScriptRoot
$themeDir = Join-Path $rootDir "wordpress/wp-content/themes/$ThemeSlug"
$distDir = Join-Path $rootDir 'dist'
$zipPath = Join-Path $distDir "$ThemeSlug-theme.zip"

if (-not (Test-Path -Path $themeDir -PathType Container)) {
    throw "Theme directory not found: $themeDir"
}

New-Item -ItemType Directory -Path $distDir -Force | Out-Null
if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

$stagingRoot = Join-Path ([System.IO.Path]::GetTempPath()) ("$ThemeSlug-zip-" + [System.Guid]::NewGuid().ToString('N'))
$stagingTheme = Join-Path $stagingRoot $ThemeSlug

try {
    New-Item -ItemType Directory -Path $stagingTheme -Force | Out-Null

    Copy-Item -Path (Join-Path $themeDir '*') -Destination $stagingTheme -Recurse -Force

    $excludeNames = @('.DS_Store', '__MACOSX', '.git', 'node_modules')
    foreach ($name in $excludeNames) {
        Get-ChildItem -Path $stagingTheme -Recurse -Force -ErrorAction SilentlyContinue |
            Where-Object { $_.Name -eq $name } |
            Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
    }

    Compress-Archive -Path $stagingTheme -DestinationPath $zipPath -CompressionLevel Optimal
}
finally {
    if (Test-Path $stagingRoot) {
        Remove-Item $stagingRoot -Recurse -Force
    }
}

Write-Host "Created: $zipPath"
