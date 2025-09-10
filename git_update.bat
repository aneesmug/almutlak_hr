@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

echo ================================
echo        GIT UPDATE SCRIPT
echo ================================
echo.

:add
echo Running 'git add .'...
git add .
if %errorlevel% neq 0 (
    echo Error: git add failed!
    pause
    exit /b 1
)
echo.

:commit
set /p "commit_reason=Enter commit reason: "
if "!commit_reason!"=="" (
    echo Error: Commit reason cannot be empty!
    echo.
    goto commit
)

echo Running 'git commit -m "!commit_reason!"'...
git commit -m "!commit_reason!"
if %errorlevel% neq 0 (
    echo Error: git commit failed!
    pause
    exit /b 1
)
echo.

:push
echo Running 'git push origin main'...
git push origin main
if %errorlevel% neq 0 (
    echo Error: git push failed!
    pause
    exit /b 1
)
echo.

echo ================================
echo    GIT UPDATE COMPLETED!
echo ================================
echo.
pause