<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['email' => 'root@example.com', 'password' => 'secret123']);
    }

    public function test_login_returns_bearer_token(): void
    {
        $this->user();

        $this->postJson('/api/v1/auth/login', ['email' => 'root@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonStructure(['access_token'])
            ->assertJsonPath('token_type', 'Bearer');
    }

    public function test_login_replaces_previous_token(): void
    {
        $user = $this->user();
        $credentials = ['email' => 'root@example.com', 'password' => 'secret123'];

        $this->postJson('/api/v1/auth/login', $credentials)->assertOk();
        $this->postJson('/api/v1/auth/login', $credentials)->assertOk();

        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_login_rejects_wrong_password(): void
    {
        $this->user();

        $this->postJson('/api/v1/auth/login', ['email' => 'root@example.com', 'password' => 'wrong'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid credentials');
    }

    public function test_login_validates_payload(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_is_rate_limited_per_email(): void
    {
        $this->user();
        $credentials = ['email' => 'root@example.com', 'password' => 'wrong'];

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/auth/login', $credentials)->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', $credentials)->assertTooManyRequests();
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = $this->user();
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_logout_requires_token(): void
    {
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
    }
}
