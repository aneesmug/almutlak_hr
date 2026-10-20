' Point Task Scheduler's Action directly at THIS file (Program/script:
' wscript.exe, Arguments: "C:\ZKSync\run_zk_biotime_export_silent.vbs") instead
' of the .bat. Task Scheduler launches cmd.exe itself to interpret a .bat file -
' that outer cmd.exe window is created by Windows before the .bat's own code
' ever runs, so nothing inside a .bat can hide it. Going through wscript.exe
' instead means Windows never creates a console window in the first place.
'
' Waits for the sync to finish (so Task Scheduler's "Do not start a new instance"
' works) and hands PowerShell's exit code back - Task Scheduler's "Last Run Result"
' then shows 0x1 when a sync failed (details in zk_biotime_export.log).
' Extra arguments are passed through, e.g. a one-off full re-sync:
'   wscript.exe "C:\ZKSync\run_zk_biotime_export_silent.vbs" -Resync
Set objShell = CreateObject("WScript.Shell")
scriptFolder = CreateObject("Scripting.FileSystemObject").GetParentFolderName(WScript.ScriptFullName)

extraArgs = ""
For Each arg In WScript.Arguments
    extraArgs = extraArgs & " " & arg
Next

cmdLine = "powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File """ & scriptFolder & "\zk_biotime_export.ps1""" & extraArgs
exitCode = objShell.Run(cmdLine, 0, True)
WScript.Quit exitCode
