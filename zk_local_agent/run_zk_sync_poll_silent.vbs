' "Sync ZKT now" listener - Task Scheduler runs THIS every minute, hidden:
'   Program/script:  C:\Windows\System32\wscript.exe
'   Add arguments:   "C:\ZKSync\run_zk_sync_poll_silent.vbs"
'   Start in:        C:\ZKSync
'   Trigger:         Daily, repeat every 1 minute, indefinitely
'   Settings:        If the task is already running: Do not start a new instance
' Runs zk_sync_poll.ps1, which asks the live app whether the "Sync ZKT" button was
' clicked and, if so, runs zk_biotime_export.ps1 and reports the result back.
Set objShell = CreateObject("WScript.Shell")
scriptFolder = CreateObject("Scripting.FileSystemObject").GetParentFolderName(WScript.ScriptFullName)
cmdLine = "powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File """ & scriptFolder & "\zk_sync_poll.ps1"""
exitCode = objShell.Run(cmdLine, 0, True)
WScript.Quit exitCode
