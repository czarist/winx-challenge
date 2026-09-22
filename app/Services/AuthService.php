<?php
namespace App\Services;

use App\DataTransferObjects\AuthResult;
use App\Models\User;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\JWTGuard;

class AuthService implements AuthServiceInterface
{
    public function register(array $data): AuthResult
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
        ]);

        $token = $this->guard()->login($user);

        return new AuthResult($user, $token);
    }

    public function login(array $credentials): AuthResult
    {
        if (! $token = $this->guard()->attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais informadas não conferem.'],
            ]);
        }

        /** @var User $user */
        $user = $this->guard()->user();

        return new AuthResult($user, $token);
    }

    public function logout(): void
    {
        $this->guard()->logout();
    }

    public function refresh(): AuthResult
    {
        $token = $this->guard()->refresh();

        /** @var User $user */
        $user = $this->guard()->setToken($token)->user();

        return new AuthResult($user, $token);
    }

    private function guard(): JWTGuard
    {
        /** @var JWTGuard */
        return Auth::guard('api');
    }
}
