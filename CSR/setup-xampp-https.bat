@echo off
setlocal EnableExtensions EnableDelayedExpansion

:: Interactive HTTPS setup for XAMPP Apache on Windows
:: Auto-elevates with UAC if not started as Administrator.

title XAMPP HTTPS Setup Wizard

net session >nul 2>&1
if "%errorlevel%"=="0" goto :ADMIN_OK
echo.
echo Requesting Administrator privileges - please accept the UAC popup...
echo.
powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath cmd.exe -ArgumentList '/c \"%~f0\"' -Verb RunAs"
exit /b 0

:ADMIN_OK

echo ================================================================
echo                 XAMPP HTTPS Setup Wizard
echo ================================================================
echo.
echo This script will:
echo 1) Create timestamped backups of Apache config files
echo 2) Create SSL certificate/key
echo 3) Add domain to Windows hosts file
echo 4) Ensure required SSL directives are enabled in httpd.conf
echo 5) Replace existing VirtualHost entries for the same domain safely
echo 6) Validate Apache config
echo 7) Check Apache port state and guide manual restart
echo 8) Trust certificate in Windows (optional, recommended)
echo 9) Configure Firefox to use Windows cert store (optional)
echo 10) Run endpoint health checks (DNS + HTTP + HTTPS)
echo.

set "DEFAULT_XAMPP=D:\xampp"
set /p "XAMPP_PATH=Enter XAMPP path [D:\xampp]: "
if "%XAMPP_PATH%"=="" set "XAMPP_PATH=%DEFAULT_XAMPP%"

set "HTTPD_CONF=%XAMPP_PATH%\apache\conf\httpd.conf"
set "VHOSTS_CONF=%XAMPP_PATH%\apache\conf\extra\httpd-vhosts.conf"
set "HOSTS_FILE=%SystemRoot%\System32\drivers\etc\hosts"
if not exist "%HOSTS_FILE%" set "HOSTS_FILE=%SystemRoot%\Sysnative\drivers\etc\hosts"
set "OPENSSL_EXE=%XAMPP_PATH%\apache\bin\openssl.exe"
set "OPENSSL_CNF=%XAMPP_PATH%\apache\conf\openssl.cnf"
set "HTTPD_EXE=%XAMPP_PATH%\apache\bin\httpd.exe"
set "CRT_DIR=%XAMPP_PATH%\apache\conf\ssl.crt"
set "KEY_DIR=%XAMPP_PATH%\apache\conf\ssl.key"

if not exist "%HTTPD_CONF%" (
    echo.
    echo [ERROR] httpd.conf not found: %HTTPD_CONF%
    pause
    exit /b 1
)
if not exist "%VHOSTS_CONF%" (
    echo.
    echo [ERROR] httpd-vhosts.conf not found: %VHOSTS_CONF%
    pause
    exit /b 1
)
if not exist "%OPENSSL_EXE%" (
    echo.
    echo [ERROR] openssl.exe not found: %OPENSSL_EXE%
    pause
    exit /b 1
)
if not exist "%OPENSSL_CNF%" (
    echo.
    echo [ERROR] openssl.cnf not found: %OPENSSL_CNF%
    pause
    exit /b 1
)
if not exist "%HTTPD_EXE%" (
    echo.
    echo [ERROR] httpd.exe not found: %HTTPD_EXE%
    pause
    exit /b 1
)
if not exist "%HOSTS_FILE%" (
    echo.
    echo [ERROR] hosts file not found: %HOSTS_FILE%
    pause
    exit /b 1
)

echo.
set /p "DOMAIN=Enter domain (example: sys.almutlak.local): "
if "%DOMAIN%"=="" (
    echo [ERROR] Domain is required.
    pause
    exit /b 1
)

set "RAW_DOMAIN=%DOMAIN%"
set "DOMAIN=%DOMAIN:http://=%"
set "DOMAIN=%DOMAIN:https://=%"
for /f "tokens=1 delims=/" %%A in ("%DOMAIN%") do set "DOMAIN=%%A"
for /f "tokens=1 delims=:" %%A in ("%DOMAIN%") do set "DOMAIN=%%A"
for /f "tokens=* delims= " %%A in ("%DOMAIN%") do set "DOMAIN=%%A"

