<#
.SYNOPSIS
    Creates the "ExportZKT Data" scheduled task that runs the ZK BioTime export every 15 minutes.

.DESCRIPTION
    One-time setup script for the PC that hosts the ZKTeco / BioTime data.
    It registers a Windows Task Scheduler task that silently runs
    C:\ZKSync\run_zk_biotime_export_silent.vbs (via wscript.exe) every 15 minutes,
    forever, starting 1 minute after this script finishes.

    Task behavior:
      - Runs whether the user is logged in or not (Windows password is stored by Task Scheduler)
      - Runs with highest privileges
      - Runs on battery, and catches up as soon as the PC turns on if a run was missed
      - Skips a run if the previous one is still running
      - Kills a run that takes longer than 30 minutes
      - Re-running this script overwrites the existing task (safe to run again)

.EXAMPLE
    Right-click this file -> "Run with PowerShell"

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File "C:\ZKSync\create_zk_task.ps1"

    Run from PowerShell or CMD. The script asks for Administrator rights (click Yes),
    then for your Windows password. No need to run Set-ExecutionPolicy.

.EXAMPLE
    Unblock-File "C:\ZKSync\create_zk_task.ps1"

    Run once first if the file was copied from email / download / USB.

.NOTES
    Requirements:
    - C:\ZKSync\run_zk_biotime_export_silent.vbs must exist
    - Administrator rights on this PC
    - The Windows password of the account the task runs as
      (Microsoft account: use the account password, not the PIN)

    Maintenance:
    - If the Windows password changes, the task stops running silently.
      Run this script again with the new password.
    - Verify: open Task Scheduler -> Task Scheduler Library -> "ExportZKT Data".
    - Remove: Unregister-ScheduledTask -TaskName "ExportZKT Data" -Confirm:$false
#>

# Define Task Variables
$TaskName         = "ExportZKT Data"
$TaskDescription  = "Runs the ZKT export script every 15 minutes"
$ScriptPath       = "C:\Windows\System32\wscript.exe"
$VbsPath          = "C:\ZKSync\run_zk_biotime_export_silent.vbs"
$ScriptArguments  = "`"$VbsPath`""
$WorkingDirectory = "C:\ZKSync"
$RunAsUser        = "$env:USERDOMAIN\$env:USERNAME"

$ErrorActionPreference = 'Stop'

# 0. Self-elevate if not running as Administrator (-RunLevel Highest needs it)
$IsAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole(
    [Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $IsAdmin) {
    Write-Host "Not running as Administrator - relaunching elevated..." -ForegroundColor Yellow
    Start-Process powershell.exe -Verb RunAs -ArgumentList "-NoProfile -ExecutionPolicy Bypass -File `"$PSCommandPath`""
    exit
}

try {
    # Make sure the script we schedule actually exists
    if (-not (Test-Path $VbsPath)) {
        throw "Script not found: $VbsPath"
    }

    # 1. Create the Action (What the task does)
    $Action = New-ScheduledTaskAction -Execute $ScriptPath -Argument $ScriptArguments -WorkingDirectory $WorkingDirectory

    # 2. Create the Trigger (When the task runs)
    # "Once" trigger + 15 min repetition. RepetitionDuration is omitted on purpose:
    # [TimeSpan]::MaxValue is rejected by Windows 10/11 ("value ... out of range");
    # leaving it out makes the repetition run indefinitely.
    $StartTime = (Get-Date).AddMinutes(1)
    $Trigger = New-ScheduledTaskTrigger -Once -At $StartTime -RepetitionInterval (New-TimeSpan -Minutes 15)

    # 3. Create the Settings (Task behavior)
    # -AllowStartIfOnBatteries / -DontStopIfGoingOnBatteries: run on laptops even if unplugged
    # -StartWhenAvailable: if the PC was off, run as soon as it turns on
    # -MultipleInstances IgnoreNew: skip a run if the previous one is still going
    # -ExecutionTimeLimit: kill a hung run so it doesn't block every later run (default is 72 hours)
    $Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries `
        -StartWhenAvailable -MultipleInstances IgnoreNew `
        -ExecutionTimeLimit (New-TimeSpan -Minutes 30)

    # 4. Register the Task
    # Runs whether the user is logged in or not; the password is stored by Task Scheduler.
    Write-Host "Creating Scheduled Task: $TaskName ..." -ForegroundColor Cyan

    $Credential = Get-Credential -UserName $RunAsUser -Message "Enter your Windows password to create the background task"
    if (-not $Credential) {
        throw "No credentials entered - task not created."
    }

    Register-ScheduledTask -TaskName $TaskName `
        -Description $TaskDescription `
        -Action $Action `
        -Trigger $Trigger `
        -Settings $Settings `
        -User $Credential.UserName `
        -Password $Credential.GetNetworkCredential().Password `
        -RunLevel Highest `
        -Force | Out-Null

    $Info = Get-ScheduledTaskInfo -TaskName $TaskName
    Write-Host "SUCCESS! The task '$TaskName' has been created and will run every 15 minutes." -ForegroundColor Green
    Write-Host "Next run: $($Info.NextRunTime)" -ForegroundColor Green
} catch {
    Write-Host "ERROR: Failed to create the task." -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
}

# Keep the window open so you can read the result
Read-Host -Prompt "Press Enter to exit"
