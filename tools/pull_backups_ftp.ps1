# Runs on THIS (local, static-IP) machine only - never on the live server.
# Live side now writes hourly backups grouped into day folders:
#   db_backups/YYYY-MM-DD/almutlak_db_HH-ii-ss.sql.gz
# This mirrors that same day-folder layout locally, pulling only files not
# already present, then deletes whole day folders older than 7 days (both
# sides use "delete the whole day folder", not per-file age). Optionally also
# mirrors every backup into extra destinations (network share by IP, another
# local drive) - see $ExtraDestinations in ftp_pull_config.ps1.
# Pure PowerShell / .NET - no PHP or other runtime needed.
#
# Setup:
#   1. Copy ftp_pull_config.ps1.example to ftp_pull_config.ps1 (same folder)
#      and fill in the real FTP host/user/pass/remote path.
#   2. Point Windows Task Scheduler at pull_backups_ftp.bat (same folder).

$ErrorActionPreference = "Stop"
$ConfigPath = Join-Path $PSScriptRoot "ftp_pull_config.ps1"

if (-not (Test-Path $ConfigPath)) {
    Write-Error "Missing tools\ftp_pull_config.ps1 - copy ftp_pull_config.ps1.example and fill it in."
    exit 1
}

. $ConfigPath

if (-not $FtpHost -or -not $FtpUser) {
    Write-Error "FTP host/user missing in ftp_pull_config.ps1."
    exit 1
}

if (-not $ExtraDestinations) {
    $ExtraDestinations = @() # older config files may not define this yet
}

if (-not (Test-Path $LocalDir)) {
    New-Item -ItemType Directory -Path $LocalDir -Force | Out-Null
}

$LogFile = Join-Path $LocalDir "pull.log"
function Write-PullLog([string]$Message) {
    $line = "[{0}] {1}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"), $Message
    Add-Content -Path $LogFile -Value $line
}

if ($IgnoreCertErrors) {
    [System.Net.ServicePointManager]::ServerCertificateValidationCallback = { $true }
}

function New-FtpRequest([string]$Path, [string]$Method) {
    $uri = "ftp://{0}:{1}{2}" -f $FtpHost, $FtpPort, $Path
    $request = [System.Net.FtpWebRequest]::Create($uri)
    $request.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
    $request.Method = $Method
    $request.EnableSsl = $UseSsl
    $request.UsePassive = $true
    $request.UseBinary = $true
    $request.KeepAlive = $false
    return $request
}

function Get-FtpListing([string]$Path) {
    $listRequest = New-FtpRequest -Path $Path -Method ([System.Net.WebRequestMethods+Ftp]::ListDirectory)
    $listResponse = $listRequest.GetResponse()
    $reader = New-Object System.IO.StreamReader($listResponse.GetResponseStream())
    $listing = $reader.ReadToEnd()
    $reader.Close()
    $listResponse.Close()
    return ($listing -split "`r?`n") | Where-Object { $_ -ne "" } | ForEach-Object { Split-Path $_ -Leaf }
}

try {
    $rootEntries = Get-FtpListing -Path "$RemoteDir/"
} catch {
    Write-PullLog "FAIL: could not list remote dir $RemoteDir - $($_.Exception.Message)"
    Write-Error "Could not list remote directory. Check host/credentials/remote path."
    exit 1
}

$dayFolders = $rootEntries | Where-Object { $_ -match '^\d{4}-\d{2}-\d{2}$' }

$downloaded = 0
foreach ($dayFolder in $dayFolders) {
    $localDayDir = Join-Path $LocalDir $dayFolder
    if (-not (Test-Path $localDayDir)) {
        New-Item -ItemType Directory -Path $localDayDir -Force | Out-Null
    }

    try {
        $remoteFiles = Get-FtpListing -Path "$RemoteDir/$dayFolder/" | Where-Object { $_ -match '\.sql\.gz$' }
    } catch {
        Write-PullLog "FAIL: could not list remote day folder $dayFolder - $($_.Exception.Message)"
        continue
    }

    foreach ($name in $remoteFiles) {
        $localPath = Join-Path $localDayDir $name
        if (Test-Path $localPath) {
            continue # already pulled
        }

        $tmpPath = "$localPath.part"
        try {
            $getRequest = New-FtpRequest -Path "$RemoteDir/$dayFolder/$name" -Method ([System.Net.WebRequestMethods+Ftp]::DownloadFile)
            $getResponse = $getRequest.GetResponse()
            $inStream = $getResponse.GetResponseStream()
            $outStream = [System.IO.File]::Create($tmpPath)
            $inStream.CopyTo($outStream)
            $outStream.Close()
            $inStream.Close()
            $getResponse.Close()

            Move-Item -Path $tmpPath -Destination $localPath -Force
            $downloaded++
            $sizeKb = [math]::Round((Get-Item $localPath).Length / 1KB, 1)
            Write-PullLog "OK: pulled $dayFolder/$name ($sizeKb KB)"
        } catch {
            if (Test-Path $tmpPath) { Remove-Item $tmpPath -Force }
            Write-PullLog "FAIL: could not download $dayFolder/$name - $($_.Exception.Message)"
        }
    }
}

if ($downloaded -eq 0) {
    Write-PullLog "No new backups to pull."
}

# Delete whole local day folders older than retention - mirrors the live side's
# folder-level rotation instead of per-file age.
$cutoffFolder = (Get-Date).AddDays(-($RetentionDays - 1)).ToString("yyyy-MM-dd")
Get-ChildItem -Path $LocalDir -Directory | Where-Object { $_.Name -match '^\d{4}-\d{2}-\d{2}$' -and $_.Name -lt $cutoffFolder } | ForEach-Object {
    Remove-Item $_.FullName -Recurse -Force
    Write-PullLog "ROTATE: deleted old local day folder $($_.Name)"
}

# Mirror into any extra destinations (network share by IP, another local drive,
# etc.) - same day-folder layout, same 7-day rotation applied to each.
foreach ($dest in ($ExtraDestinations | Where-Object { $_ })) {
    try {
        if (-not (Test-Path $dest)) {
            New-Item -ItemType Directory -Path $dest -Force | Out-Null
        }

        robocopy $LocalDir $dest /E /NFL /NDL /NJH /NJS /NP | Out-Null
        Write-PullLog "MIRROR: synced to $dest"

        Get-ChildItem -Path $dest -Directory | Where-Object { $_.Name -match '^\d{4}-\d{2}-\d{2}$' -and $_.Name -lt $cutoffFolder } | ForEach-Object {
            Remove-Item $_.FullName -Recurse -Force
            Write-PullLog "ROTATE: deleted old day folder in $dest -> $($_.Name)"
        }
    } catch {
        Write-PullLog "FAIL: could not mirror to $dest - $($_.Exception.Message)"
    }
}

Write-Output "Done. Downloaded: $downloaded"
