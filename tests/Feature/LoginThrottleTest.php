<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_429_after_five_failed_attempts_for_the_same_email(): void
    {
        $user = User::factory()->patient()->create(['email' => 'victim@example.com']);

        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'wrong-password'])
                ->assertStatus(302);
        }

        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertStatus(429)
            ->assertSee('عدد المحاولات تجاوز الحد المسموح');

        // حتى كلمة السر الصحيحة تنرفض لحد ما تخلص الدقيقة
        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(429);
        $this->assertGuest();

        $this->travel(61)->seconds();

        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_is_limited_per_ip_across_many_emails(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->post(route('login.submit'), ['email' => "user{$i}@example.com", 'password' => 'x'])
                ->assertStatus(302);
        }

        $this->post(route('login.submit'), ['email' => 'user21@example.com', 'password' => 'x'])
            ->assertStatus(429);
    }
}
