<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+263771234567',
            'user_type' => 'resident',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertTrue(User::where('email', 'test@example.com')->first()->hasRole('resident'));
    }

    public function test_registration_with_account_number_creates_pending_link(): void
    {
        $account = $this->makePropertyWithAccount();

        $this->post('/register', [
            'name' => 'Link Test', 'email' => 'link@example.com', 'phone' => '+263771111111',
            'user_type' => 'property_owner', 'account_number' => $account->account_number,
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);

        $user = User::where('email', 'link@example.com')->firstOrFail();
        $this->assertDatabaseHas('property_users', [
            'user_id' => $user->id, 'property_id' => $account->property_id, 'status' => 'pending',
        ]);
    }

    public function test_weak_password_rejected(): void
    {
        $this->post('/register', [
            'name' => 'T', 'email' => 'weak@example.com', 'phone' => '+263771',
            'user_type' => 'resident', 'password' => 'password', 'password_confirmation' => 'password',
        ])->assertSessionHasErrors('password');
    }

    public function test_suspended_user_cannot_login(): void
    {
        $user = $this->makeUser('resident', ['status' => 'suspended', 'email_verified_at' => now()]);

        $this->post('/login', ['email' => $user->email, 'password' => 'Password123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_throttled_and_lockout_recorded(): void
    {
        $user = $this->makeUser('resident', ['email_verified_at' => now()]);

        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $user->refresh();
        $this->assertGreaterThanOrEqual(5, $user->failed_login_attempts);

        // Correct credentials are now rejected by the rate limiter.
        $this->post('/login', ['email' => $user->email, 'password' => 'Password123']);
        $this->assertGuest();
    }

    public function test_locked_user_cannot_login_even_with_correct_password(): void
    {
        $user = $this->makeUser('resident', [
            'email_verified_at' => now(), 'locked_until' => now()->addMinutes(10),
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'Password123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
