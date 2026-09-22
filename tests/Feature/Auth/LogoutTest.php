<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Facades\JWTAuth;

class LogoutTest extends TestCase
{
    public function test_an_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = Auth::guard('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertNoContent();
    }

    public function test_the_token_is_blacklisted_after_logout(): void
    {
        $user = User::factory()->create();
        $token = Auth::guard('api')->login($user);

        $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/auth/logout');

        $this->expectException(TokenBlacklistedException::class);
        JWTAuth::setToken($token)->checkOrFail();
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertUnauthorized();
    }
}
