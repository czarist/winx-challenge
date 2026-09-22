<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class ApiExceptionRenderer
{
    /**
     * Builds the JSON envelope for every exception raised inside the API,
     * so consumers always get the same {message, errors} shape regardless
     * of what failed.
     */
    public function render(Throwable $exception, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*') && ! $request->expectsJson()) {
            return null;
        }

        return match (true) {
            $exception instanceof ValidationException => $this->validation($exception),
            $exception instanceof TokenExpiredException => $this->error('Token expirado.', 401),
            $exception instanceof TokenInvalidException => $this->error('Token inválido.', 401),
            $exception instanceof JWTException => $this->error('Token não informado.', 401),
            $exception instanceof AuthenticationException => $this->error('Não autenticado.', 401),
            $exception instanceof AuthorizationException => $this->error('Ação não autorizada.', 403),
            $exception instanceof ModelNotFoundException => $this->error('Recurso não encontrado.', 404),
            $exception instanceof NotFoundHttpException => $this->error('Recurso não encontrado.', 404),
            $exception instanceof MethodNotAllowedHttpException => $this->error('Método HTTP não permitido para esta rota.', 405),
            $exception instanceof TooManyRequestsHttpException => $this->error('Muitas requisições. Tente novamente em instantes.', 429),
            $exception instanceof HttpExceptionInterface => $this->error(
                $exception->getMessage() ?: 'Erro ao processar a requisição.',
                $exception->getStatusCode(),
            ),
            default => $this->unexpected($exception),
        };
    }

    private function validation(ValidationException $exception): JsonResponse
    {
        return $this->error('Os dados informados são inválidos.', $exception->status, $exception->errors());
    }

    private function unexpected(Throwable $exception): JsonResponse
    {
        $message = config('app.debug')
            ? $exception->getMessage()
            : 'Erro interno do servidor.';

        return $this->error($message, 500);
    }

    /**
     * @param  array<string, array<int, string>>|null  $errors
     */
    private function error(string $message, int $status, ?array $errors = null): JsonResponse
    {
        return response()->json(array_filter([
            'message' => $message,
            'errors' => $errors,
        ], fn ($value) => $value !== null), $status);
    }
}