echo %DOMAIN%| findstr /R /I "^[a-z0-9][a-z0-9.-]*[a-z0-9]$" >nul
if not "%errorlevel%"=="0" (
    echo [ERROR] Invalid domain format: %RAW_DOMAIN%
    echo Please enter hostname only, for example: sys.almutlak.local
    pause
    exit /b 1
)

if /I not "%RAW_DOMAIN%"=="%DOMAIN%" (
    echo [INFO] Normalized domain to: %DOMAIN%
)

set "DEFAULT_DOCROOT=D:\xampp\htdocs\almutlak\system"
set /p "DOCROOT=Enter document root [%DEFAULT_DOCROOT%]: "
if "%DOCROOT%"=="" set "DOCROOT=%DEFAULT_DOCROOT%"

if not exist "%DOCROOT%" (
    echo.
    echo [WARNING] DocumentRoot does not exist: %DOCROOT%
    choice /c YN /m "Continue anyway"
    if errorlevel 2 exit /b 1
)
echo %DOCROOT%| findstr /I /R "\\CSR$" >nul
if "%errorlevel%"=="0" (
    echo.
    echo [WARNING] You selected a CSR tools folder as DocumentRoot:
    echo          %DOCROOT%
    echo [WARNING] For sys.almutlak.local, DocumentRoot is usually: D:\xampp\htdocs\almutlak\system
    choice /c YN /m "Continue with this DocumentRoot anyway"
    if errorlevel 2 exit /b 1
)

set "CRT_DIR=%DOCROOT%\SSLCertificates"
set "KEY_DIR=%DOCROOT%\SSLCertificates"

echo.
echo Certificate subject values:
set /p "C=Country (2 letters) [SA]: "
if "%C%"=="" set "C=SA"
set /p "ST=State/Province [Makkah]: "
if "%ST%"=="" set "ST=Makkah"
set /p "L=Locality/City [Jeddah]: "
if "%L%"=="" set "L=Jeddah"
set /p "O=Organization [LocalDev]: "
if "%O%"=="" set "O=LocalDev"
set /p "OU=Org Unit [IT]: "
if "%OU%"=="" set "OU=IT"
set /p "DAYS=Certificate days [365]: "
if "%DAYS%"=="" set "DAYS=365"

echo.
choice /c YN /m "Redirect HTTP to HTTPS for this domain"
if errorlevel 2 (
    set "FORCE_HTTPS_REDIRECT=N"
) else (
    set "FORCE_HTTPS_REDIRECT=Y"
)

set "CERT_FILE=%CRT_DIR%\%DOMAIN%.crt"
set "KEY_FILE=%KEY_DIR%\%DOMAIN%.key"
set "SUBJ=/C=%C%/ST=%ST%/L=%L%/O=%O%/OU=%OU%/CN=%DOMAIN%"
set "SAN=DNS:%DOMAIN%"

echo.
echo ---------------------- Summary ----------------------
echo XAMPP Path   : %XAMPP_PATH%
echo Domain       : %DOMAIN%
echo DocumentRoot : %DOCROOT%
echo Cert File    : %CERT_FILE%
echo Key File     : %KEY_FILE%
echo Subject      : %SUBJ%
echo SAN          : %SAN%
echo HTTP Redirect: %FORCE_HTTPS_REDIRECT%
echo -----------------------------------------------------
echo.
choice /c YN /m "Proceed with setup"
if errorlevel 2 exit /b 1

if not exist "%CRT_DIR%" mkdir "%CRT_DIR%"

