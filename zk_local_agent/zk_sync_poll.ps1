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

    Cheap: one small request every 5 seconds, no database access unless a sync
    was requested. If a sync is already running (lock file present) it tells the
    live app "busy" and picks the request up as soon as that run ends. While the
    export runs, every step (sync_progress.json) is forwarded to the live app so
    the Sync ZKT modal shows live status.

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

# Each Task Scheduler run (every minute) checks every $PollEverySeconds for ~$PollForSeconds,
# so a click on "Sync ZKT" is picked up within a few seconds, not up to a minute.
$PollEverySeconds = 5
$PollForSeconds = 50
$deadline = (Get-Date).AddSeconds($PollForSeconds)
$request = $null
while ($true) {
    # A sync (scheduled or previous request) is still running -> tell the live app we're
    # busy (the modal says so) but don't claim the request: the export would just skip
    # on its lock.
    $busy = $false
    if (Test-Path $LockFile) {
        $lockAge = (Get-Date) - (Get-Item $LockFile).LastWriteTime
        if ($lockAge.TotalMinutes -lt 120) { $busy = $true }
    }

    $poll = Invoke-Live @{ action = 'poll'; busy = $(if ($busy) { '1' } else { '0' }) }
    if ($null -eq $poll) { exit 1 }
    if ($poll.status -ne 'success') {
        Write-PollLog "ERROR: live app refused the poll - $($poll.message)"
        exit 1
    }
    if ($null -ne $poll.request) { $request = $poll.request; break }
    if ((Get-Date).AddSeconds($PollEverySeconds) -ge $deadline) { exit 0 }   # nothing requested - the normal case
    Start-Sleep -Seconds $PollEverySeconds
}

$requestId = [string]$request.id
$mode = [string]$request.mode
Write-PollLog "Sync requested from the app (id $requestId, mode $mode) - running zk_biotime_export.ps1$(if ($mode -eq 'resync') { ' -Resync' })."

$ProgressFile = "$PSScriptRoot\sync_progress.json"
$psArgs = @("-NoProfile", "-ExecutionPolicy", "Bypass", "-WindowStyle", "Hidden", "-File", $ExportScript)
if ($mode -eq 'resync') { $psArgs += "-Resync" }
Remove-Item "$PSScriptRoot\last_run.json", $ProgressFile -Force -ErrorAction SilentlyContinue
$proc = Start-Process -FilePath "powershell.exe" -ArgumentList $psArgs -WorkingDirectory $PSScriptRoot -WindowStyle Hidden -PassThru

# Forward every step the export writes to sync_progress.json (the Sync ZKT modal shows
# them); also a heartbeat every 30s so the live app knows the run is still alive.
$lastSent = ''
$lastSentAt = Get-Date
while (-not $proc.HasExited) {
    Start-Sleep -Milliseconds 1500
    $text = ''
    if (Test-Path $ProgressFile) {
        try { $text = [System.IO.File]::ReadAllText($ProgressFile).Trim() } catch { $text = '' }
    }
    if (($text -and $text -ne $lastSent) -or ((Get-Date) - $lastSentAt).TotalSeconds -ge 30) {
        $null = Invoke-Live @{ action = 'progress'; id = $requestId; progress = $(if ($text) { $text } else { $lastSent }) }
        if ($text) { $lastSent = $text }
        $lastSentAt = Get-Date
    }
}
$proc.WaitForExit()
$exitCode = $proc.ExitCode
Remove-Item $ProgressFile -Force -ErrorAction SilentlyContinue

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
