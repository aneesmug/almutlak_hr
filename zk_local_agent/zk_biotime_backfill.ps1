<#
.SYNOPSIS
    One-off FULL re-sync of BioTime punches into the live HR app (e.g. after the
    live attendance tables were emptied). Runs ONLY on the local BioTime server,
    next to zk_biotime_export.ps1.

.DESCRIPTION
    zk_biotime_export.ps1 (the every-minute task) only ever sends the NEWEST
    $BatchSize punches, newest first - perfect for keeping up, but it can never
    refill history: after the live tables are emptied it would just keep
    re-sending the same latest batch. This script walks BioTime's
    iclock_transaction FORWARD from -From, oldest first, in batches, posting each
    batch to the same zk_sync_import.php endpoint (which inserts the punches,
    folds them into the daily `attendance` table and skips duplicates), until it
    reaches the newest punch.

    Resumable: the last BioTime id sent is saved in backfill_state.json after
    every successful batch. Re-running continues where it stopped (a network
    error just stops the run - run it again). Use -Reset to start over.
    Duplicates are harmless (UNIQUE keys + INSERT IGNORE on the live side).

    Settings ($MssqlConnectionString, $LiveSyncUrl, $LiveSyncSecret) are READ from
    zk_biotime_export.ps1 in the same folder - configure them once, there.

    Punches older than the live app's retention window (App Settings >
    Attendance Config > Data Retention) are skipped by the live app, so raise
    retention FIRST if you want more history than that.

.EXAMPLE
    powershell -NoProfile -ExecutionPolicy Bypass -File .\zk_biotime_backfill.ps1 -From 2026-08-22
    powershell -NoProfile -ExecutionPolicy Bypass -File .\zk_biotime_backfill.ps1 -From 2026-08-22 -Reset
#>
param(
    [Parameter(Mandatory = $true)][string]$From,   # yyyy-MM-dd, oldest punch date to send
    [int]$BatchSize = 2000,
    [switch]$Reset
)

$LogFile = "$PSScriptRoot\zk_biotime_backfill.log"
$StateFile = "$PSScriptRoot\backfill_state.json"

function Write-Log($message) {
    $line = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') - $message"
    Write-Output $line
    Add-Content -Path $LogFile -Value $line
}

# Same lone-surrogate cleanup as zk_biotime_export.ps1 (PHP's json_decode rejects them)
function Remove-LoneSurrogates([string]$s) {
    if ([string]::IsNullOrEmpty($s)) { return $s }
    $sb = New-Object System.Text.StringBuilder
    for ($i = 0; $i -lt $s.Length; $i++) {
        $c = $s[$i]
        if ([char]::IsHighSurrogate($c)) {
            if (($i + 1) -lt $s.Length -and [char]::IsLowSurrogate($s[$i + 1])) {
                [void]$sb.Append($c); [void]$sb.Append($s[$i + 1]); $i++
            }
        } elseif (-not [char]::IsLowSurrogate($c)) {
            [void]$sb.Append($c)
        }
    }
    return $sb.ToString()
}

# --- Settings: read (not executed) from zk_biotime_export.ps1 ---
$mainScript = "$PSScriptRoot\zk_biotime_export.ps1"
if (-not (Test-Path $mainScript)) {
    Write-Log "ERROR: zk_biotime_export.ps1 not found next to this script."
    exit 1
}
$mainText = Get-Content -Path $mainScript -Raw
function Get-Setting($name) {
    $m = [regex]::Match($mainText, '(?m)^\s*\$' + $name + '\s*=\s*"([^"]*)"')
    if ($m.Success) { return $m.Groups[1].Value }
    return $null
}
$MssqlConnectionString = Get-Setting 'MssqlConnectionString'
$LiveSyncUrl = Get-Setting 'LiveSyncUrl'
$LiveSyncSecret = Get-Setting 'LiveSyncSecret'
if (-not $MssqlConnectionString -or -not $LiveSyncUrl -or -not $LiveSyncSecret) {
    Write-Log "ERROR: could not read MssqlConnectionString / LiveSyncUrl / LiveSyncSecret from zk_biotime_export.ps1."
    exit 1
}

try {
    $fromDate = [datetime]::ParseExact($From, 'yyyy-MM-dd', $null)
} catch {
    Write-Log "ERROR: -From must be yyyy-MM-dd (got '$From')."
    exit 1
}

# --- Resume state ---
$lastId = 0
$totals = @{ sent = 0; inserted = 0; duplicates = 0; batches = 0 }
if (-not $Reset -and (Test-Path $StateFile)) {
    $state = Get-Content $StateFile -Raw | ConvertFrom-Json
    if ($state.from -eq $From) {
        $lastId = [long]$state.last_id
        $totals.sent = [long]$state.sent; $totals.inserted = [long]$state.inserted
        $totals.duplicates = [long]$state.duplicates; $totals.batches = [long]$state.batches
        Write-Log "Resuming backfill from $From after BioTime id $lastId ($($totals.sent) punches already sent)."
    }
}
if ($lastId -eq 0) {
    Write-Log "Starting backfill from $From -> $LiveSyncUrl (batch size $BatchSize)."
}