for /f %%I in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set "STAMP=%%I"
set "HTTPD_BAK=%HTTPD_CONF%.bak_%STAMP%"
set "VHOSTS_BAK=%VHOSTS_CONF%.bak_%STAMP%"
copy /Y "%HTTPD_CONF%" "%HTTPD_BAK%" >nul
if not "%errorlevel%"=="0" (
    echo [ERROR] Failed creating backup: %HTTPD_BAK%
    pause
    exit /b 1
)
copy /Y "%VHOSTS_CONF%" "%VHOSTS_BAK%" >nul
if not "%errorlevel%"=="0" (
    echo [ERROR] Failed creating backup: %VHOSTS_BAK%
    pause
    exit /b 1
)

echo.
echo [0/10] Backup created:
echo     %HTTPD_BAK%
echo     %VHOSTS_BAK%

echo.
echo [1/10] Creating SSL certificate...
set "OPENSSL_CONF=%OPENSSL_CNF%"
"%OPENSSL_EXE%" req -x509 -nodes -sha256 -days %DAYS% -newkey rsa:2048 -keyout "%KEY_FILE%" -out "%CERT_FILE%" -subj "%SUBJ%" -addext "subjectAltName=%SAN%" -addext "keyUsage=digitalSignature,keyEncipherment" -addext "extendedKeyUsage=serverAuth" -addext "basicConstraints=critical,CA:FALSE"
if not "%errorlevel%"=="0" (
    echo [ERROR] Failed to create certificate.
    echo This OpenSSL build may not support -addext. Update OpenSSL/XAMPP if needed.
    pause
    exit /b 1
)

echo.
echo [2/10] Adding domain to hosts file (if missing)...
set "TMP_HOSTS_PS=%TEMP%\xampp_hosts_fix_%RANDOM%.ps1"
>"%TMP_HOSTS_PS%" echo $hostsPath = '%HOSTS_FILE%'
>>"%TMP_HOSTS_PS%" echo $domain = '%DOMAIN%'
>>"%TMP_HOSTS_PS%" echo $line = '127.0.0.1 ' + $domain
>>"%TMP_HOSTS_PS%" echo $raw = Get-Content -Raw -Path $hostsPath
>>"%TMP_HOSTS_PS%" echo $original = $raw
>>"%TMP_HOSTS_PS%" echo $raw = $raw -replace '``r``n', "`r`n"
>>"%TMP_HOSTS_PS%" echo $raw = $raw -replace '`r`n', "`r`n"
>>"%TMP_HOSTS_PS%" echo $raw = [regex]::Replace($raw,'(?im)([A-Za-z0-9\.-])((?:127\.0\.0\.1^|0\.0\.0\.0)\s+)','$1`r`n$2')
>>"%TMP_HOSTS_PS%" echo $normalized = ($raw -ne $original)
>>"%TMP_HOSTS_PS%" echo if($raw -match ('(?im)^\s*127\.0\.0\.1\s+' + [regex]::Escape($domain) + '\s*$')) {
>>"%TMP_HOSTS_PS%" echo     if($normalized) {
>>"%TMP_HOSTS_PS%" echo         Set-Content -Path $hostsPath -Value $raw -Encoding ASCII
>>"%TMP_HOSTS_PS%" echo         Write-Host 'hosts file normalized; entry already exists.'
>>"%TMP_HOSTS_PS%" echo     } else {
>>"%TMP_HOSTS_PS%" echo         Write-Host 'hosts entry already exists.'
>>"%TMP_HOSTS_PS%" echo     }
>>"%TMP_HOSTS_PS%" echo } else {
>>"%TMP_HOSTS_PS%" echo     if($raw.Length -gt 0 -and -not ($raw.EndsWith("`r`n") -or $raw.EndsWith("`n"))) { $raw += "`r`n" }
>>"%TMP_HOSTS_PS%" echo     $raw += $line + "`r`n"
>>"%TMP_HOSTS_PS%" echo     Set-Content -Path $hostsPath -Value $raw -Encoding ASCII
>>"%TMP_HOSTS_PS%" echo     Write-Host 'hosts entry added.'
>>"%TMP_HOSTS_PS%" echo }

