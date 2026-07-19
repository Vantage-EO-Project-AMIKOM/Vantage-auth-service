<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('The pdo_sqlite extension is required for database tests.');
        }

        parent::setUp();
    }

    public function test_a_public_account_registers_as_a_universal_user(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Vantage User',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.role', 'user')
            ->assertJsonStructure(['token', 'expires_at', 'user' => ['id', 'name', 'email', 'role']]);

        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'role' => 'user',
        ]);
    }

    public function test_login_returns_a_token_and_user(): void
    {
        User::create([
            'name' => 'Vantage User',
            'email' => 'user@example.com',
            'password' => 'password123',
            'role' => 'user',
        ]);

        $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ])->assertOk()->assertJsonPath('user.role', 'user')->assertJsonStructure(['token', 'expires_at']);
    }

    public function test_login_revokes_the_users_previous_token(): void
    {
        $user = User::create([
            'name' => 'Vantage User',
            'email' => 'user@example.com',
            'password' => 'password123',
            'role' => 'user',
        ]);
        $oldToken = $user->createToken('old-token')->plainTextToken;

        $newToken = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ])->assertOk()->json('token');

        $this->withToken($oldToken)->getJson('/api/me')->assertUnauthorized();
        $this->withToken($newToken)->getJson('/api/me')->assertOk();
        $this->assertCount(1, $user->fresh()->tokens);
    }
}
