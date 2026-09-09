' Point Task Scheduler's Action directly at THIS file (Program/script:
' wscript.exe, Arguments: "C:\ZKSync\run_zk_biotime_export_silent.vbs") instead
' of the .bat. Task Scheduler launches cmd.exe itself to interpret a .bat file -
' that outer cmd.exe window is created by Windows before the .bat's own code
' ever runs, so nothing inside a .bat can hide it. Going through wscript.exe
' instead means Windows never creates a console window in the first place.
Set objShell = CreateObject("WScript.Shell")
scriptFolder = CreateObject("Scripting.FileSystemObject").GetParentFolderName(WScript.ScriptFullName)
cmdLine = "powershell.exe -NoProfile -ExecutionPolicy Bypass -File """ & scriptFolder & "\zk_biotime_export.ps1"""
objShell.Run cmdLine, 0, True
