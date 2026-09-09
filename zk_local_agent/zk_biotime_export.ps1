<#
.SYNOPSIS
    Reads NEW attendance punches from BioTime's own MSSQL database and pushes
    them to the live HR app over HTTPS. Runs ONLY on the local BioTime server.

.DESCRIPTION
    Does not touch BioTime itself, the ZK devices, or the router - this is a
    separate, standalone script. It:
      1. Connects to BioTime's MSSQL database (native local access - no PHP/
         MySQL driver involved, that's the whole point of running this here).
      2. Selects the newest $BatchSize rows from iclock_transaction, limited
         to the last 1 year (WHERE punch_time >= 1 year ago), ORDER BY id
         DESC - newest punches first, not an ascending backlog crawl, and
         never reaching further back than a year regardless of how large
         BioTime's full history is. Duplicate sends are expected and
         harmless - the live app's zk_attendance_raw table has a UNIQUE KEY
         on (serial_number, pin, punch_time, status) and the import endpoint
         uses INSERT IGNORE, so an already-stored punch is silently skipped
         every time it's resent, never double-counted or duplicated. No
         local watermark/state file is needed for this - the server is the
         source of truth for what's new.
      3. Selects every device's live status from iclock_terminal (state,
         last_activity, user/fp/face/palm/transaction counts) - schema
         confirmed against the real database, sent every run regardless of
         whether that device had a punch, so all devices' Online/Offline
         state stays accurate on the live app, not just ones with recent
         punches.
      4. POSTs both as JSON to zk_sync_import.php on the live app,
         authenticated with a shared secret.

    SETUP (fill these in before first run):
      - $MssqlConnectionString - point at BioTime's actual database. If the
        table/column names below don't match your BioTime version, adjust
        the two SELECT statements accordingly (confirm via SQL Server
        Management Studio first).
      - $LiveSyncUrl - https://yourdomain.com/zk_sync_import.php
      - $LiveSyncSecret - copy the value of the 'zk_sync_secret_key' row from
        the app_settings table on the LIVE app's database (auto-generated the
        first time zk_sync_import.php or any zk_* page runs).

    SCHEDULING (Windows Task Scheduler):
      Program/script:   powershell.exe
      Arguments:         -NoProfile -ExecutionPolicy Bypass -File "C:\path\to\zk_biotime_export.ps1"
      Trigger:           Repeat every 1-2 minutes, indefinitely.
#>

# ---- SETUP: fill these in ----
$MssqlConnectionString  = "Server=192.168.9.185;Database=zkbiotime;User Id=zkbio;Password=@DmiN56539306#;"
$LiveSyncUrl            = "https://sys.almutlak.local/zk_sync_import.php"
$LiveSyncSecret         = "08d7b7df34b6fd1b7ea5dfe4aaf0c7cd00c34693d84e4d74b9d237bd1aa12f9b"
$BatchSize              = 1500
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
function Write-Log($message) {
    $line = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') - $message"
    Write-Output $line
    Add-Content -Path $LogFile -Value $line
}

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

try {
    $connection = New-Object System.Data.SqlClient.SqlConnection($MssqlConnectionString)
    $connection.Open()
} catch {
    Write-Log "ERROR: could not connect to MSSQL - $($_.Exception.Message)"
    exit 1
}

