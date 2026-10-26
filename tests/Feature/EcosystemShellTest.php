<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\Ecosystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Ecosistema Carrousel: shared shell, Administración screens and audit trail. */
class EcosystemShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_has_the_ecosystem_shell(): void
    {
        $agent = User::factory()->create();

        $this->actingAs($agent)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-sidebar-toggle', false)
            ->assertSee('class="sidebar-version"', false)
            ->assertSee('Ecosistema v'.Ecosystem::ecosystemVersion())
            ->assertSee('data-notifications', false)
            ->assertSee('class="help-fab"', false)
            ->assertSee('portal-btn', false)
            ->assertSee('assets/css/ecosystem.css', false)
            ->assertSee('assets/js/ecosystem.js', false);
    }

    public function test_agents_do_not_see_or_open_admin_screens(): void
    {
        $agent = User::factory()->create();

        $this->actingAs($agent)->get(route('dashboard'))->assertDontSee(route('admin.audit.index'));
        $this->actingAs($agent)->get(route('admin.audit.index'))->assertForbidden();
        $this->actingAs($agent)->get(route('admin.mail.index'))->assertForbidden();
        $this->actingAs($agent)->post(route('admin.mail.test'), ['to' => 'x@example.com'])->assertForbidden();
    }

    public function test_admin_sees_audit_and_mail_screens(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertSee(route('admin.audit.index'))
            ->assertSee(route('admin.mail.index'));
        $this->actingAs($admin)->get(route('admin.audit.index'))->assertOk()->assertSee('Registro');
        $this->actingAs($admin)->get(route('admin.mail.index'))->assertOk()->assertSee('Estado del canal');
    }

    public function test_changes_to_records_are_audited_without_sensitive_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $vehicle = Vehicle::factory()->create();
        $vehicle->update(['make' => 'Marca QA']);

        $this->assertDatabaseHas('audit_logs', ['action' => 'created', 'subject_type' => 'Vehículo', 'subject_id' => (string) $vehicle->id]);
        $updated = AuditLog::where('action', 'updated')->where('subject_type', 'Vehículo')->first();
        $this->assertSame('Marca QA', $updated->details['despues']['make']);
        $this->assertSame($admin->email, $updated->actor);

        $userLog = AuditLog::where('subject_type', 'Usuario')->where('action', 'created')->first();
        $this->assertArrayNotHasKey('password', $userLog->details ?? []);
        $this->assertArrayNotHasKey('remember_token', $userLog->details ?? []);
    }

    public function test_admin_can_send_a_test_email_and_it_is_audited(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->from(route('admin.mail.index'))
            ->post(route('admin.mail.test'), ['to' => 'prueba@example.com'])
            ->assertRedirect(route('admin.mail.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('audit_logs', ['action' => 'mail_test', 'subject_id' => 'prueba@example.com']);
    }

    public function test_login_screen_uses_the_shared_access_layout(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('auth-body-v2', false)
            ->assertSee('Acceso seguro')
            ->assertSee('Te enviaremos un código temporal de seis dígitos a tu correo.')
            ->assertSee('Enviar código');
    }
}
