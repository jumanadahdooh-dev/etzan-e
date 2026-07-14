<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->post(route('register.submit'), [
            'name' => 'محمد أحمد',
            'email' => 'mohammad@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'agree_terms' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'mohammad@example.com', 'role' => 'patient']);
        $this->assertDatabaseHas('patient_profiles', [
            'user_id' => User::where('email', 'mohammad@example.com')->first()->id,
        ]);
    }

    public function test_registration_rejects_duplicate_email_with_clear_message(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->post(route('register.submit'), [
            'name' => 'اسم آخر',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'agree_terms' => '1',
        ]);

        $response->assertSessionHasErrors('email');
        $errors = session('errors')->getBag('default');
        $this->assertStringContainsString('مستخدم بالفعل', $errors->first('email'));
    }

    public function test_registration_rejects_short_password(): void
    {
        $response = $this->post(route('register.submit'), [
            'name' => 'اسم',
            'email' => 'newuser@example.com',
            'password' => '123',
            'password_confirmation' => '123',
            'agree_terms' => '1',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'newuser@example.com']);
    }

    public function test_registration_requires_accepting_terms(): void
    {
        $response = $this->post(route('register.submit'), [
            'name' => 'اسم',
            'email' => 'newuser2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('agree_terms');
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->patient()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login.submit'), [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_missing_password(): void
    {
        $response = $this->post(route('login.submit'), [
            'email' => 'someone@example.com',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_login_rejects_wrong_credentials(): void
    {
        User::factory()->patient()->create([
            'email' => 'real@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login.submit'), [
            'email' => 'real@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