# --- 1. Newest attendance punches (confirmed field names, matches BioTime's
#        standard iclock_transaction schema) - newest first, see .DESCRIPTION
#        above for why (and why re-sending already-stored punches is safe). ---
$punches = @()
try {
    $cmd = $connection.CreateCommand()
    $cmd.CommandText = "
        SELECT TOP ($BatchSize) id, emp_code, punch_time, punch_state, verify_type, work_code, terminal_sn
        FROM iclock_transaction
        WHERE punch_time >= DATEADD(YEAR, -1, CAST(GETDATE() AS DATE))
        ORDER BY id DESC"
    $reader = $cmd.ExecuteReader()
    while ($reader.Read()) {
        $punches += [PSCustomObject]@{
            terminal_sn = Remove-LoneSurrogates ([string]$reader["terminal_sn"])
            pin         = Remove-LoneSurrogates ([string]$reader["emp_code"])
            punch_time  = ([datetime]$reader["punch_time"]).ToString("yyyy-MM-dd HH:mm:ss")
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

# --- 2. Device/terminal live status (confirmed schema against the real
#        iclock_terminal table - state=1 means online, last_activity is the
#        real heartbeat timestamp BioTime's own device page reads from) ---
$terminals = @()
try {
    $cmd2 = $connection.CreateCommand()
    $cmd2.CommandText = "
        SELECT sn, alias, ip_address, real_ip, state, last_activity, user_count, transaction_count, fp_count, face_count, palm_count
        FROM iclock_terminal"
    $reader2 = $cmd2.ExecuteReader()
    while ($reader2.Read()) {
        $terminals += [PSCustomObject]@{
            sn               = Remove-LoneSurrogates ([string]$reader2["sn"])
            alias            = Remove-LoneSurrogates ([string]$reader2["alias"])
            ip_address       = Remove-LoneSurrogates ([string]$reader2["ip_address"])
            real_ip          = Remove-LoneSurrogates ([string]$reader2["real_ip"])
            state            = [int]$reader2["state"]
            last_activity    = if ($reader2["last_activity"] -is [DBNull]) { $null } else { ([datetime]$reader2["last_activity"]).ToString("yyyy-MM-dd HH:mm:ss") }
            user_count       = [int]$reader2["user_count"]
            transaction_count = [int]$reader2["transaction_count"]
            fp_count         = [int]$reader2["fp_count"]
            face_count       = [int]$reader2["face_count"]
            palm_count       = [int]$reader2["palm_count"]
        }
    }
    $reader2.Close()
} catch {
    Write-Log "WARNING: iclock_terminal query failed (non-fatal, punch sync still proceeds) - $($_.Exception.Message)"
}

$connection.Close()

if ($punches.Count -eq 0 -and $terminals.Count -eq 0) {
    Write-Log "No new punches and no terminal status available."
    exit 0
}

$bodyJson = @{ punches = $punches; terminals = $terminals } | ConvertTo-Json -Depth 5

# Self-check BEFORE sending anything over the network: if real MSSQL text data
# contains something ConvertTo-Json mishandles (a known Windows PowerShell 5.1
# issue with certain control characters in legacy text data), this catches it
# here with the bad JSON saved locally for inspection, instead of a useless
# generic "Invalid JSON body" from the live app with no way to see what broke.
try {
    $null = $bodyJson | ConvertFrom-Json
} catch {
    $badBodyPath = "$PSScriptRoot\last_bad_body.json"
    Set-Content -Path $badBodyPath -Value $bodyJson -Encoding UTF8
    Write-Log "ERROR: ConvertTo-Json produced invalid JSON locally - $($_.Exception.Message)"
    Write-Log "ERROR: saved the bad JSON to $badBodyPath for inspection - open it and search near the error position/an unusual character."
    exit 1
}

# PowerShell's own HTTP clients (Invoke-RestMethod's inconsistent body
# encoding across 5.1 builds, HttpWebRequest's Expect:100-continue handshake
# over a real network hop) have both caused silent corruption/empty-body
# failures here that were hard to pin down. Switching to curl.exe instead -
# built into Windows 10/11 and Server 2019+ by default, and the same tool
# used successfully for every diagnostic test throughout building this
# integration. Writing the JSON to a temp file (raw UTF-8 bytes, no BOM, no
# PowerShell string round-tripping) and having curl POST that file directly
# removes every layer of ambiguity at once.
$tempFile = [System.IO.Path]::GetTempFileName()
[System.IO.File]::WriteAllText($tempFile, $bodyJson, (New-Object System.Text.UTF8Encoding($false)))

$curlPath = "curl.exe"
if (-not (Get-Command $curlPath -ErrorAction SilentlyContinue)) {
    Write-Log "ERROR: curl.exe not found on this system (expected built into Windows 10/11 and Server 2019+). Install it or adjust the script to use a different HTTP client."
    Remove-Item $tempFile -Force -ErrorAction SilentlyContinue
    exit 1
}

# -H "Expect:" sends an empty Expect header, overriding curl's own default
# 100-continue behavior for large bodies - same fix as before, now applied
# to a client that isn't fighting us on encoding too.
# -k allows the self-signed cert during internal/LAN testing (matches the
# ServicePointManager bypass above - remove when pointed at the real public
# HTTPS domain with a proper cert).
$curlArgs = @(
    "-s", "-S",
    "-X", "POST",
    $LiveSyncUrl,
    "-H", "Content-Type: application/json; charset=utf-8",
    "-H", "X-Sync-Secret: $LiveSyncSecret",
    "-H", "Expect:",
    "--data-binary", "@$tempFile",
    "-k",
    "--max-time", "30"
)

$responseText = & $curlPath @curlArgs
$curlExitCode = $LASTEXITCODE
Remove-Item $tempFile -Force -ErrorAction SilentlyContinue

if ($curlExitCode -ne 0) {
    Write-Log "ERROR: curl failed with exit code $curlExitCode (network/connection-level failure, see curl's own error output above if any)."
    exit 1
}

try {
    $response = $responseText | ConvertFrom-Json
} catch {
    Write-Log "ERROR: could not parse the live app's response as JSON. Raw response: $responseText"
    exit 1
}

if ($response.status -eq "success") {
    Write-Log "Synced $($response.inserted) new punch(es), $($response.skipped_duplicate) duplicate(s) skipped, $($response.devices_synced)/$($response.terminals_received) device(s) state-synced."

    # Only when this run actually added new punches - the live app is the one
    # that knows which of the sent punches were genuinely new vs already-
    # stored duplicates (see inserted_punches in zk_sync_import.php's
    # response), so this exports exactly that set, not the whole batch. One
    # timestamped file per run that had new data, so nothing overwrites a
    # previous export.
    if ($response.inserted -gt 0 -and $response.inserted_punches) {
        $exportDir = "$PSScriptRoot\exports"
        if (-not (Test-Path $exportDir)) {
            New-Item -ItemType Directory -Path $exportDir -Force | Out-Null
        }
        $exportPath = "$exportDir\new_punches_$(Get-Date -Format 'yyyyMMdd_HHmmss').json"
        $response.inserted_punches | ConvertTo-Json -Depth 5 | Set-Content -Path $exportPath -Encoding UTF8
        Write-Log "Exported $($response.inserted) new punch(es) recorded in ZKT server's MSSQL to $exportPath."
    }
} else {
    Write-Log "ERROR: live app rejected batch - $($response | ConvertTo-Json -Compress)"
    exit 1
}
