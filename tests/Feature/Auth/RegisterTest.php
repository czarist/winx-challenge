<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    public function test_a_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'access_token', 'token_type', 'expires_in'])
            ->assertJsonPath('user.email', 'maria@example.com');

        $this->assertDatabaseHas('users', ['email' => 'maria@example.com']);
    }

    public function test_registering_hashes_the_password(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ]);

        $user = User::whereEmail('maria@example.com')->firstOrFail();

        $this->assertNotEquals('senha12345', $user->password);
        $this->assertTrue(password_verify('senha12345', $user->password));
    }

    public function test_registration_fails_with_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'maria@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_registration_fails_when_password_confirmation_does_not_match(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'senha12345',
            'password_confirmation' => 'outrasenha',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_registration_fails_without_required_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
    }
}