powershell -NoProfile -ExecutionPolicy Bypass -File "%TMP_HOSTS_PS%"
del "%TMP_HOSTS_PS%" >nul 2>&1
if not "%errorlevel%"=="0" (
    echo [ERROR] Failed updating hosts file.
    echo Run this script as Administrator and try again.
    pause
    exit /b 1
)

echo.
echo [3/10] Ensuring SSL directives in httpd.conf...
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
"$p='%HTTPD_CONF%'; $lines=Get-Content -Path $p; $list=New-Object 'System.Collections.Generic.List[string]'; $list.AddRange([string[]]$lines); ^
$required=@('LoadModule ssl_module modules/mod_ssl.so','LoadModule socache_shmcb_module modules/mod_socache_shmcb.so','Include conf/extra/httpd-ssl.conf','Include conf/extra/httpd-vhosts.conf'); ^
foreach($r in $required){ ^
  $has=$false; ^
    for($i=0;$i -lt $list.Count;$i++){ ^
        if($list[$i] -match ('^\s*#\s*' + [regex]::Escape($r) + '\s*$')){ $list[$i]=$r; $has=$true; break } ^
        if($list[$i] -match ('^\s*' + [regex]::Escape($r) + '\s*$')){ $has=$true; break } ^
  } ^
    if(-not $has){ $list.Add($r) } ^
}; ^
if(-not ($list -match '^\s*Listen\s+443\b')){ $list.Insert(0,'Listen 443') } ^
if(-not ($list -match '^\s*SSLSessionCache\s+')){ $list.Add('SSLSessionCache "shmcb:logs/ssl_scache(512000)"') } ^
Set-Content -Path $p -Value $list -Encoding ASCII"
if not "%errorlevel%"=="0" (
    echo [ERROR] Failed updating httpd.conf.
    copy /Y "%HTTPD_BAK%" "%HTTPD_CONF%" >nul
    pause
    exit /b 1
)

