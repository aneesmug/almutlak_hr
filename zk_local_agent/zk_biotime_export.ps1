<#
.SYNOPSIS
    Sends NEW attendance punches from BioTime's own MSSQL database to the live
    HR app over HTTPS. Runs ONLY on the local BioTime server, from Task
    Scheduler every 15 minutes.

.DESCRIPTION
    Does not touch BioTime itself, the ZK devices, or the router - this is a
    separate, standalone script. Each run:
      1. Takes a lock (zk_biotime_export.lock) so a slow run is never
         overlapped by the next scheduled one.
      2. Reads every device's live status from iclock_terminal (state,
         last_activity, user/fp/face/palm/transaction counts) - sent with the
         first request of every run so Online/Offline stays accurate even when
         there are no new punches.
      3. Sends every punch BioTime stored since the last run: iclock_transaction
         rows with id > the last id sent (saved in last_sent_id.txt), oldest
         first, in batches of $BatchSize, until caught up. BioTime's id grows
         with every stored punch, so punches a device uploads late (after being
         offline) are picked up too - nothing is missed however many punches
         arrive between runs. Only punches inside the sync window are read.
      4. Saves the last id after EVERY successful batch - a failed run just
         continues from there next time.

    Sync window = "Attendance days to sync & keep" in the live app (App
    Settings > Attendance Config > Sync Settings, default 180). The live app
    returns it with every response; it is remembered in last_sent_id.txt and
    used for the next run. The live app also deletes anything older once a day
    (rolling window: on day 181 the oldest day is removed).

    First run (no last_sent_id.txt) automatically sends the whole window.
    After the live attendance tables were emptied, run ONCE by hand with
    -Resync to send the whole window again (duplicates are skipped safely by
    the live app's UNIQUE keys):
        powershell -NoProfile -ExecutionPolicy Bypass -File .\zk_biotime_export.ps1 -Resync

    SETUP (fill these in before first run):
      - $MssqlConnectionString - point at BioTime's actual database.
      - $LiveSyncUrl - https://yourdomain.com/zk_sync_import.php
      - $LiveSyncSecret - the 'zk_sync_secret_key' value from the LIVE app's
        app_settings table.

    SCHEDULING (Windows Task Scheduler):
      Program/script:   run_zk_biotime_export.bat   (runs this hidden)
      Trigger:          Daily, repeat every 15 minutes, indefinitely.
      Settings:         "If the task is already running: Do not start a new instance".
#>
param(
    [switch]$Resync   # resend the whole sync window (after live tables were emptied)
)

# ---- SETUP: fill these in ----
$MssqlConnectionString  = "Server=192.168.9.185;Database=zkbiotime;User Id=zkbio;Password=@DmiN56539306#;"
$LiveSyncUrl            = "https://sys.almutlak.local/zk_sync_import.php"
$LiveSyncSecret         = "08d7b7df34b6fd1b7ea5dfe4aaf0c7cd00c34693d84e4d74b9d237bd1aa12f9b"
$BatchSize              = 2000   # punches per request
$MaxBatchesPerRun       = 100    # safety cap per run (200,000 punches) - the next run continues
$DefaultSyncDays        = 180    # used until the live app has reported its own setting
# TEMP for local/internal testing only (self-signed cert on sys.almutlak.local)
# REMOVE these two lines once $LiveSyncUrl points at the real public live
# domain with a proper cert (Let's Encrypt/AutoSSL) - do not ship this
# bypass to production, it disables TLS certificate validation entirely.
[System.Net.ServicePointManager]::ServerCertificateValidationCallback = { $true }
[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12
# .NET's HttpWebRequest sends "Expect: 100-continue" by default for POST
# bodies - some servers/network paths (real network hop, not loopback) don't
# handle that handshake cleanly and the POST body silently never gets sent
# (server receives 0 bytes). Disabling it is the standard fix.
[System.Net.ServicePointManager]::Expect100Continue = $false
# --------------------------------

$LogFile = "$PSScriptRoot\zk_biotime_export.log"
$StateFile = "$PSScriptRoot\last_sent_id.txt"
$LockFile = "$PSScriptRoot\zk_biotime_export.lock"

function Write-Log($message) {
    $line = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') - $message"
    # Write-Host, not Write-Output: output would leak into function return values
    Write-Host $line
    Add-Content -Path $LogFile -Value $line
    if ($message -like 'ERROR*') { $script:LastError = $message }
}

# Totals of this run, written to last_run.json at the end (read by zk_sync_poll.ps1 to
# report a "Sync ZKT" button run back to the live app)
$script:LastError = ''
$script:RunTotals = @{ sent = 0; inserted = 0; duplicates = 0; last_id = 0 }

# Legacy MSSQL nvarchar text (device aliases/work codes typed on device
# keypads over the years) can contain lone/unpaired UTF-16 surrogate
# characters - ConvertTo-Json emits a malformed \uD8XX-style escape for
# these that .NET's own JSON parser tolerates but PHP's json_decode
# correctly rejects as invalid, breaking the ENTIRE batch over one bad
# character in one field. Strip any lone surrogate (keep valid pairs intact)
# from every text field before it ever reaches ConvertTo-Json.
function Remove-LoneSurrogates([string]$s) {
    if ([string]::IsNullOrEmpty($s)) { return $s }
    $sb = New-Object System.Text.StringBuilder
    for ($i = 0; $i -lt $s.Length; $i++) {
        $c = $s[$i]
        if ([char]::IsHighSurrogate($c)) {
            if (($i + 1) -lt $s.Length -and [char]::IsLowSurrogate($s[$i + 1])) {
                [void]$sb.Append($c)
                [void]$sb.Append($s[$i + 1])
                $i++
            }
            # else: lone high surrogate - dropped
        } elseif ([char]::IsLowSurrogate($c)) {
            # lone low surrogate - dropped
        } else {
            [void]$sb.Append($c)
        }
    }
    return $sb.ToString()
}

# --- State: "lastId|syncDays" ---
function Read-State {
    $state = @{ lastId = [long]0; syncDays = $DefaultSyncDays }
    if (Test-Path $StateFile) {
        $parts = (Get-Content $StateFile -Raw).Trim() -split '\|'
        if ($parts.Count -ge 1 -and $parts[0] -match '^\d+$') { $state.lastId = [long]$parts[0] }
        if ($parts.Count -ge 2 -and $parts[1] -match '^\d+$') { $state.syncDays = [int]$parts[1] }
    }
    return $state
}

function Save-State($lastId, $syncDays) {
    Set-Content -Path $StateFile -Value "$lastId|$syncDays" -Encoding ASCII
}

# Posts one JSON body via curl.exe, returns the parsed response or $null (already logged).
# PowerShell's own HTTP clients (Invoke-RestMethod's inconsistent body
# encoding across 5.1 builds, HttpWebRequest's Expect:100-continue handshake
# over a real network hop) have both caused silent corruption/empty-body
# failures here. curl.exe is built into Windows 10/11 and Server 2019+; the
# JSON goes through a temp file as raw UTF-8 (no BOM) so nothing re-encodes it.
function Send-Batch($punches, $terminals) {
    $bodyJson = @{ punches = @($punches); terminals = @($terminals) } | ConvertTo-Json -Depth 5

    # Self-check BEFORE sending: a bad character in legacy MSSQL text is caught
    # here with the JSON saved for inspection, instead of a generic server error.
    try {
        $null = $bodyJson | ConvertFrom-Json
    } catch {
        $badBodyPath = "$PSScriptRoot\last_bad_body.json"
        Set-Content -Path $badBodyPath -Value $bodyJson -Encoding UTF8
        Write-Log "ERROR: ConvertTo-Json produced invalid JSON locally - $($_.Exception.Message). Saved to $badBodyPath."
        return $null
    }

    $tempFile = [System.IO.Path]::GetTempFileName()
    [System.IO.File]::WriteAllText($tempFile, $bodyJson, (New-Object System.Text.UTF8Encoding($false)))

    # -H "Expect:" disables curl's 100-continue for large bodies.
    # -k allows the self-signed cert during internal/LAN testing (matches the
    # ServicePointManager bypass above - remove for the real public domain).
    $curlArgs = @(
        "-s", "-S",
        "-X", "POST",
        $LiveSyncUrl,
        "-H", "Content-Type: application/json; charset=utf-8",
        "-H", "X-Sync-Secret: $LiveSyncSecret",
        "-H", "Expect:",
        "--data-binary", "@$tempFile",
        "-k",
        "--max-time", "300"
    )
    $responseText = & curl.exe @curlArgs
    $curlExitCode = $LASTEXITCODE
    Remove-Item $tempFile -Force -ErrorAction SilentlyContinue

    if ($curlExitCode -ne 0) {
        Write-Log "ERROR: curl failed with exit code $curlExitCode (network/connection-level failure)."
        return $null
    }
    try {
        $response = $responseText | ConvertFrom-Json
    } catch {
        Write-Log "ERROR: could not parse the live app's response as JSON. Raw response: $responseText"
        return $null
    }
    if ($response.status -ne "success") {
        Write-Log "ERROR: live app rejected batch - $($response | ConvertTo-Json -Compress)"
        return $null
    }
    return $response
}

# --- Lock: never two runs at once (a big first sync can outlast 15 minutes) ---
if (Test-Path $LockFile) {
    $lockAge = (Get-Date) - (Get-Item $LockFile).LastWriteTime
    if ($lockAge.TotalMinutes -lt 120) {
        Write-Log "Previous run still active (lock $([int]$lockAge.TotalMinutes) min old) - skipping this run."
        exit 0
    }
    Write-Log "WARNING: removing stale lock ($([int]$lockAge.TotalMinutes) min old)."
}
Set-Content -Path $LockFile -Value $PID

function Invoke-Sync {
    if (-not (Get-Command "curl.exe" -ErrorAction SilentlyContinue)) {
        Write-Log "ERROR: curl.exe not found on this system (expected built into Windows 10/11 and Server 2019+)."
        return 1
    }

    $state = Read-State
    if ($Resync) {
        $state.lastId = [long]0
        Write-Log "Resync requested - sending the whole $($state.syncDays)-day window again."
    }
    $windowFrom = (Get-Date).Date.AddDays( - ($state.syncDays - 1))

    try {
        $connection = New-Object System.Data.SqlClient.SqlConnection($MssqlConnectionString)
        $connection.Open()
    } catch {
        Write-Log "ERROR: could not connect to MSSQL - $($_.Exception.Message)"
        return 1
    }

    # --- Device/terminal live status (confirmed schema against the real
    #     iclock_terminal table - state=1 means online, last_activity is the
    #     real heartbeat timestamp BioTime's own device page reads from) ---
    $terminals = @()
    try {
        $cmd2 = $connection.CreateCommand()
        $cmd2.CommandText = "
            SELECT sn, alias, ip_address, real_ip, state, last_activity, user_count, transaction_count, fp_count, face_count, palm_count
            FROM iclock_terminal"
        $reader2 = $cmd2.ExecuteReader()
        while ($reader2.Read()) {
            $terminals += [PSCustomObject]@{
                sn                = Remove-LoneSurrogates ([string]$reader2["sn"])
                alias             = Remove-LoneSurrogates ([string]$reader2["alias"])
                ip_address        = Remove-LoneSurrogates ([string]$reader2["ip_address"])
                real_ip           = Remove-LoneSurrogates ([string]$reader2["real_ip"])
                state             = [int]$reader2["state"]
                last_activity     = if ($reader2["last_activity"] -is [DBNull]) { $null } else { ([datetime]$reader2["last_activity"]).ToString("yyyy-MM-dd HH:mm:ss") }
                user_count        = [int]$reader2["user_count"]
                transaction_count = [int]$reader2["transaction_count"]
                fp_count          = [int]$reader2["fp_count"]
                face_count        = [int]$reader2["face_count"]
                palm_count        = [int]$reader2["palm_count"]
            }
        }
        $reader2.Close()
    } catch {
        Write-Log "WARNING: iclock_terminal query failed (non-fatal, punch sync still proceeds) - $($_.Exception.Message)"
    }

    # --- New punches, oldest first, batch by batch until caught up ---
    $failed = $false
    $totalSent = 0; $totalInserted = 0; $totalDuplicate = 0; $totalOld = 0; $batchNo = 0
    $exportDir = "$PSScriptRoot\exports"

    while ($true) {
        $batchNo++
        $punches = @()
        $maxId = $state.lastId
        $lastTime = ''
        try {
            $cmd = $connection.CreateCommand()
            $cmd.CommandText = "
                SELECT TOP ($BatchSize) id, emp_code, punch_time, punch_state, verify_type, work_code, terminal_sn
                FROM iclock_transaction
                WHERE id > @lastId AND punch_time >= @from
                ORDER BY id ASC"
            [void]$cmd.Parameters.Add('@lastId', [System.Data.SqlDbType]::BigInt)
            $cmd.Parameters['@lastId'].Value = $state.lastId
            [void]$cmd.Parameters.Add('@from', [System.Data.SqlDbType]::DateTime)
            $cmd.Parameters['@from'].Value = $windowFrom
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
            $failed = $true
            break
        }

        # Terminal status rides along with the first request of the run (also
        # when there are no new punches - keeps device Online/Offline current)
        $sendTerminals = if ($batchNo -eq 1) { $terminals } else { @() }
        if ($punches.Count -eq 0 -and $sendTerminals.Count -eq 0) { break }

        $response = Send-Batch $punches $sendTerminals
        if ($null -eq $response) {
            Write-Log "Stopped after $($batchNo - 1) batch(es); the next run continues after BioTime id $($state.lastId)."
            $failed = $true
            break
        }

        if ($response.retention_days -and [int]$response.retention_days -ge 7) {
            $state.syncDays = [int]$response.retention_days
        }
        $state.lastId = $maxId
        Save-State $state.lastId $state.syncDays

        $totalSent += $punches.Count
        $totalInserted += [int]$response.inserted
        $totalDuplicate += [int]$response.skipped_duplicate
        $totalOld += [int]$response.skipped_old
        if ($batchNo -eq 1) {
            Write-Log "Device status: $($response.devices_synced)/$($response.terminals_received) device(s) state-synced."
        }
        if ($punches.Count -gt 0) {
            Write-Log "Batch ${batchNo}: sent $($punches.Count) punch(es) up to $lastTime - $($response.inserted) new, $($response.skipped_duplicate) duplicate(s)."
        }

        # Only punches the live app reports as genuinely new are exported -
        # one timestamped file per batch that had new data.
        if ([int]$response.inserted -gt 0 -and $response.inserted_punches) {
            if (-not (Test-Path $exportDir)) {
                New-Item -ItemType Directory -Path $exportDir -Force | Out-Null
            }
            $exportPath = "$exportDir\new_punches_$(Get-Date -Format 'yyyyMMdd_HHmmss')_$batchNo.json"
            $response.inserted_punches | ConvertTo-Json -Depth 5 | Set-Content -Path $exportPath -Encoding UTF8
        }

        if ($punches.Count -lt $BatchSize) { break }   # caught up
        if ($batchNo -ge $MaxBatchesPerRun) {
            Write-Log "Reached $MaxBatchesPerRun batches this run - the next run continues after BioTime id $($state.lastId)."
            break
        }
    }

    $connection.Close()
    Write-Log "Run done: $totalSent punch(es) sent, $totalInserted new, $totalDuplicate duplicate(s), $totalOld outside the $($state.syncDays)-day window. Last BioTime id $($state.lastId)."
    $script:RunTotals = @{ sent = $totalSent; inserted = $totalInserted; duplicates = $totalDuplicate; last_id = $state.lastId }
    if ($failed) { return 1 }
    return 0
}

$exitCode = 1
try {
    $exitCode = Invoke-Sync
} finally {
    Remove-Item $LockFile -Force -ErrorAction SilentlyContinue
}

try {
    @{
        ok         = ($exitCode -eq 0)
        sent       = $script:RunTotals.sent
        inserted   = $script:RunTotals.inserted
        duplicates = $script:RunTotals.duplicates
        last_id    = $script:RunTotals.last_id
        message    = $script:LastError
        finished   = (Get-Date -Format 'yyyy-MM-dd HH:mm:ss')
    } | ConvertTo-Json | Set-Content -Path "$PSScriptRoot\last_run.json" -Encoding UTF8
} catch { }
exit $exitCode
