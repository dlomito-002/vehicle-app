<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\LoginVerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_user(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New Agent',
            'email' => 'new.agent@example.com',
            'role' => 'agent',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('users', ['email' => 'new.agent@example.com', 'role' => 'agent']);
    }

    public function test_create_and_edit_forms_have_no_password_field(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->create();

        $this->actingAs($admin)->get(route('users.create'))
            ->assertOk()->assertDontSee('name="password', false);
        $this->actingAs($admin)->get(route('users.edit', $agent))
            ->assertOk()->assertDontSee('name="password', false);
    }

    public function test_create_rejects_missing_required_data(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => '',
            'email' => '',
            'role' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'role']);
        $this->assertSame(1, User::count());
    }

    public function test_create_rejects_an_invalid_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Nobody',
            'email' => 'nobody@example.com',
            'role' => 'superadmin',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'nobody@example.com']);
    }

    public function test_create_rejects_a_duplicate_email(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Duplicate',
            'email' => 'taken@example.com',
            'role' => 'agent',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame(1, User::where('email', 'taken@example.com')->count());
    }

    public function test_create_rejects_a_duplicate_email_that_differs_only_in_case(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Duplicate',
            'email' => 'TAKEN@Example.com',
            'role' => 'agent',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame(1, User::whereRaw('lower(email) = ?', ['taken@example.com'])->count());
    }

    public function test_email_is_normalized_so_the_new_user_can_actually_log_in(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Mixed Case',
            'email' => '  Mixed.Case@Example.COM ',
            'role' => 'agent',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'mixed.case@example.com']);

        // Login looks the account up by its lowercased address, so a stored
        // mixed-case email would have made the account unreachable.
        $this->post(route('logout'));

        $this->post(route('login'), ['email' => 'Mixed.Case@Example.com'])
            ->assertRedirect(route('login.verify'))
            ->assertSessionHasNoErrors();

        $plainCode = null;
        Mail::assertSent(LoginVerificationCodeMail::class, function (LoginVerificationCodeMail $mail) use (&$plainCode) {
            $plainCode = $mail->code;

            return true;
        });

        $this->post(route('login.verify'), ['code' => $plainCode])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs(User::where('email', 'mixed.case@example.com')->firstOrFail());
    }

    public function test_edit_form_shows_the_users_current_values(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->create(['name' => 'Ana Agente', 'email' => 'ana@example.com']);

        $response = $this->actingAs($admin)->get(route('users.edit', $agent));

        $response->assertOk();
        $response->assertSee('Ana Agente', false);
        $response->assertSee('ana@example.com', false);
        $response->assertDontSee($agent->password, false);

        $this->actingAs($admin)->get(route('users.edit', $admin))
            ->assertOk()
            ->assertSee('No puedes cambiar tu propio rol.', false)
            ->assertDontSee($admin->password, false);
    }

    public function test_admin_can_update_a_user_without_changing_the_password(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->create(['name' => 'Old Name']);
        $originalPassword = $agent->password;

        $response = $this->actingAs($admin)->put(route('users.update', $agent), [
            'name' => 'New Name',
            'email' => $agent->email,
            'role' => $agent->role->value,
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasNoErrors();

        $agent->refresh();
        $this->assertSame('New Name', $agent->name);
        $this->assertSame($originalPassword, $agent->password);
    }

    public function test_a_user_can_keep_their_own_email_when_edited(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->create(['email' => 'keep@example.com']);

        $response = $this->actingAs($admin)->put(route('users.update', $agent), [
            'name' => 'Renamed',
            'email' => 'keep@example.com',
            'role' => $agent->role->value,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('users.index'));
        $this->assertSame('keep@example.com', $agent->fresh()->email);
    }

    public function test_update_rejects_an_email_already_used_by_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->create(['email' => 'mine@example.com']);
        User::factory()->create(['email' => 'theirs@example.com']);

        $response = $this->actingAs($admin)->put(route('users.update', $agent), [
            'name' => $agent->name,
            'email' => 'THEIRS@example.com',
            'role' => $agent->role->value,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame('mine@example.com', $agent->fresh()->email);
    }

    public function test_admin_can_update_another_users_role(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->create();

        $response = $this->actingAs($admin)->put(route('users.update', $agent), [
            'name' => $agent->name,
            'email' => $agent->email,
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertSame(UserRole::Admin, $agent->fresh()->role);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put(route('users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'agent',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    public function test_admin_can_still_edit_their_own_name_keeping_their_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put(route('users.update', $admin), [
            'name' => 'Renamed Admin',
            'email' => $admin->email,
            'role' => 'admin',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Renamed Admin', $admin->fresh()->name);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $admin));

        $response->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $agent));

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $agent->id]);
    }

    public function test_agent_cannot_access_user_management(): void
    {
        $agent = User::factory()->create();

        $response = $this->actingAs($agent)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_agent_cannot_create_edit_or_delete_users(): void
    {
        $agent = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($agent)->get(route('users.create'))->assertForbidden();

        $this->actingAs($agent)->post(route('users.store'), [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'role' => 'admin',
        ])->assertForbidden();

        $this->actingAs($agent)->get(route('users.edit', $target))->assertForbidden();

        $this->actingAs($agent)->put(route('users.update', $target), [
            'name' => 'Hijacked',
            'email' => $target->email,
            'role' => 'admin',
        ])->assertForbidden();

        $this->actingAs($agent)->delete(route('users.destroy', $target))->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
        $this->assertSame(UserRole::Agent, $target->fresh()->role);
    }

    public function test_admin_can_create_a_user_with_or_without_fleet_desk_emails(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Con correos',
            'email' => 'con@example.com',
            'role' => 'agent',
            'receives_notification_emails' => '1',
        ])->assertRedirect(route('users.index'));

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Sin correos',
            'email' => 'sin@example.com',
            'role' => 'agent',
            'receives_notification_emails' => '0',
        ])->assertRedirect(route('users.index'));

        $this->assertTrue(User::where('email', 'con@example.com')->first()->receives_notification_emails);
        $this->assertFalse(User::where('email', 'sin@example.com')->first()->receives_notification_emails);
    }

    public function test_new_users_do_not_receive_fleet_desk_emails_by_default(): void
    {
        $admin = User::factory()->admin()->create();

        // Unchecked checkbox: the field is not submitted at all.
        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Default',
            'email' => 'default@example.com',
            'role' => 'agent',
        ])->assertRedirect(route('users.index'));

        $this->assertFalse(User::where('email', 'default@example.com')->first()->receives_notification_emails);
        $this->assertFalse($admin->fresh()->receives_notification_emails);
    }

    public function test_admin_can_toggle_a_users_fleet_desk_emails(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->create();

        $payload = ['name' => $agent->name, 'email' => $agent->email, 'role' => 'agent'];

        $this->actingAs($admin)->put(route('users.update', $agent), $payload + ['receives_notification_emails' => '1'])
            ->assertRedirect(route('users.index'));
        $this->assertTrue($agent->fresh()->receives_notification_emails);

        $this->actingAs($admin)->put(route('users.update', $agent), $payload + ['receives_notification_emails' => '0'])
            ->assertRedirect(route('users.index'));
        $this->assertFalse($agent->fresh()->receives_notification_emails);
    }

    public function test_forms_and_list_show_the_fleet_desk_email_setting(): void
    {
        $admin = User::factory()->admin()->create();
        $subscribed = User::factory()->create(['receives_notification_emails' => true]);

        $this->actingAs($admin)->get(route('users.create'))
            ->assertOk()->assertSee('Recibir correos de Fleet Desk')
            ->assertSee('name="receives_notification_emails"', false);

        $this->actingAs($admin)->get(route('users.edit', $subscribed))
            ->assertOk()->assertSee('Recibir correos de Fleet Desk')
            ->assertSee('value="1" checked', false);

        $this->actingAs($admin)->get(route('users.index'))
            ->assertOk()->assertSee('Correos Fleet Desk')
            ->assertSee('Recibe correos')
            ->assertSee('No recibe');
    }

    public function test_agent_cannot_change_fleet_desk_emails_through_a_direct_request(): void
    {
        $agent = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($agent)->put(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'agent',
            'receives_notification_emails' => '1',
        ])->assertForbidden();

        $this->actingAs($agent)->put(route('users.update', $agent), [
            'name' => $agent->name,
            'email' => $agent->email,
            'role' => 'agent',
            'receives_notification_emails' => '1',
        ])->assertForbidden();

        $this->assertFalse($target->fresh()->receives_notification_emails);
        $this->assertFalse($agent->fresh()->receives_notification_emails);
    }

    public function test_guest_cannot_access_user_management(): void
    {
        $target = User::factory()->create();

        $this->get(route('users.index'))->assertRedirect(route('login'));
        $this->get(route('users.create'))->assertRedirect(route('login'));
        $this->get(route('users.edit', $target))->assertRedirect(route('login'));
        $this->post(route('users.store'), [])->assertRedirect(route('login'));
    }
}
