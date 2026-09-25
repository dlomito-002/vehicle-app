<?php

namespace App\Support;

/**
 * Environment checks run by `php artisan app:install`. Each check returns
 * [label, passed, blocking, hint] so the command can render and decide.
 */
class InstallRequirements
{
    public const MINIMUM_PHP = '8.2.0';

    private const EXTENSIONS = ['ctype', 'curl', 'dom', 'fileinfo', 'gd', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml'];

    /** @return list<array{0: string, 1: bool, 2: bool, 3: string}> */
    public function checks(): array
    {
        $checks = [[
            'PHP >= '.self::MINIMUM_PHP.' (actual '.PHP_VERSION.')',
            version_compare(PHP_VERSION, self::MINIMUM_PHP, '>='),
            true,
            'Instala PHP 8.2 o superior.',
        ]];

        foreach (self::EXTENSIONS as $extension) {
            $checks[] = ["Extensión PHP {$extension}", extension_loaded($extension), true, "Habilita la extensión {$extension} en php.ini."];
        }

        $checks[] = [
            'Extensión PDO para SQLite o MySQL',
            extension_loaded('pdo_sqlite') || extension_loaded('pdo_mysql'),
            true,
            'Habilita pdo_sqlite o pdo_mysql.',
        ];

        foreach ([storage_path(), storage_path('app'), storage_path('framework'), storage_path('logs'), base_path('bootstrap/cache')] as $dir) {
            $relative = ltrim(str_replace(base_path(), '', $dir), '/\\');
            $checks[] = ["Escritura en {$relative}", is_dir($dir) && is_writable($dir), true, "Da permisos de escritura al usuario del servidor web sobre {$relative}."];
        }

        $checks[] = [
            'Assets compilados (public/build)',
            is_file(public_path('build/manifest.json')),
            false,
            'Ejecuta "npm ci && npm run build" antes de usar la aplicación.',
        ];

        return $checks;
    }
}
