@echo off
REM Wrapper for Task Scheduler - point a Scheduled Task's "Program/script" at
REM this .bat file directly (Task Scheduler running .ps1 files directly is
REM unreliable across Windows versions; this avoids that entirely).
REM %~dp0 = this .bat's own folder, so it finds zk_biotime_export.ps1 next to
REM it no matter where the whole zk_local_agent folder is copied/renamed.
REM
REM Runs fully silent - no visible console window, whether launched from
REM Task Scheduler or double-clicked. -WindowStyle Hidden on PowerShell alone
REM does NOT hide the cmd.exe host window that runs this .bat, so this
REM relaunches itself once through a tiny throwaway VBScript (WScript.Shell
REM Run with window style 0 = hidden) that actually suppresses it. All output
REM still goes to zk_biotime_export.log as before - nothing is lost, it's
REM just not shown on screen.
if "%~1"=="RUNNING_HIDDEN" goto :run

set "VBS=%TEMP%\zk_biotime_hidden_%RANDOM%.vbs"
> "%VBS%" echo Set objShell = CreateObject("WScript.Shell")
>> "%VBS%" echo objShell.Run """" ^& WScript.Arguments(0) ^& """ RUNNING_HIDDEN", 0, False
wscript.exe "%VBS%" "%~f0"
del "%VBS%" >nul 2>&1
exit /b 0

:run
powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "%~dp0zk_biotime_export.ps1"
