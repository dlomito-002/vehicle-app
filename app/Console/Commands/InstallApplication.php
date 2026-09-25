<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\EnvFileEditor;
use App\Support\InstallRequirements;
use Database\Seeders\InitialUsersSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Guided, non-destructive installer. It never drops or truncates anything:
 * on a fresh database it creates the schema (existing migrations) plus the
 * initial users; on an existing installation it only applies pending
 * migrations and adds missing initial users.
 */
class InstallApplication extends Command
{
    protected $signature = 'app:install
        {--app-url= : URL pública de la aplicación}
        {--db= : Motor de base de datos (sqlite o mysql)}
        {--db-host= : Host de MySQL}
        {--db-port= : Puerto de MySQL}
        {--db-name= : Nombre de la base de datos (MySQL)}
        {--db-user= : Usuario de MySQL (la contraseña se pide de forma interactiva o se lee de DB_PASSWORD en .env)}
        {--admin-name= : Nombre del administrador inicial}
        {--admin-email= : Correo del administrador inicial}
        {--no-team-users : No crear los usuarios del equipo definidos en InitialUsersSeeder}
        {--optimize : Cachear config, rutas y vistas (recomendado en producción)}';

    protected $description = 'Instala la aplicación: entorno, base de datos limpia, usuarios iniciales y almacenamiento';

    /** Tables that hold system/authentication data; everything else is operational and must stay empty. */
    private const SYSTEM_TABLES = [
        'migrations', 'users', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks',
        'jobs', 'job_batches', 'failed_jobs', 'login_verification_codes',
    ];

    private ?string $dbPassword = null;

    public function handle(): int
    {
        $this->components->info('Instalador de Control de Vehículos');

        if (! $this->checkRequirements()) {
            return self::FAILURE;
        }

        $env = new EnvFileEditor($this->laravel->environmentFilePath());

        try {
            $this->prepareEnvironment($env);

            if (! $this->configureDatabase($env)) {
                return self::FAILURE;
            }

            $alreadyInstalled = Schema::hasTable('migrations') && DB::table('migrations')->exists();

            if ($alreadyInstalled) {
                $this->components->warn('Se detectó una instalación existente: solo se aplicarán migraciones pendientes y se agregarán usuarios faltantes. No se borrará ningún dato.');
            }

            $this->components->task('Ejecutando migraciones', fn () => Artisan::call('migrate', ['--force' => true]) === 0);

            if (! $this->seedUsers()) {
                return self::FAILURE;
            }

            $this->setUpStorage();

            if ($this->option('optimize')) {
                $this->components->task('Cacheando configuración, rutas y vistas', function () {
                    Artisan::call('config:cache');
                    Artisan::call('route:cache');
                    Artisan::call('view:cache');
                });
            }
        } catch (Throwable $e) {
            $this->components->error('La instalación falló: '.$this->redact($e->getMessage()));

            return self::FAILURE;
        }

        $this->summary($alreadyInstalled);

        return self::SUCCESS;
    }

    private function checkRequirements(): bool
    {
        $ok = true;

        foreach ((new InstallRequirements)->checks() as [$label, $passed, $blocking, $hint]) {
            if ($passed) {
                $this->components->twoColumnDetail($label, '<fg=green>OK</>');
            } elseif ($blocking) {
                $ok = false;
                $this->components->twoColumnDetail($label, '<fg=red>FALLA</>');
                $this->line("    $hint");
            } else {
                $this->components->twoColumnDetail($label, '<fg=yellow>AVISO</>');
                $this->line("    $hint");
            }
        }

        if (! $ok) {
            $this->components->error('Corrige los requisitos marcados como FALLA y vuelve a ejecutar el instalador.');
        }

        return $ok;
    }

    private function prepareEnvironment(EnvFileEditor $env): void
    {
        if (! $env->exists()) {
            copy(base_path('.env.example'), $this->laravel->environmentFilePath());
            $this->components->info('Se creó .env a partir de .env.example.');
        }

        if (! $env->get('APP_KEY')) {
            $key = 'base64:'.base64_encode(random_bytes(32));
            $env->set('APP_KEY', $key);
            config(['app.key' => $key]);
            $this->components->info('Se generó APP_KEY.');
        }

        $url = $this->option('app-url') ?: text('URL de la aplicación', default: $env->get('APP_URL') ?: 'http://localhost', required: true);
        $env->set('APP_URL', rtrim($url, '/'));
    }

