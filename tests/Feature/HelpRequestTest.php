<?php

namespace Tests\Feature;

use App\Mail\HelpRequestMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class HelpRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_submit_a_help_request(): void
    {
        $response = $this->post(route('help.store'), ['message' => 'Algo no funciona.']);

        $response->assertRedirect(route('login'));
    }

    public function test_message_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('help.store'), ['message' => '']);

        $response->assertSessionHasErrors('message');
    }

    public function test_submitting_help_sends_mail_to_every_selected_user(): void
    {
        Mail::fake();
        config(['vehicle.manager_email' => 'gerente@example.com']);

        User::factory()->create(['email' => 'uno@example.com', 'receives_notification_emails' => true]);
        User::factory()->create(['email' => 'dos@example.com', 'receives_notification_emails' => true]);
        User::factory()->create(['email' => 'no@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('help.store'), ['message' => 'El vehículo no enciende.'])
            ->assertRedirect(route('help.create'));

        Mail::assertSentCount(1);
        Mail::assertSent(HelpRequestMail::class, function (HelpRequestMail $mail) {
            // Selected users replace the .env fallback instead of adding to it.
            return count($mail->to) === 2
                && $mail->hasTo('uno@example.com')
                && $mail->hasTo('dos@example.com')
                && ! $mail->hasTo('no@example.com')
                && ! $mail->hasTo('gerente@example.com');
        });
    }

    public function test_submitting_help_falls_back_to_the_configured_manager_address_when_no_user_is_selected(): void
    {
        Mail::fake();
        config(['vehicle.manager_email' => 'gerente@example.com']);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('help.store'), [
            'message' => 'El vehículo no enciende.',
        ]);

        $response->assertRedirect(route('help.create'));
        $response->assertSessionHas('status');

        Mail::assertSent(HelpRequestMail::class, function (HelpRequestMail $mail) use ($user) {
            return $mail->hasTo('gerente@example.com')
                && $mail->reporter->is($user)
                && $mail->reportMessage === 'El vehículo no enciende.';
        });
    }

    public function test_no_recipients_shows_a_configuration_error_and_does_not_send(): void
    {
        Mail::fake();
        config(['vehicle.manager_email' => null]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('help.store'), [
            'message' => 'El vehículo no enciende.',
        ]);

        $response->assertSessionHasErrors('message');
        Mail::assertNothingSent();
    }
}