echo.
echo [4/10] Writing VirtualHost config (replace old block for this domain)...
set "TMP_PS=%TEMP%\xampp_https_vhost_%RANDOM%.ps1"
>"%TMP_PS%" echo $vhostPath = "%VHOSTS_CONF%"
>>"%TMP_PS%" echo $domain = "%DOMAIN%"
>>"%TMP_PS%" echo $docroot = "%DOCROOT%" -replace '\\','/'
>>"%TMP_PS%" echo $crt = "%CERT_FILE%" -replace '\\','/'
>>"%TMP_PS%" echo $key = "%KEY_FILE%" -replace '\\','/'
>>"%TMP_PS%" echo $redirect = "%FORCE_HTTPS_REDIRECT%"
>>"%TMP_PS%" echo $tag = ($domain -replace '[^a-zA-Z0-9]','_')
>>"%TMP_PS%" echo $begin = "# BEGIN AUTO_HTTPS_$tag"
>>"%TMP_PS%" echo $end = "# END AUTO_HTTPS_$tag"
>>"%TMP_PS%" echo $content = Get-Content -Raw -Path $vhostPath
>>"%TMP_PS%" echo $escaped = [regex]::Escape($domain)
>>"%TMP_PS%" echo $patternManaged = [regex]::Escape($begin) + '(?s).*?' + [regex]::Escape($end) + "\s*"
>>"%TMP_PS%" echo $pattern80 = '(?is)^^\s*^<VirtualHost\s+[*]:80^>.*?\bServerName\s+' + $escaped + '\b.*?^</VirtualHost^>\s*'
>>"%TMP_PS%" echo $pattern443 = '(?is)^^\s*^<VirtualHost\s+[*]:443^>.*?\bServerName\s+' + $escaped + '\b.*?^</VirtualHost^>\s*'
>>"%TMP_PS%" echo $content = [regex]::Replace($content,$patternManaged,'')
>>"%TMP_PS%" echo $content = [regex]::Replace($content,$pattern80,'')
>>"%TMP_PS%" echo $content = [regex]::Replace($content,$pattern443,'')
>>"%TMP_PS%" echo $redir = ''
>>"%TMP_PS%" echo if ($redirect -eq 'Y') { $redir = '    Redirect "/" "https://' + $domain + '/"' }
>>"%TMP_PS%" echo $block = @"
>>"%TMP_PS%" echo # BEGIN AUTO_HTTPS_$tag
>>"%TMP_PS%" echo ^<VirtualHost *:80^>
>>"%TMP_PS%" echo     ServerName $domain
>>"%TMP_PS%" echo     DocumentRoot "$docroot"
>>"%TMP_PS%" echo $redir
>>"%TMP_PS%" echo     ^<Directory "$docroot"^>
>>"%TMP_PS%" echo         AllowOverride All
>>"%TMP_PS%" echo         Require all granted
>>"%TMP_PS%" echo     ^</Directory^>
>>"%TMP_PS%" echo ^</VirtualHost^>
>>"%TMP_PS%" echo(
>>"%TMP_PS%" echo ^<VirtualHost *:443^>
>>"%TMP_PS%" echo     ServerName $domain
>>"%TMP_PS%" echo     DocumentRoot "$docroot"
>>"%TMP_PS%" echo     SSLEngine on
>>"%TMP_PS%" echo     SSLCertificateFile "$crt"
>>"%TMP_PS%" echo     SSLCertificateKeyFile "$key"
>>"%TMP_PS%" echo     ^<Directory "$docroot"^>
>>"%TMP_PS%" echo         AllowOverride All
>>"%TMP_PS%" echo         Require all granted
>>"%TMP_PS%" echo     ^</Directory^>
>>"%TMP_PS%" echo ^</VirtualHost^>
>>"%TMP_PS%" echo # END AUTO_HTTPS_$tag
>>"%TMP_PS%" echo "@
>>"%TMP_PS%" echo $new = $content.TrimEnd() + "`r`n`r`n" + $block + "`r`n"
>>"%TMP_PS%" echo $new = [regex]::Replace($new,'(?im)^\s*ECHO( is off\.)?\s*$','').TrimEnd() + "`r`n"
>>"%TMP_PS%" echo Set-Content -Path $vhostPath -Value $new -Encoding ASCII

powershell -NoProfile -ExecutionPolicy Bypass -File "%TMP_PS%"
del "%TMP_PS%" >nul 2>&1
if not "%errorlevel%"=="0" (
    echo [ERROR] Failed updating vhosts file.
    copy /Y "%HTTPD_BAK%" "%HTTPD_CONF%" >nul
    copy /Y "%VHOSTS_BAK%" "%VHOSTS_CONF%" >nul
    pause
    exit /b 1
)

echo.
echo [5/10] Validating Apache config...
"%HTTPD_EXE%" -t -f "%HTTPD_CONF%"
if not "%errorlevel%"=="0" (
    echo.
    echo [ERROR] Apache syntax check failed. Please review output above.
    echo Restoring backups...
    copy /Y "%HTTPD_BAK%" "%HTTPD_CONF%" >nul
    copy /Y "%VHOSTS_BAK%" "%VHOSTS_CONF%" >nul
    pause
    exit /b 1
)

echo.
echo [6/10] Apache restart skipped.
echo Please restart Apache manually from XAMPP Control Panel after this wizard closes.

set "P80=NO"
set "P443=NO"
netstat -ano -p tcp | findstr /R /C:":80 .*LISTENING" >nul && set "P80=YES"
netstat -ano -p tcp | findstr /R /C:":443 .*LISTENING" >nul && set "P443=YES"

echo [INFO] Port 80 listening  : %P80%
echo [INFO] Port 443 listening : %P443%
if /I "%P80%"=="YES" if /I "%P443%"=="NO" (
    echo [WARNING] Apache appears to be running with old state on port 80 only.
    echo [WARNING] Open XAMPP Control Panel as Administrator, then Stop Apache and Start Apache again.
)
if /I "%P80%"=="NO" if /I "%P443%"=="NO" (
    echo [WARNING] Apache does not appear to be listening on 80/443 yet.
    echo [WARNING] Start Apache from XAMPP Control Panel as Administrator.
)

echo.
echo [7/10] Trust certificate in Windows certificate stores...
choice /c YN /m "Import certificate now (Machine+User TrustedPeople + Root)"
if errorlevel 2 (
    echo Skipped certificate trust import.
) else (
    call :ImportCertAllStores "%DOMAIN%" "%CERT_FILE%"
    if errorlevel 1 (
        echo [WARNING] Certificate import step did not complete successfully.
    ) else (
        echo [INFO] Certificate trust import completed.
    )
)

echo.
echo [8/10] Configure Firefox to use Windows certificate store...
choice /c YN /m "Enable Firefox enterprise roots policy now"
if errorlevel 2 (
    echo Skipped Firefox policy setup.
) else (
    set "FF_DIST_DIR="
    if exist "%ProgramFiles%\Mozilla Firefox\distribution" set "FF_DIST_DIR=%ProgramFiles%\Mozilla Firefox\distribution"
    if "%FF_DIST_DIR%"=="" if exist "%ProgramFiles(x86)%\Mozilla Firefox\distribution" set "FF_DIST_DIR=%ProgramFiles(x86)%\Mozilla Firefox\distribution"

    if "%FF_DIST_DIR%"=="" (
        echo [WARNING] Firefox distribution folder not found.
        echo If Firefox is installed, set security.enterprise_roots.enabled=true in about:config manually.
    ) else (
        if not exist "%FF_DIST_DIR%" mkdir "%FF_DIST_DIR%"
        >"%FF_DIST_DIR%\policies.json" echo {
        >>"%FF_DIST_DIR%\policies.json" echo   "policies": {
        >>"%FF_DIST_DIR%\policies.json" echo     "Certificates": {
        >>"%FF_DIST_DIR%\policies.json" echo       "ImportEnterpriseRoots": true
        >>"%FF_DIST_DIR%\policies.json" echo     }
        >>"%FF_DIST_DIR%\policies.json" echo   }
        >>"%FF_DIST_DIR%\policies.json" echo }
        echo Firefox policy written: %FF_DIST_DIR%\policies.json
        echo Restart Firefox fully to apply this policy.
    )
)

echo.
echo [9/10] Final validation (active vhost mapping for this domain)...
"%HTTPD_EXE%" -S -f "%HTTPD_CONF%" | findstr /I /C:"%DOMAIN%" /C:"*:443"

echo.
echo [10/10] Endpoint health checks...
set "TMP_HEALTH_PS=%TEMP%\xampp_https_health_%RANDOM%.ps1"
>"%TMP_HEALTH_PS%" echo $domain = '%DOMAIN%'
>>"%TMP_HEALTH_PS%" echo $hostsPath = '%HOSTS_FILE%'
>>"%TMP_HEALTH_PS%" echo $port80 = '%P80%'
>>"%TMP_HEALTH_PS%" echo $port443 = '%P443%'
>>"%TMP_HEALTH_PS%" echo $dnsOk = $false
>>"%TMP_HEALTH_PS%" echo $httpOk = $false
>>"%TMP_HEALTH_PS%" echo $httpsOk = $false
>>"%TMP_HEALTH_PS%" echo $hostsOk = $false
>>"%TMP_HEALTH_PS%" echo $osTrustOk = $false
>>"%TMP_HEALTH_PS%" echo try {
>>"%TMP_HEALTH_PS%" echo     $ips = [System.Net.Dns]::GetHostAddresses($domain) ^| Select-Object -ExpandProperty IPAddressToString -Unique
>>"%TMP_HEALTH_PS%" echo     if($ips){ $dnsOk = $true; Write-Host ('[PASS] DNS/hosts resolves for ' + $domain + ' -> ' + ($ips -join ', ')) }
>>"%TMP_HEALTH_PS%" echo } catch {
>>"%TMP_HEALTH_PS%" echo     Write-Host ('[FAIL] DNS/hosts does not resolve for ' + $domain)
>>"%TMP_HEALTH_PS%" echo }
>>"%TMP_HEALTH_PS%" echo try {
>>"%TMP_HEALTH_PS%" echo     $pattern = '127\.0\.0\.1\s+' + [regex]::Escape($domain)
>>"%TMP_HEALTH_PS%" echo     if(Select-String -Path $hostsPath -Pattern $pattern -SimpleMatch:$false -Quiet){
>>"%TMP_HEALTH_PS%" echo         $hostsOk = $true
>>"%TMP_HEALTH_PS%" echo         Write-Host ('[PASS] hosts file has 127.0.0.1 mapping for ' + $domain)
>>"%TMP_HEALTH_PS%" echo     } else {
>>"%TMP_HEALTH_PS%" echo         Write-Host ('[FAIL] hosts file missing 127.0.0.1 mapping for ' + $domain)
>>"%TMP_HEALTH_PS%" echo     }
>>"%TMP_HEALTH_PS%" echo } catch {
>>"%TMP_HEALTH_PS%" echo     Write-Host '[WARNING] Could not verify hosts file mapping.'
>>"%TMP_HEALTH_PS%" echo }
>>"%TMP_HEALTH_PS%" echo if($port80 -ne 'YES'){ Write-Host '[FAIL] Port 80 is not listening. Apache is not running (or not started yet).' }
>>"%TMP_HEALTH_PS%" echo if($port443 -ne 'YES'){ Write-Host '[FAIL] Port 443 is not listening. Apache SSL vhost is not active yet.' }
>>"%TMP_HEALTH_PS%" echo try {
>>"%TMP_HEALTH_PS%" echo     if($port80 -eq 'YES'){
>>"%TMP_HEALTH_PS%" echo         $resp = Invoke-WebRequest -UseBasicParsing -Uri ('http://' + $domain + '/') -TimeoutSec 8
>>"%TMP_HEALTH_PS%" echo         if($resp.StatusCode -ge 200 -and $resp.StatusCode -lt 400){ $httpOk = $true; Write-Host ('[PASS] HTTP endpoint reachable (' + $resp.StatusCode + ')') }
>>"%TMP_HEALTH_PS%" echo     }
>>"%TMP_HEALTH_PS%" echo } catch {
>>"%TMP_HEALTH_PS%" echo     Write-Host '[FAIL] HTTP endpoint not reachable'
>>"%TMP_HEALTH_PS%" echo }
>>"%TMP_HEALTH_PS%" echo try {
>>"%TMP_HEALTH_PS%" echo     if($port443 -eq 'YES'){
>>"%TMP_HEALTH_PS%" echo         [System.Net.ServicePointManager]::ServerCertificateValidationCallback = { $true }
>>"%TMP_HEALTH_PS%" echo         $resp = Invoke-WebRequest -UseBasicParsing -Uri ('https://' + $domain + '/') -TimeoutSec 8
>>"%TMP_HEALTH_PS%" echo         if($resp.StatusCode -ge 200 -and $resp.StatusCode -lt 400){ $httpsOk = $true; Write-Host ('[PASS] HTTPS endpoint reachable (' + $resp.StatusCode + ')') }
>>"%TMP_HEALTH_PS%" echo     }
>>"%TMP_HEALTH_PS%" echo } catch {
>>"%TMP_HEALTH_PS%" echo     Write-Host '[FAIL] HTTPS endpoint not reachable'
>>"%TMP_HEALTH_PS%" echo }
>>"%TMP_HEALTH_PS%" echo try {
>>"%TMP_HEALTH_PS%" echo     $curl = Get-Command curl.exe -ErrorAction SilentlyContinue
>>"%TMP_HEALTH_PS%" echo     if($curl -and $port443 -eq 'YES'){
>>"%TMP_HEALTH_PS%" echo         $null = & $curl.Source -sS ('https://' + $domain + '/') -o NUL
>>"%TMP_HEALTH_PS%" echo         if($LASTEXITCODE -eq 0){
>>"%TMP_HEALTH_PS%" echo             $osTrustOk = $true
>>"%TMP_HEALTH_PS%" echo             Write-Host '[PASS] Windows/Schannel trust check passed (curl).'
>>"%TMP_HEALTH_PS%" echo         } else {
>>"%TMP_HEALTH_PS%" echo             Write-Host '[FAIL] Windows/Schannel trust check failed (curl).'
>>"%TMP_HEALTH_PS%" echo         }
>>"%TMP_HEALTH_PS%" echo     }
>>"%TMP_HEALTH_PS%" echo } catch {
>>"%TMP_HEALTH_PS%" echo     Write-Host '[WARNING] Could not run Windows trust check via curl.'
>>"%TMP_HEALTH_PS%" echo }
>>"%TMP_HEALTH_PS%" echo if(-not ($dnsOk -and $hostsOk -and $httpOk -and $httpsOk -and $osTrustOk)){
>>"%TMP_HEALTH_PS%" echo     Write-Host '[WARNING] One or more endpoint checks failed. Review warnings above.'
>>"%TMP_HEALTH_PS%" echo }

powershell -NoProfile -ExecutionPolicy Bypass -File "%TMP_HEALTH_PS%"
del "%TMP_HEALTH_PS%" >nul 2>&1

echo.
echo ================================================================
echo Setup complete.
echo Domain: https://%DOMAIN%
echo.
echo Next:
echo 1) Open https://%DOMAIN%
echo 2) If browser is already open, close and reopen it
echo 3) In Firefox, verify URL starts with https:// and certificate warning is gone
echo 4) If needed, restore backups:
echo    copy /Y "%HTTPD_BAK%" "%HTTPD_CONF%"
echo    copy /Y "%VHOSTS_BAK%" "%VHOSTS_CONF%"
echo ================================================================
echo.
set "SCRIPT_DIR=%~dp0"
set "STRAY_FILE=%SCRIPT_DIR%'"
if exist "%STRAY_FILE%" (
    del /F /Q "%STRAY_FILE%" >nul 2>&1
    if exist "%STRAY_FILE%" (
        echo [WARNING] Could not remove stray file: %STRAY_FILE%
    ) else (
        echo [INFO] Removed stray file: %STRAY_FILE%
    )
)
echo.
pause
exit /b 0

