<?php
namespace App\Http\Controllers\Api\V1;

use App\DataTransferObjects\AuthResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->tokenResponse($result, 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->tokenResponse($result, 200);
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json(null, 204);
    }

    public function refresh(): JsonResponse
    {
        $result = $this->authService->refresh();

        return $this->tokenResponse($result, 200);
    }

    private function tokenResponse(AuthResult $result, int $status): JsonResponse
    {
        return response()->json([
            'user'         => new UserResource($result->user),
            'access_token' => $result->token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
        ], $status);
    }
}
