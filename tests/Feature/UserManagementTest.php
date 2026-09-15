<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'new.agent@example.com', 'role' => 'agent']);
    }

    public function test_agent_cannot_access_user_management(): void
    {
        $agent = User::factory()->create();

        $response = $this->actingAs($agent)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $admin));

        $response->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
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
        $this->assertSame('admin', $agent->fresh()->role->value);
    }
}
