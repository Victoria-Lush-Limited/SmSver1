#Requires -Version 5.1
<# Same env as backend: VLL_DEPLOY_HOST, VLL_DEPLOY_USER, VLL_DEPLOY_KEY. Optional: scripts/.deploy.env #>
$ErrorActionPreference = "Stop"
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$root = Split-Path -Parent $here
$envFile = Join-Path $here ".deploy.env"
if (Test-Path $envFile) {
  Get-Content $envFile | ForEach-Object {
    $line = $_.Trim()
    if ($line -match '^\s*#' -or $line -eq "") { return }
    $eq = $line.IndexOf("=")
    if ($eq -gt 0) {
      $k = $line.Substring(0, $eq).Trim()
      $v = $line.Substring($eq + 1).Trim()
      if ($v.StartsWith('"') -and $v.EndsWith('"')) { $v = $v.Substring(1, $v.Length - 2) }
      [Environment]::SetEnvironmentVariable($k, $v, "Process")
    }
  }
}
$bash = @(
  "C:\Program Files\Git\bin\bash.exe",
  "C:\Program Files (x86)\Git\bin\bash.exe"
) | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $bash) { Write-Error "Install Git for Windows (bash)." }
Push-Location $root
try { & $bash -lc "scripts/deploy-smsver1-remote.sh" } finally { Pop-Location }
