@echo off
REM Launches print-agent.ps1 - use this as the Task Scheduler Action
REM (Program: this .bat file). %~dp0 resolves to this .bat's own folder,
REM so the .ps1 is found regardless of Task Scheduler's working directory.
powershell.exe -ExecutionPolicy Bypass -File "%~dp0zk_biotime_export.ps1"
