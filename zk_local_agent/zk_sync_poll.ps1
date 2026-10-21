<#
.SYNOPSIS
    "Sync ZKT now" listener - runs on the local BioTime server every minute
    (Task Scheduler, via run_zk_sync_poll_silent.vbs).

.DESCRIPTION
    Asks the live app (zk_sync_request.php) whether someone clicked "Sync ZKT"
    on the Device Monitor page. If yes, runs zk_biotime_export.ps1 right away
    (with -Resync for a full re-sync request) and reports the result back, so
    the button shows "N new punches". Every poll also tells the live app the
    agent is alive ("ZKT server: connected").

    Only outgoing HTTPS calls to the live app - nothing is opened on the office
    network. Uses the same $LiveSyncUrl / $LiveSyncSecret as zk_biotime_export.ps1
    (read from that file - configure them once, there).

    Cheap: one small request per minute, no database access unless a sync was
    requested. If a sync is already running (lock file present) it waits for
    the next minute.

    SCHEDULING (Windows Task Scheduler, separate task "ZKT Sync Poll"):
      Program/script:  C:\Windows\System32\wscript.exe
      Add arguments:   "C:\ZKSync\run_zk_sync_poll_silent.vbs"
      Start in:        C:\ZKSync
      Trigger:         Daily, repeat every 1 minute, indefinitely.
      Settings:        "If the task is already running: Do not start a new instance".
#>

$PollLogFile = "$PSScriptRoot\zk_sync_poll.log"
$ExportScript = "$PSScriptRoot\zk_biotime_export.ps1"
$LockFile = "$PSScriptRoot\zk_biotime_export.lock"

function Write-PollLog($message) {
    $line = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') - $message"
    Write-Host $line
    Add-Content -Path $PollLogFile -Value $line
}

if (-not (Test-Path $ExportScript)) {
    Write-PollLog "ERROR: zk_biotime_export.ps1 not found next to this script."
    exit 1
}

# --- Settings read (not executed) from zk_biotime_export.ps1 ---
$mainText = Get-Content -Path $ExportScript -Raw
function Get-Setting($name) {
    $m = [regex]::Match($mainText, '(?m)^\s*\$' + $name + '\s*=\s*"([^"]*)"')
    if ($m.Success) { return $m.Groups[1].Value }
    return $null
}
$LiveSyncUrl = Get-Setting 'LiveSyncUrl'
$LiveSyncSecret = Get-Setting 'LiveSyncSecret'
if (-not $LiveSyncUrl -or -not $LiveSyncSecret) {
    Write-PollLog "ERROR: could not read LiveSyncUrl / LiveSyncSecret from zk_biotime_export.ps1."
    exit 1
}
# https://domain/zk_sync_import.php -> https://domain/zk_sync_request.php
$RequestUrl = $LiveSyncUrl -replace 'zk_sync_import\.php$', 'zk_sync_request.php'
# Same TEMP self-signed-cert allowance as zk_biotime_export.ps1 while it still uses -k
$insecure = $mainText -match '"-k"'

function Invoke-Live([hashtable]$fields) {
    $curlArgs = @("-s", "-S", "-X", "POST", $RequestUrl, "-H", "X-Sync-Secret: $LiveSyncSecret", "-H", "Expect:", "--max-time", "30")
    foreach ($k in $fields.Keys) {
        $curlArgs += @("--data-urlencode", "$k=$($fields[$k])")
    }
    if ($insecure) { $curlArgs += "-k" }
    $text = & curl.exe @curlArgs
    if ($LASTEXITCODE -ne 0) {
        Write-PollLog "ERROR: could not reach $RequestUrl (curl exit $LASTEXITCODE)."
        return $null
    }
    try {
        return ($text | ConvertFrom-Json)
    } catch {
        Write-PollLog "ERROR: unexpected response from $RequestUrl - $text"
        return $null
    }
}

# A sync (scheduled or previous request) is still running -> check again next minute.
# Polling now would claim the request while the export would just skip on its lock.
if (Test-Path $LockFile) {
    $lockAge = (Get-Date) - (Get-Item $LockFile).LastWriteTime
    if ($lockAge.TotalMinutes -lt 120) { exit 0 }
}

$poll = Invoke-Live @{ action = 'poll' }
if ($null -eq $poll) { exit 1 }
if ($poll.status -ne 'success') {
    Write-PollLog "ERROR: live app refused the poll - $($poll.message)"
    exit 1
}
if ($null -eq $poll.request) { exit 0 }   # nothing requested - the normal case

$requestId = [string]$poll.request.id
$mode = [string]$poll.request.mode
Write-PollLog "Sync requested from the app (id $requestId, mode $mode) - running zk_biotime_export.ps1$(if ($mode -eq 'resync') { ' -Resync' })."

$psArgs = @("-NoProfile", "-ExecutionPolicy", "Bypass", "-WindowStyle", "Hidden", "-File", $ExportScript)
if ($mode -eq 'resync') { $psArgs += "-Resync" }
Remove-Item "$PSScriptRoot\last_run.json" -Force -ErrorAction SilentlyContinue
$proc = Start-Process -FilePath "powershell.exe" -ArgumentList $psArgs -WorkingDirectory $PSScriptRoot -WindowStyle Hidden -Wait -PassThru
$exitCode = $proc.ExitCode

$run = $null
if (Test-Path "$PSScriptRoot\last_run.json") {
    try { $run = Get-Content "$PSScriptRoot\last_run.json" -Raw | ConvertFrom-Json } catch { $run = $null }
}
$ok = ($exitCode -eq 0) -and ($null -ne $run) -and $run.ok
$message = if ($run -and $run.message) { [string]$run.message } elseif (-not $ok) { "Export exited with code $exitCode - see zk_biotime_export.log" } else { '' }

$report = Invoke-Live @{
    action     = 'report'
    id         = $requestId
    ok         = $(if ($ok) { '1' } else { '0' })
    sent       = $(if ($run) { [string]$run.sent } else { '0' })
    inserted   = $(if ($run) { [string]$run.inserted } else { '0' })
    duplicates = $(if ($run) { [string]$run.duplicates } else { '0' })
    last_id    = $(if ($run) { [string]$run.last_id } else { '' })
    message    = $message
}
Write-PollLog "Sync $(if ($ok) { 'finished' } else { 'FAILED' }): $(if ($run) { "$($run.inserted) new of $($run.sent) sent" } else { 'no result' })$(if ($message) { " - $message" }). Reported: $(if ($report -and $report.status -eq 'success') { 'yes' } else { 'NO' })."
if ($ok) { exit 0 } else { exit 1 }
