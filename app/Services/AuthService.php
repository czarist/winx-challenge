<?php

namespace App\Services;

use App\DataTransferObjects\AuthResult;
use App\Models\User;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService implements AuthServiceInterface
{
    public function register(array $data): AuthResult
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $token = Auth::guard('api')->login($user);

        return new AuthResult($user, $token);
    }

    public function login(array $credentials): AuthResult
    {
        if (! $token = Auth::guard('api')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais informadas não conferem.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::guard('api')->user();

        return new AuthResult($user, $token);
    }

    public function logout(): void
    {
        Auth::guard('api')->logout();
    }

    public function refresh(): AuthResult
    {
        $token = Auth::guard('api')->refresh();

        /** @var User $user */
        $user = Auth::guard('api')->setToken($token)->user();

        return new AuthResult($user, $token);
    }
}
