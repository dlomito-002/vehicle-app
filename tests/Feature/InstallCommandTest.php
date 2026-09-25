<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\EnvFileEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $envPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->envPath = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($this->envPath, "APP_KEY=base64:existing\nAPP_URL=http://old\n");
        $this->app->useEnvironmentPath(dirname($this->envPath));
        $this->app->loadEnvironmentFrom(basename($this->envPath));
    }

    protected function tearDown(): void
    {
        @unlink($this->envPath);
        parent::tearDown();
    }

    private function install(array $options = [])
    {
        return $this->artisan('app:install', $options + [
            '--db' => 'sqlite',
            '--app-url' => 'http://example.test',
            '--no-interaction' => true,
        ]);
    }

    public function test_install_creates_initial_users_and_leaves_operational_tables_empty(): void
    {
        $this->install(['--admin-email' => 'Boss@Example.com', '--admin-name' => 'Boss'])->assertSuccessful();

        $admin = User::where('email', 'boss@example.com')->first();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertGreaterThan(1, User::count());

        foreach (['vehicles', 'vehicle_receptions', 'vehicle_deliveries', 'vehicle_photos', 'vehicle_documentations',
            'vehicle_equipment_checks', 'vehicle_condition_items', 'vehicle_services',
            'vehicle_maintenance_schedules', 'vehicle_maintenance_completions'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_rerunning_does_not_duplicate_or_modify_users_or_destroy_data(): void
    {
        $this->install(['--admin-email' => 'boss@example.com'])->assertSuccessful();

        User::where('email', 'boss@example.com')->update(['name' => 'Renamed', 'role' => UserRole::Agent]);
        $count = User::count();

        $this->install(['--admin-email' => 'boss@example.com'])->assertSuccessful();

        $this->assertSame($count, User::count());
        $this->assertSame('Renamed', User::where('email', 'boss@example.com')->value('name'));
    }

    public function test_initial_users_have_no_known_password(): void
    {
        $this->install(['--admin-email' => 'boss@example.com'])->assertSuccessful();

        foreach (User::all() as $user) {
            $this->assertFalse(password_verify('password', $user->getRawOriginal('password')));
        }
    }

    public function test_fails_without_any_admin(): void
    {
        $this->install(['--no-team-users' => true])->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_env_editor_updates_uncomments_and_appends(): void
    {
        file_put_contents($this->envPath, "A=1\n# B=2\n");
        $env = new EnvFileEditor($this->envPath);

        $env->set('A', 'x y');
        $env->set('B', 'p$1');
        $env->set('C', 'z');

        $this->assertSame('p$1', $env->get('B'));
        $this->assertSame('x y', $env->get('A'));
        $this->assertSame("A=\"x y\"\nB=\"p\\\$1\"\nC=z\n", file_get_contents($this->envPath));
    }
}