function Save-State {
    @{ from = $From; last_id = $lastId; sent = $totals.sent; inserted = $totals.inserted;
       duplicates = $totals.duplicates; batches = $totals.batches; updated = (Get-Date -Format 'yyyy-MM-dd HH:mm:ss') } |
        ConvertTo-Json | Set-Content -Path $StateFile -Encoding UTF8
}

$curlPath = "curl.exe"
if (-not (Get-Command $curlPath -ErrorAction SilentlyContinue)) {
    Write-Log "ERROR: curl.exe not found."
    exit 1
}

try {
    $connection = New-Object System.Data.SqlClient.SqlConnection($MssqlConnectionString)
    $connection.Open()
} catch {
    Write-Log "ERROR: could not connect to MSSQL - $($_.Exception.Message)"
    exit 1
}

while ($true) {
    # Oldest first, strictly after the last id sent
    $punches = @()
    $maxId = $lastId
    $lastTime = ''
    try {
        $cmd = $connection.CreateCommand()
        $cmd.CommandText = "
            SELECT TOP ($BatchSize) id, emp_code, punch_time, punch_state, verify_type, work_code, terminal_sn
            FROM iclock_transaction
            WHERE punch_time >= @from AND id > @lastId
            ORDER BY id ASC"
        [void]$cmd.Parameters.Add('@from', [System.Data.SqlDbType]::DateTime)
        $cmd.Parameters['@from'].Value = $fromDate
        [void]$cmd.Parameters.Add('@lastId', [System.Data.SqlDbType]::BigInt)
        $cmd.Parameters['@lastId'].Value = $lastId
        $cmd.CommandTimeout = 300
        $reader = $cmd.ExecuteReader()
        while ($reader.Read()) {
            $id = [long]$reader["id"]
            if ($id -gt $maxId) { $maxId = $id }
            $lastTime = ([datetime]$reader["punch_time"]).ToString("yyyy-MM-dd HH:mm:ss")
            $punches += [PSCustomObject]@{
                terminal_sn = Remove-LoneSurrogates ([string]$reader["terminal_sn"])
                pin         = Remove-LoneSurrogates ([string]$reader["emp_code"])
                punch_time  = $lastTime
                status      = Remove-LoneSurrogates ([string]$reader["punch_state"])
                verify      = Remove-LoneSurrogates ([string]$reader["verify_type"])
                work_code   = Remove-LoneSurrogates ([string]$reader["work_code"])
            }
        }
        $reader.Close()
    } catch {
        Write-Log "ERROR: punch query failed - $($_.Exception.Message)"
        $connection.Close()
        exit 1
    }

    if ($punches.Count -eq 0) {
        Write-Log "DONE. $($totals.batches) batch(es), $($totals.sent) punches sent, $($totals.inserted) inserted, $($totals.duplicates) duplicates skipped."
        break
    }

    $bodyJson = @{ punches = $punches; terminals = @() } | ConvertTo-Json -Depth 5
    $tempFile = [System.IO.Path]::GetTempFileName()
    [System.IO.File]::WriteAllText($tempFile, $bodyJson, (New-Object System.Text.UTF8Encoding($false)))
    $curlArgs = @(
        "-s", "-S", "-X", "POST", $LiveSyncUrl,
        "-H", "Content-Type: application/json; charset=utf-8",
        "-H", "X-Sync-Secret: $LiveSyncSecret",
        "-H", "Expect:",
        "--data-binary", "@$tempFile",
        "-k",
        "--max-time", "300"
    )
    $responseText = & $curlPath @curlArgs
    $curlExitCode = $LASTEXITCODE
    Remove-Item $tempFile -Force -ErrorAction SilentlyContinue

    if ($curlExitCode -ne 0) {
        Write-Log "ERROR: curl failed (exit $curlExitCode) - run the script again to resume after id $lastId."
        $connection.Close()
        exit 1
    }
    try {
        $response = $responseText | ConvertFrom-Json
    } catch {
        Write-Log "ERROR: response is not JSON - run again to resume. Raw: $($responseText | Out-String)"
        $connection.Close()
        exit 1
    }
    if ($response.status -ne "success") {
        Write-Log "ERROR: live app rejected batch - $($response | ConvertTo-Json -Compress). Run again to resume after id $lastId."
        $connection.Close()
        exit 1
    }

    $lastId = $maxId
    $totals.batches++
    $totals.sent += $punches.Count
    $totals.inserted += [long]$response.inserted
    $totals.duplicates += [long]$response.skipped_duplicate
    Save-State
    Write-Log "Batch $($totals.batches): sent $($punches.Count) (up to $lastTime), inserted $($response.inserted), duplicates $($response.skipped_duplicate). Total sent $($totals.sent)."
    Start-Sleep -Milliseconds 500
}

$connection.Close()
