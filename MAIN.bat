@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"
title Control de Vehiculos Carrousel - MAIN

set "APP_NAME=Control de Vehiculos Carrousel"
set "LOCAL_URL=http://localhost/ControlVehiculosCarrousel/public/"
set "PORTAL_URL=https://portal.carrousel-apps.com/portal/"
set "REPO_URL=https://github.com/dlomito-002/vehicle-app"
set "MYSQLDUMP=C:\xampp\mysql\bin\mysqldump.exe"
set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" set "PHP=php"

if not exist "artisan" (
  echo [ERROR] Ejecuta MAIN.bat desde la carpeta del proyecto.
  pause
  exit /b 1
)

:menu
cls
set "VERSION=desconocida"
if exist "VERSION" set /p VERSION=<VERSION
set "BRANCH=sin-git"
for /f "delims=" %%B in ('git branch --show-current 2^>nul') do set "BRANCH=%%B"
call :read_env DB_CONNECTION DB_CONN
call :read_env DB_DATABASE DB_NAME
echo ============================================================
echo                %APP_NAME%
echo ============================================================
echo Version: %VERSION%
echo Rama: %BRANCH%
echo Base: %DB_CONN% / %DB_NAME%
echo.
echo [1] Abrir Control de Vehiculos
echo [2] Abrir Portal de Sistemas
echo [3] Estado Git
echo [4] Actualizar desde GitHub (incluye migraciones y compilacion)
echo [5] Validar aplicacion
echo [6] Aplicar migraciones de BD
echo [7] Respaldar BD
echo [8] Validar SMTP
echo [9] Guardar cambios / Commit
echo [10] Push GitHub
echo [11] Abrir GitHub
echo.
echo [0] Salir
echo ============================================================
set "OP="
set /p "OP=Seleccione una opcion: "

if "%OP%"=="1" start "" "%LOCAL_URL%" & goto menu
if "%OP%"=="2" start "" "%PORTAL_URL%" & goto menu
if "%OP%"=="3" goto status
if "%OP%"=="4" goto pull
if "%OP%"=="5" goto validate
if "%OP%"=="6" goto migrate
if "%OP%"=="7" goto backup
if "%OP%"=="8" start "" "%LOCAL_URL%admin/correo" & goto menu
if "%OP%"=="9" goto commit
if "%OP%"=="10" goto push
if "%OP%"=="11" start "" "%REPO_URL%" & goto menu
if "%OP%"=="0" exit /b 0
goto menu

:status
echo.
call :guard
echo.
git status --short
echo.
git diff --check
echo.
git log -1 --oneline
pause
goto menu

:pull
call :require_main || goto menu
call :require_clean || goto menu
echo.
git pull --ff-only origin main
if errorlevel 1 (
  pause
  goto menu
)
echo.
call composer install --no-interaction
call npm ci
call npm run build
"%PHP%" artisan migrate --force
"%PHP%" artisan optimize:clear
pause
goto menu

:validate
echo.
if not exist "vendor\autoload.php" (
  echo [ERROR] Falta vendor. Ejecuta composer install.
  pause
  goto menu
)
echo [1/4] Limpiar cache de Laravel
"%PHP%" artisan optimize:clear >nul || goto valfail
echo [2/4] Compilar vistas Blade
"%PHP%" artisan view:cache >nul || goto valfail
echo [3/4] Compilar estilos y scripts (Vite)
call npm run build || goto valfail
echo [4/4] Pruebas (base SQLite en memoria, no toca datos)
"%PHP%" artisan test || goto valfail
echo.
echo [OK] Aplicacion validada.
pause
goto menu

:valfail
echo.
echo [ERROR] La validacion fallo. No hagas commit hasta corregirlo.
pause
goto menu

:migrate
echo.
"%PHP%" artisan migrate --force
pause
goto menu

:backup
if not exist "backups" mkdir "backups"
for /f %%I in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set "STAMP=%%I"
if not defined STAMP set "STAMP=manual"
if /I "%DB_CONN%"=="sqlite" (
  copy /y "database\database.sqlite" "backups\database_%STAMP%.sqlite" >nul && echo [OK] backups\database_%STAMP%.sqlite
  pause
  goto menu
)
if not exist "%MYSQLDUMP%" (
  echo [ERROR] No se encontro mysqldump: %MYSQLDUMP%
  pause
  goto menu
)
call :read_env DB_USERNAME DB_USER
call :read_env DB_PASSWORD DB_PASS
set "MYSQL_AUTH=-u%DB_USER%"
if defined DB_PASS set "MYSQL_AUTH=-u%DB_USER% -p%DB_PASS%"
echo.
echo Respaldando %DB_NAME%...
"%MYSQLDUMP%" %MYSQL_AUTH% --single-transaction --routines --triggers --default-character-set=utf8mb4 "%DB_NAME%" > "backups\%DB_NAME%_%STAMP%.sql"
if errorlevel 1 (echo [ERROR] No se pudo crear el respaldo.) else (echo [OK] backups\%DB_NAME%_%STAMP%.sql)
pause
goto menu

:commit
call :guard || goto menu
git diff --check
if errorlevel 1 (
  echo [BLOQUEADO] git diff --check encontro errores.
  pause
  goto menu
)
echo.
git status --short
echo.
set /p "MSG=Mensaje del commit: "
if "%MSG%"=="" goto menu
set /p "CONF=Escriba COMMIT para guardar los cambios: "
if /I not "%CONF%"=="COMMIT" goto menu
git add -A
git commit -m "%MSG%"
pause
goto menu

:push
call :guard || goto menu
call :require_clean || goto menu
echo.
echo Rama actual: %BRANCH%
set /p "CONF=Escriba PUSH para subir %BRANCH% a GitHub: "
if /I not "%CONF%"=="PUSH" goto menu
git push origin %BRANCH%
pause
goto menu

:read_env
set "%2="
for /f "usebackq tokens=1,* delims==" %%A in (".env") do if /I "%%A"=="%1" set "%2=%%~B"
exit /b 0

:require_main
set "CURRENT_BRANCH="
for /f "delims=" %%B in ('git branch --show-current') do set "CURRENT_BRANCH=%%B"
if /I not "!CURRENT_BRANCH!"=="main" (
  echo [BLOQUEADO] Rama actual: !CURRENT_BRANCH!
  echo Cambie a main antes de continuar.
  pause
  exit /b 1
)
exit /b 0

:require_clean
git status --porcelain > "%TEMP%\vehiculos_main_status.txt"
for %%A in ("%TEMP%\vehiculos_main_status.txt") do set "STATUS_SIZE=%%~zA"
if not "!STATUS_SIZE!"=="0" (
  echo [BLOQUEADO] Hay cambios locales pendientes:
  type "%TEMP%\vehiculos_main_status.txt"
  del "%TEMP%\vehiculos_main_status.txt" >nul 2>&1
  pause
  exit /b 1
)
del "%TEMP%\vehiculos_main_status.txt" >nul 2>&1
exit /b 0

:guard
git ls-files --error-unmatch ".env" >nul 2>&1
if not errorlevel 1 (
  echo [BLOQUEADO] .env contiene claves y esta rastreado por Git.
  pause
  exit /b 1
)
git ls-files | findstr /i /r "storage/app/public/flota backups/ database/database.sqlite" >nul
if not errorlevel 1 (
  echo [BLOQUEADO] Hay fotos, respaldos o la base SQLite rastreados por Git.
  pause
  exit /b 1
)
echo [OK] Auditoria basica de archivos sensibles.
exit /b 0
