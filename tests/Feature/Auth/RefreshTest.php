<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Facades\JWTAuth;

class RefreshTest extends TestCase
{
    public function test_a_valid_token_can_be_refreshed(): void
    {
        $user = User::factory()->create();
        $token = Auth::guard('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/refresh');

        $response->assertOk()->assertJsonStructure(['user', 'access_token', 'token_type', 'expires_in']);
        $this->assertNotSame($token, $response->json('access_token'));
    }

    public function test_the_previous_token_is_blacklisted_after_refresh(): void
    {
        $user = User::factory()->create();
        $token = Auth::guard('api')->login($user);

        $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/auth/refresh');

        $this->expectException(TokenBlacklistedException::class);
        JWTAuth::setToken($token)->checkOrFail();
    }

    public function test_refresh_requires_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh');

        $response->assertUnauthorized();
    }
}