:ImportCertAllStores
set "IMPORT_DOMAIN=%~1"
set "IMPORT_CERT=%~2"

where certutil >nul 2>&1
if not "%errorlevel%"=="0" (
    echo [WARNING] certutil is not available. Cannot import certificate automatically.
    exit /b 1
)

if not exist "%IMPORT_CERT%" (
    echo [WARNING] Certificate file not found: %IMPORT_CERT%
    exit /b 1
)

certutil -delstore TrustedPeople "%IMPORT_DOMAIN%" >nul 2>&1
certutil -delstore Root "%IMPORT_DOMAIN%" >nul 2>&1
certutil -user -delstore TrustedPeople "%IMPORT_DOMAIN%" >nul 2>&1
certutil -user -delstore Root "%IMPORT_DOMAIN%" >nul 2>&1

certutil -f -addstore TrustedPeople "%IMPORT_CERT%" >nul 2>&1
if not "%errorlevel%"=="0" exit /b 1
certutil -f -addstore Root "%IMPORT_CERT%" >nul 2>&1
if not "%errorlevel%"=="0" exit /b 1
certutil -user -f -addstore TrustedPeople "%IMPORT_CERT%" >nul 2>&1
if not "%errorlevel%"=="0" exit /b 1
certutil -user -f -addstore Root "%IMPORT_CERT%" >nul 2>&1
if not "%errorlevel%"=="0" exit /b 1

certutil -store TrustedPeople "%IMPORT_DOMAIN%" | findstr /I "Serial Thumbprint" >nul 2>&1
if not "%errorlevel%"=="0" exit /b 1

echo Certificate imported into Machine/User stores successfully.
exit /b 0
