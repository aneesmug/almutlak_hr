' ONE-OFF full re-sync.
' Runs:  powershell -NoProfile -ExecutionPolicy Bypass -File .\zk_biotime_export.ps1 -Resync
' which re-sends the whole sync window (App Settings > Attendance Config > Sync Settings,
' e.g. 180 days) to the live app - use it after the live attendance tables were emptied.
' Duplicates are skipped safely by the live app.
'
' Two ways to run it:
'   1. Double-click: asks for confirmation, shows the PowerShell window, then a result box.
'   2. Silent (Task Scheduler, ONE-TIME trigger only - never a repeating one, that would
'      re-send the whole window over and over):
'        Program/script:  C:\Windows\System32\wscript.exe
'        Add arguments:   "C:\ZKSync\run_zk_biotime_resync.vbs" /silent
'        Start in:        C:\ZKSync
'      No prompts, no windows; the result is in zk_biotime_export.log and in Task
'      Scheduler's "Last Run Result" (0x0 = OK, 0x1 = failed).
'
' The regular 15-minute task keeps using run_zk_biotime_export_silent.vbs (incremental).
Set objShell = CreateObject("WScript.Shell")
scriptFolder = CreateObject("Scripting.FileSystemObject").GetParentFolderName(WScript.ScriptFullName)

silent = False
For Each arg In WScript.Arguments
    If LCase(arg) = "/silent" Or LCase(arg) = "-silent" Then silent = True
Next

If Not silent Then
    answer = MsgBox("Re-send ALL attendance punches of the sync window (e.g. 180 days) to the live app?" & vbCrLf & vbCrLf & _
        "Use this after the live attendance tables were emptied. It can take several minutes.", _
        vbYesNo + vbQuestion, "ZK full re-sync")
    If answer <> vbYes Then WScript.Quit 0
End If

cmdLine = "powershell.exe -NoProfile -ExecutionPolicy Bypass"
If silent Then cmdLine = cmdLine & " -WindowStyle Hidden"
cmdLine = cmdLine & " -File """ & scriptFolder & "\zk_biotime_export.ps1"" -Resync"

If silent Then
    exitCode = objShell.Run(cmdLine, 0, True)   ' 0 = hidden
Else
    exitCode = objShell.Run(cmdLine, 1, True)   ' 1 = visible window
    If exitCode = 0 Then
        MsgBox "Full re-sync finished successfully." & vbCrLf & vbCrLf & "Details: " & scriptFolder & "\zk_biotime_export.log", vbInformation, "ZK full re-sync"
    Else
        MsgBox "Re-sync stopped with an error (exit code " & exitCode & ")." & vbCrLf & vbCrLf & _
            "Check " & scriptFolder & "\zk_biotime_export.log." & vbCrLf & _
            "Progress is saved - the scheduled task continues from where it stopped once the problem is fixed.", vbExclamation, "ZK full re-sync"
    End If
End If
WScript.Quit exitCode