    private function configureDatabase(EnvFileEditor $env): bool
    {
        $driver = $this->option('db') ?: select('Motor de base de datos', ['mysql' => 'MySQL / MariaDB', 'sqlite' => 'SQLite'], default: $env->get('DB_CONNECTION') === 'sqlite' ? 'sqlite' : 'mysql');

        if (! in_array($driver, ['sqlite', 'mysql'], true)) {
            $this->components->error('Motor de base de datos no soportado: use sqlite o mysql.');

            return false;
        }

        $env->set('DB_CONNECTION', $driver);
        $settings = ['database.default' => $driver];

        if ($driver === 'mysql') {
            $host = $this->option('db-host') ?: text('Host', default: $env->get('DB_HOST') ?: '127.0.0.1', required: true);
            $port = $this->option('db-port') ?: text('Puerto', default: $env->get('DB_PORT') ?: '3306', required: true);
            $name = $this->option('db-name') ?: text('Nombre de la base de datos (debe existir)', default: $env->get('DB_DATABASE') ?: '', required: true);
            $user = $this->option('db-user') ?: text('Usuario', default: $env->get('DB_USERNAME') ?: '', required: true);
            $this->dbPassword = $this->input->isInteractive()
                ? password('Contraseña (vacía para conservar la de .env)')
                : '';
            $this->dbPassword = $this->dbPassword !== '' ? $this->dbPassword : ($env->get('DB_PASSWORD') ?? '');

            foreach (['DB_HOST' => $host, 'DB_PORT' => $port, 'DB_DATABASE' => $name, 'DB_USERNAME' => $user, 'DB_PASSWORD' => $this->dbPassword] as $k => $v) {
                $env->set($k, $v);
            }

            $settings += [
                'database.connections.mysql.host' => $host,
                'database.connections.mysql.port' => $port,
                'database.connections.mysql.database' => $name,
                'database.connections.mysql.username' => $user,
                'database.connections.mysql.password' => $this->dbPassword,
            ];
        } else {
            $path = $env->get('DB_DATABASE') ?: config('database.connections.sqlite.database');

            if ($path !== ':memory:' && ! is_file($path)) {
                touch($path);
                $this->components->info('Se creó el archivo de base de datos SQLite.');
            }

            $settings['database.connections.sqlite.database'] = $path;
        }

        // The .env may have just been created, so point this process at the chosen database.
        config($settings);
        if (($settings['database.connections.sqlite.database'] ?? null) !== ':memory:') {
            DB::purge();
        }

        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $this->components->error('No se pudo conectar a la base de datos: '.$this->redact($e->getMessage()));

            return false;
        }

        $this->components->twoColumnDetail('Conexión a la base de datos', '<fg=green>OK</>');

        return true;
    }

    private function seedUsers(): bool
    {
        config([
            'install.admin_name' => $this->option('admin-name') ?: config('install.admin_name'),
            'install.admin_email' => $this->option('admin-email') ?: config('install.admin_email'),
            'install.seed_team' => $this->option('no-team-users') ? false : config('install.seed_team'),
        ]);

        if (! config('install.admin_email') && ! User::where('role', UserRole::Admin)->exists() && ! config('install.seed_team')) {
            $email = text('Correo del administrador inicial', required: true, validate: fn ($v) => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Correo inválido.');
            config(['install.admin_email' => $email, 'install.admin_name' => text('Nombre del administrador', default: (string) config('install.admin_name'), required: true)]);
        }

        if (config('install.admin_email') && ! filter_var(config('install.admin_email'), FILTER_VALIDATE_EMAIL)) {
            $this->components->error('El correo del administrador no es válido.');

            return false;
        }

        $this->components->task('Creando usuarios iniciales', fn () => Artisan::call('db:seed', ['--class' => InitialUsersSeeder::class, '--force' => true]) === 0);

        if (! User::where('role', UserRole::Admin)->exists()) {
            $this->components->error('No existe ningún administrador. Ejecuta de nuevo con --admin-email=correo@dominio.com.');

            return false;
        }

        return true;
    }

    private function setUpStorage(): void
    {
        if (is_link(public_path('storage')) || file_exists(public_path('storage'))) {
            $this->components->twoColumnDetail('Enlace public/storage', '<fg=green>ya existe</>');

            return;
        }

        try {
            Artisan::call('storage:link');
            $this->components->twoColumnDetail('Enlace public/storage', '<fg=green>creado</>');
        } catch (Throwable) {
            $this->components->warn('No se pudo crear public/storage (en Windows requiere privilegios); ejecuta "php artisan storage:link" manualmente.');
        }
    }

    private function summary(bool $alreadyInstalled): void
    {
        $this->newLine();
        $this->components->info($alreadyInstalled ? 'Instalación existente actualizada.' : 'Instalación completada.');

        $tables = collect(Schema::getTableListing(schemaQualified: false))->reject(fn ($t) => in_array($t, self::SYSTEM_TABLES, true));
        $nonEmpty = $tables->filter(fn ($t) => DB::table($t)->exists());

        $this->components->twoColumnDetail('Base de datos', config('database.default'));
        $this->components->twoColumnDetail('Migraciones', 'al día');
        $this->components->twoColumnDetail('Usuarios (administradores)', User::count().' ('.User::where('role', UserRole::Admin)->count().')');
        $this->components->twoColumnDetail('Tablas operativas', $tables->count().' — con datos: '.($nonEmpty->isEmpty() ? 'ninguna' : $nonEmpty->implode(', ')));
        $this->components->twoColumnDetail('Almacenamiento', 'disco de fotos: '.config('vehicle.photos_disk'));

        $this->newLine();
        $this->line('Siguientes pasos:');

        if (config('mail.default') === 'log') {
            $this->line('  - MAIL_MAILER=log: los códigos de acceso NO se envían por correo. Configura SMTP en .env (MAIL_*) para que los usuarios puedan iniciar sesión.');
        }

        if (config('vehicle.photos_disk') === 'cloudinary' && str_starts_with((string) config('cloudinary.cloud_url'), 'cloudinary://:@')) {
            $this->line('  - VEHICLE_PHOTOS_DISK=cloudinary requiere CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY y CLOUDINARY_API_SECRET.');
        }

        if (config('app.env') === 'production' && config('app.debug')) {
            $this->line('  - Define APP_DEBUG=false en .env.');
        }

        $this->line('  - Apunta el servidor web a la carpeta public/ y agrega los vehículos desde la administración.');
        $this->line('  - Opcional (cron): * * * * * php artisan maintenance:check-alerts  (o cada hora) para alertas de mantenimiento.');
    }

    private function redact(string $message): string
    {
        if ($this->dbPassword) {
            $message = str_replace($this->dbPassword, '******', $message);
        }

        return Str::limit($message, 300);
    }
}
