Param(
  [string]$Base64Path = ".\dist\mpa-books-2026-theme.zip.b64",
  [string]$OutputZip = ".\dist\mpa-books-2026-theme.zip"
)

if (-not (Test-Path $Base64Path)) {
  throw "Base64 file not found: $Base64Path"
}

$targetDir = Split-Path -Parent $OutputZip
if ($targetDir -and -not (Test-Path $targetDir)) {
  New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
}

$b64 = (Get-Content -Path $Base64Path -Raw).Trim()
[IO.File]::WriteAllBytes($OutputZip, [Convert]::FromBase64String($b64))
Write-Host "Wrote ZIP to $OutputZip"
