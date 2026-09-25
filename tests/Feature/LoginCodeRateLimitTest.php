<?php

namespace Tests\Feature;

use App\Mail\LoginVerificationCodeMail;
use App\Models\LoginVerificationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginCodeRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Cache::flush();
        config(['vehicle.login_code_cooldown_seconds' => 60]);
    }

    private function request(string $email, string $ip = '10.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->post(route('login'), ['email' => $email]);
    }

    public function test_first_request_sends_a_code(): void
    {
        User::factory()->create(['email' => 'a@example.com']);

        $this->request('a@example.com')->assertRedirect(route('login.verify'))->assertSessionHasNoErrors();

        Mail::assertSentCount(1);
    }

    public function test_rapid_repeated_requests_send_only_one_email_and_show_remaining_time(): void
    {
        User::factory()->create(['email' => 'a@example.com']);

        $this->request('a@example.com');
        foreach (range(1, 5) as $_) {
            $this->request('a@example.com')
                ->assertRedirect(route('login.verify'))
                ->assertSessionHasErrors('email');
        }

        Mail::assertSentCount(1);
        $this->assertSame(1, LoginVerificationCode::count());
        $this->assertStringContainsString('segundo(s)', session('errors')->first('email'));
    }

    public function test_cooldown_cannot_be_bypassed_from_another_ip_or_session(): void
    {
        User::factory()->create(['email' => 'a@example.com']);

        $this->request('a@example.com', '10.0.0.1');
        $this->flushSession();
        $this->request('A@Example.com', '10.0.0.2')->assertSessionHasErrors('email');

        Mail::assertSentCount(1);
    }

    public function test_blocked_requests_do_not_consume_the_request_limit_or_invalidate_the_code(): void
    {
        User::factory()->create(['email' => 'a@example.com']);

        $this->request('a@example.com');
        $code = LoginVerificationCode::activeFor('a@example.com');

        foreach (range(1, 5) as $_) {
            $this->request('a@example.com');
        }

        $this->assertSame($code->id, LoginVerificationCode::activeFor('a@example.com')->id);
        $this->assertSame(1, RateLimiter::attempts('login-code:10.0.0.1|a@example.com'));
    }

    public function test_unknown_email_gets_the_same_cooldown_response(): void
    {
        $this->request('ghost@example.com');
        $this->request('ghost@example.com')
            ->assertRedirect(route('login.verify'))
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    public function test_different_emails_are_independent(): void
    {
        User::factory()->create(['email' => 'a@example.com']);
        User::factory()->create(['email' => 'b@example.com']);

        $this->request('a@example.com');
        $this->request('b@example.com')->assertSessionHasNoErrors();

        Mail::assertSentCount(2);
    }

    public function test_another_code_can_be_requested_after_the_cooldown(): void
    {
        User::factory()->create(['email' => 'a@example.com']);

        $this->request('a@example.com');
        $this->travel(61)->seconds();
        $this->request('a@example.com')->assertSessionHasNoErrors();

        Mail::assertSentCount(2);
        $this->assertNotNull(LoginVerificationCode::activeFor('a@example.com'));
    }

    public function test_verification_and_expiry_still_work(): void
    {
        $user = User::factory()->create(['email' => 'a@example.com']);

        $this->request('a@example.com');
        [, $plain] = LoginVerificationCode::generateFor('a@example.com');

        $this->post(route('login.verify'), ['code' => $plain])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->post(route('logout'));

        $this->travel(61)->seconds();
        $this->request('a@example.com');
        [, $plain] = LoginVerificationCode::generateFor('a@example.com');
        $this->travel(LoginVerificationCode::TTL_MINUTES + 1)->minutes();

        $this->post(route('login.verify'), ['code' => $plain])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_cooldown_can_be_disabled_by_config(): void
    {
        config(['vehicle.login_code_cooldown_seconds' => 0]);
        User::factory()->create(['email' => 'a@example.com']);

        $this->request('a@example.com');
        $this->request('a@example.com')->assertSessionHasNoErrors();

        Mail::assertSentCount(2);
    }
}
