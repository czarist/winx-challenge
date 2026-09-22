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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     tags={"Auth"},
     *     summary="Cria uma conta de usuário e retorna um token de acesso",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *
     *             @OA\Property(property="name", type="string", example="Maria Silva"),
     *             @OA\Property(property="email", type="string", format="email", example="maria@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="senha12345"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="senha12345")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Usuário criado", @OA\JsonContent(ref="#/components/schemas/AuthResponse")),
     *     @OA\Response(response=422, description="Dados inválidos", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->tokenResponse($result, 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     tags={"Auth"},
     *     summary="Autentica um usuário e retorna um token de acesso",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="maria@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="senha12345")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Autenticado com sucesso", @OA\JsonContent(ref="#/components/schemas/AuthResponse")),
     *     @OA\Response(response=422, description="Credenciais inválidas", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->tokenResponse($result, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     tags={"Auth"},
     *     summary="Invalida o token de acesso atual",
     *     security={{"bearerAuth"={}}},
     *
     *     @OA\Response(response=204, description="Logout realizado com sucesso"),
     *     @OA\Response(response=401, description="Não autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json(null, 204);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     tags={"Auth"},
     *     summary="Renova o token de acesso a partir do token atual",
     *     security={{"bearerAuth"={}}},
     *
     *     @OA\Response(response=200, description="Token renovado", @OA\JsonContent(ref="#/components/schemas/AuthResponse")),
     *     @OA\Response(response=401, description="Token inválido ou expirado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function refresh(): JsonResponse
    {
        $result = $this->authService->refresh();

        return $this->tokenResponse($result, 200);
    }

    private function tokenResponse(AuthResult $result, int $status): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($result->user),
            'access_token' => $result->token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ], $status);
    }
}
