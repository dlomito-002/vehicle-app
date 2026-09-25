@echo off
setlocal EnableExtensions DisableDelayedExpansion
chcp 65001 >nul
title Control de Vehiculos Carrousel

cd /d "%~dp0"
set "ROOT=%CD%"
set "BRANCH=luis/setup-local"
set "REMOTE=origin"
set "REPO=https://github.com/dlomito-002/vehicle-app"
set "APPURL=http://localhost/ControlVehiculosCarrousel/public/"
set "PATCH=%ROOT%\tools\APLICAR_RONDA_4A_HELPDESK.ps1"

rem Tema sobrio: fondo negro, texto gris claro
color 07

if not exist ".git" goto :badroot
if not exist "artisan" goto :badroot

:menu
cls
call :refresh_state

echo.
echo  CONTROL DE VEHICULOS CARROUSEL
echo  ================================================================
echo  Rama      %CURRENT%
echo  Estado    %SYNCSTATE%
echo  Local     %LOCALSTATE%
echo  ================================================================
echo.
echo  [1] Actualizar desde GitHub
echo  [2] Aplicar mejoras visuales Helpdesk
echo  [3] Validar aplicacion
echo.
echo  [4] Guardar version
echo  [5] Subir a GitHub
echo.
echo  [6] Ver estado Git
echo  [7] Abrir aplicacion
echo  [8] Abrir GitHub
echo  [9] Herramientas
echo.
echo  [0] Salir
echo.
set "OPT="
set /p "OPT=  Seleccione una opcion: "

if "%OPT%"=="1" goto :update
if "%OPT%"=="2" goto :round4a
if "%OPT%"=="3" goto :validate
if "%OPT%"=="4" goto :commit
if "%OPT%"=="5" goto :push
if "%OPT%"=="6" goto :status
if "%OPT%"=="7" start "" "%APPURL%" & goto :menu
if "%OPT%"=="8" start "" "%REPO%/tree/%BRANCH%" & goto :menu
if "%OPT%"=="9" goto :tools
if "%OPT%"=="0" exit /b 0

echo.
echo  Opcion no valida.
timeout /t 1 >nul
goto :menu

:refresh_state
for /f "delims=" %%B in ('git branch --show-current 2^>nul') do set "CURRENT=%%B"

set "LOCALSTATE=Limpio"
git status --porcelain | findstr . >nul
if not errorlevel 1 set "LOCALSTATE=Con cambios"

set "SYNCSTATE=Sin comprobar"
git rev-parse --verify "%REMOTE%/%BRANCH%" >nul 2>&1
if not errorlevel 1 (
    for /f "tokens=1,2" %%A in ('git rev-list --left-right --count "%REMOTE%/%BRANCH%...HEAD" 2^>nul') do (
        if "%%A"=="0" (
            if "%%B"=="0" (
                set "SYNCSTATE=Sincronizada"
            ) else (
                set "SYNCSTATE=%%B commit(s) por subir"
            )
        ) else (
            if "%%B"=="0" (
                set "SYNCSTATE=%%A commit(s) por bajar"
            ) else (
                set "SYNCSTATE=Rama divergente"
            )
        )
    )
)
exit /b 0

:update
cls
call :section "ACTUALIZAR DESDE GITHUB"

call :ensure_branch
if errorlevel 1 goto :return

git diff --quiet
if errorlevel 1 goto :dirty
git diff --cached --quiet
if errorlevel 1 goto :dirty

echo  Comprobando repositorio remoto...
git fetch %REMOTE% --prune
if errorlevel 1 goto :gitfail

echo  Actualizando %BRANCH%...
git pull --ff-only %REMOTE% %BRANCH%
if errorlevel 1 (
    echo.
    echo  [ERROR] Git detuvo la actualizacion.
    echo  No se hizo merge ni rebase automatico.
    goto :return
)

