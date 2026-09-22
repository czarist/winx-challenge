<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_a_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create(['email' => 'maria@example.com', 'password' => 'senha12345']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'maria@example.com',
            'password' => 'senha12345',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'access_token', 'token_type', 'expires_in'])
            ->assertJsonPath('token_type', 'bearer');
    }

    public function test_login_fails_with_an_incorrect_password(): void
    {
        User::factory()->create(['email' => 'maria@example.com', 'password' => 'senha12345']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'maria@example.com',
            'password' => 'senha-errada',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_login_fails_for_an_unknown_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'ninguem@example.com',
            'password' => 'senha12345',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_login_fails_without_required_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    }
}
