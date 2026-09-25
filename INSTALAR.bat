@echo off
REM Ayuda opcional para Windows: ejecuta los mismos comandos de INSTALACION_WINDOWS.txt.
REM La aplicacion no depende de este archivo; todo el trabajo lo hace "php artisan app:install".
cd /d "%~dp0"

where php >nul 2>nul
if errorlevel 1 (
    if exist "C:\xampp\php\php.exe" (
        set "PATH=C:\xampp\php;%PATH%"
    ) else (
        echo No se encontro PHP. Instala XAMPP o agrega php al PATH.
        pause
        exit /b 1
    )
)

where composer >nul 2>nul
if errorlevel 1 (
    echo No se encontro Composer. Instalalo desde https://getcomposer.org
    pause
    exit /b 1
)

where npm >nul 2>nul
if errorlevel 1 (
    echo No se encontro Node.js/npm. Instalalo desde https://nodejs.org
    pause
    exit /b 1
)

echo Recuerda: crea antes una base de datos MySQL vacia en phpMyAdmin.
pause

call composer install --no-dev --optimize-autoloader || goto :error
call npm ci || goto :error
call npm run build || goto :error
php artisan app:install %* || goto :error

echo.
echo Instalacion terminada. Configura el correo (MAIL_*) en .env para poder iniciar sesion.
pause
exit /b 0

:error
echo.
echo La instalacion se detuvo por un error. Revisa los mensajes anteriores.
pause
exit /b 1