echo.
echo  Limpiando cache Laravel...
php artisan optimize:clear >nul
if errorlevel 1 (
    echo  [AVISO] No se pudo limpiar cache Laravel.
) else (
    echo  [OK] Cache limpia.
)

echo.
echo  [OK] Proyecto actualizado.
git log -1 --oneline
goto :return

:round4a
cls
call :section "MEJORAS VISUALES - RONDA 4A"

call :ensure_branch
if errorlevel 1 goto :return

if not exist "%PATCH%" (
    echo  [ERROR] Falta tools\APLICAR_RONDA_4A_HELPDESK.ps1
    goto :return
)

echo  Se aplicara:
echo.
echo    - Modo oscuro completo
echo    - Sidebar y topbar alineados al Helpdesk
echo    - Mejor aprovechamiento del ancho
echo    - Botones y titulos consistentes
echo    - Acceso al Portal
echo    - Iconografia uniforme
echo.
choice /c SN /n /m "  Continuar? [S/N]: "
if errorlevel 2 goto :menu

echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%PATCH%" -ProjectPath "%ROOT%"

if errorlevel 1 (
    echo.
    echo  [ERROR] La mejora no termino correctamente.
    echo  No hagas commit todavia.
    goto :return
)

echo.
echo  [OK] Mejora aplicada y validada.
goto :return

:validate
cls
call :section "VALIDACION COMPLETA"

where php >nul 2>&1
if errorlevel 1 (
    echo  [ERROR] PHP no esta disponible en PATH.
    goto :return
)

where npm >nul 2>&1
if errorlevel 1 (
    echo  [ERROR] npm no esta disponible en PATH.
    goto :return
)

if not exist "vendor\autoload.php" (
    echo  [ERROR] Falta vendor. Ejecuta composer install.
    goto :return
)

if not exist "node_modules" (
    echo  [ERROR] Falta node_modules. Ejecuta npm install.
    goto :return
)

echo  [1/4] Laravel cache
php artisan optimize:clear >nul
if errorlevel 1 goto :valfail
echo        OK

echo  [2/4] Vistas Blade
php artisan view:cache >nul
if errorlevel 1 goto :valfail
echo        OK

echo  [3/4] Build Vite
call npm run build
if errorlevel 1 goto :valfail

echo.
echo  [4/4] Tests
php artisan test
if errorlevel 1 goto :valfail

echo.
echo  ================================================================
echo  VALIDACION COMPLETA: OK
echo  ================================================================
goto :return

:commit
cls
call :section "GUARDAR VERSION"

call :ensure_branch
if errorlevel 1 goto :return

git status --short
echo.

git status --porcelain | findstr . >nul
if errorlevel 1 (
    echo  No hay cambios para guardar.
    goto :return
)

choice /c SN /n /m "  Guardar todos estos cambios? [S/N]: "
if errorlevel 2 goto :menu

git add -A
git reset -- ".round4a_backup" >nul 2>&1
git reset -- ".round4a_backup\*" >nul 2>&1

echo.
echo  Archivos preparados:
git status --short
echo.

set "MSG="
set /p "MSG=  Mensaje del commit: "
if not defined MSG set "MSG=Mejoras Control de Vehiculos"

git commit -m "%MSG%"
if errorlevel 1 goto :gitfail

echo.
echo  [OK] Version guardada.
git log -1 --oneline
goto :return

:push
cls
call :section "SUBIR A GITHUB"

call :ensure_branch
if errorlevel 1 goto :return

git fetch %REMOTE% --prune >nul 2>&1

echo  Commits pendientes de subir:
echo.
git log --oneline "%REMOTE%/%BRANCH%..HEAD"
echo.

choice /c SN /n /m "  Subir ahora? [S/N]: "
if errorlevel 2 goto :menu

git push %REMOTE% %BRANCH%
if errorlevel 1 goto :gitfail

echo.
echo  [OK] Rama subida a GitHub.
goto :return

:status
cls
call :section "ESTADO GIT"

