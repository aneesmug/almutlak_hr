@echo off
setlocal EnableExtensions EnableDelayedExpansion

title HTTPS Setup Diagnostic

echo ================================================================
echo                   HTTPS Setup Diagnostic Test
echo ================================================================
echo.

set "DEFAULT_XAMPP=D:\xampp"
set /p "XAMPP_PATH=Enter XAMPP path [D:\xampp]: "
if "%XAMPP_PATH%"=="" set "XAMPP_PATH=%DEFAULT_XAMPP%"

set "HTTPD_CONF=%XAMPP_PATH%\apache\conf\httpd.conf"
set "VHOSTS_CONF=%XAMPP_PATH%\apache\conf\extra\httpd-vhosts.conf"
set "SSL_CONF=%XAMPP_PATH%\apache\conf\extra\httpd-ssl.conf"
set "HTTPD_EXE=%XAMPP_PATH%\apache\bin\httpd.exe"
set "ERROR_LOG=%XAMPP_PATH%\apache\logs\error.log"

echo [Test 1] Checking Apache executable...
if exist "%HTTPD_EXE%" (
    echo OK: Apache found at %HTTPD_EXE%
) else (
    echo ERROR: Apache not found at %HTTPD_EXE%
    goto :end
)

echo.
echo [Test 2] Running Apache config syntax check...
"%HTTPD_EXE%" -t -f "%HTTPD_CONF%"
if "%errorlevel%"=="0" (
    echo OK: Apache config syntax is valid
) else (
    echo ERROR: Apache config has syntax errors (see above)
    goto :end
)

echo.
echo [Test 3] Checking if SSL module is loaded...
findstr /R /I "LoadModule ssl_module" "%HTTPD_CONF%" >nul
if "%errorlevel%"=="0" (
    echo OK: SSL module LoadModule found in httpd.conf
) else (
    echo WARNING: SSL module LoadModule not found in httpd.conf
)

echo.
echo [Test 4] Checking Listen 443 in httpd-ssl.conf...
if exist "%SSL_CONF%" (
    findstr /R /I "Listen 443" "%SSL_CONF%" >nul
    if "%errorlevel%"=="0" (
        echo OK: Listen 443 found in httpd-ssl.conf
    ) else (
        echo ERROR: Listen 443 NOT found in httpd-ssl.conf
        echo Adding Listen 443 now...
        powershell -NoProfile -ExecutionPolicy Bypass -Command "Add-Content -Path '%SSL_CONF%' -Value 'Listen 443' -Encoding ASCII"
    )
) else (
    echo ERROR: httpd-ssl.conf not found at %SSL_CONF%
)

echo.
echo [Test 5] Checking VirtualHost blocks in httpd-vhosts.conf...
findstr /R /I "VirtualHost.*:443" "%VHOSTS_CONF%" >nul
if "%errorlevel%"=="0" (
    echo OK: Found HTTPS VirtualHost (*:443) in httpd-vhosts.conf
) else (
    echo WARNING: No HTTPS VirtualHost (*:443) found in httpd-vhosts.conf
)

echo.
echo [Test 6] Checking certificate files...
for /f "delims=" %%A in ('powershell -NoProfile -ExecutionPolicy Bypass -Command "Get-Item '%XAMPP_PATH%\apache\conf\ssl.crt\*.crt' | Select-Object -ExpandProperty FullName | Select-Object -First 1"') do set "CERT_FILE=%%A"
if not "%CERT_FILE%"=="" (
    echo OK: Found certificate: %CERT_FILE%
) else (
    echo ERROR: No certificate files found in %XAMPP_PATH%\apache\conf\ssl.crt\
)

echo.
echo [Test 7] Showing recent Apache errors (last 20 lines)...
if exist "%ERROR_LOG%" (
    echo.
    echo --- Apache error.log tail (last 20 lines) ---
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Get-Content '%ERROR_LOG%' | Select-Object -Last 20"
    echo --- End error.log ---
    echo.
) else (
    echo WARNING: error.log not found at %ERROR_LOG%
)

echo.
echo [Test 8] Checking if Apache is running...
tasklist | findstr /I httpd >nul
if "%errorlevel%"=="0" (
    echo OK: Apache (httpd.exe) is running
) else (
    echo WARNING: Apache (httpd.exe) is NOT running
    echo You may need to restart Apache from XAMPP Control Panel
)

echo.
echo ================================================================
echo Diagnostic Complete
echo ================================================================
echo.
echo To fix issues:
echo 1. Fix any syntax errors shown above
echo 2. Ensure Listen 443 is in httpd-ssl.conf
echo 3. Ensure SSL module is loaded
echo 4. Restart Apache from XAMPP Control Panel
echo 5. Test: https://inv.snapspro.local
echo.
pause <con

:end
exit /b 0