echo  Rama:
echo    %CURRENT%
echo.
echo  Ultimo commit:
git log -1 --oneline --decorate
echo.
echo  Cambios locales:
git status --short
echo.
echo  Diferencia contra origin:
git fetch %REMOTE% --prune >nul 2>&1
git rev-list --left-right --count "%REMOTE%/%BRANCH%...HEAD"
echo.
echo  Leyenda: izquierda = por bajar / derecha = por subir
goto :return

:tools
cls
call :section "HERRAMIENTAS"

echo  [1] Diagnostico avanzado
echo  [2] Comparar contra main
echo  [3] Limpiar respaldo viejo .round4a_backup
echo  [4] Abrir comparacion en GitHub
echo.
echo  [0] Volver
echo.
set "T="
set /p "T=  Opcion: "

if "%T%"=="1" goto :diagnostic
if "%T%"=="2" goto :compare
if "%T%"=="3" goto :cleanup
if "%T%"=="4" start "" "%REPO%/compare/main...%BRANCH%" & goto :tools
if "%T%"=="0" goto :menu
goto :tools

:diagnostic
cls
call :section "DIAGNOSTICO AVANZADO"

echo  Git:
git --version
echo.
echo  PHP:
php -v
echo.
echo  Composer:
composer --version
echo.
echo  Node:
node --version
echo.
echo  npm:
call npm --version
echo.
echo  Remotos:
git remote -v
echo.
echo  Estado:
git status --short
goto :return_tools

:compare
cls
call :section "COMPARACION CONTRA MAIN"

git fetch %REMOTE% --prune
if errorlevel 1 goto :gitfail

echo  Commits:
git log --oneline --decorate "%REMOTE%/main..HEAD"
echo.
echo  Resumen:
git diff --stat "%REMOTE%/main...HEAD"
echo.
echo  Ahead / Behind:
git rev-list --left-right --count "%REMOTE%/main...HEAD"
goto :return_tools

:cleanup
cls
call :section "LIMPIAR RESPALDO VIEJO"

if not exist ".round4a_backup" (
    echo  No existe .round4a_backup.
    goto :return_tools
)

echo  Se eliminara:
echo  %ROOT%\.round4a_backup
echo.
choice /c SN /n /m "  Eliminar? [S/N]: "
if errorlevel 2 goto :tools

rmdir /s /q ".round4a_backup"
if exist ".round4a_backup" (
    echo.
    echo  [ERROR] No se pudo eliminar.
) else (
    echo.
    echo  [OK] Respaldo viejo eliminado.
)
goto :return_tools

:ensure_branch
for /f "delims=" %%B in ('git branch --show-current 2^>nul') do set "CURRENT=%%B"

if /I "%CURRENT%"=="%BRANCH%" exit /b 0

echo  Rama actual: %CURRENT%
echo  Rama requerida: %BRANCH%
echo.
choice /c SN /n /m "  Cambiar de rama? [S/N]: "
if errorlevel 2 exit /b 1

git switch %BRANCH%
exit /b %errorlevel%

:dirty
echo.
echo  [ERROR] Hay cambios locales rastreados.
echo  Para protegerlos, no se ejecutara pull.
echo.
git status --short
goto :return

:gitfail
echo.
echo  [ERROR] Git devolvio un error.
echo  No se forzo ningun merge ni descarte.
goto :return

:valfail
echo.
echo  ================================================================
echo  VALIDACION FALLIDA
echo  No hagas commit hasta corregir el error.
echo  ================================================================
goto :return

:badroot
cls
echo.
echo  MAIN.bat debe estar en la raiz del proyecto:
echo  C:\xampp\htdocs\ControlVehiculosCarrousel
echo.
pause
exit /b 1

:section
echo.
echo  %~1
echo  ================================================================
echo.
exit /b 0

:return
echo.
pause
goto :menu

:return_tools
echo.
pause
goto :tools
