<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ApiExceptionHeadersTest extends TestCase
{
    public function test_method_not_allowed_preserves_the_allow_header_and_json_envelope(): void
    {
        Route::post('/api/test-method', fn () => response()->noContent());

        $this->getJson('/api/test-method')
            ->assertStatus(405)
            ->assertHeader('Allow', 'POST')
            ->assertExactJson(['message' => 'Método HTTP não permitido para esta rota.']);
    }

    public function test_rate_limit_preserves_retry_and_limit_headers_and_json_envelope(): void
    {
        $this->travelTo(now()->startOfSecond());

        Route::get('/api/test-throttle', fn () => response()->noContent())
            ->middleware('throttle:1,1');

        $this->getJson('/api/test-throttle')->assertNoContent();

        $this->getJson('/api/test-throttle')
            ->assertStatus(429)
            ->assertHeader('Retry-After', '60')
            ->assertHeader('X-RateLimit-Limit', '1')
            ->assertHeader('X-RateLimit-Remaining', '0')
            ->assertHeader('X-RateLimit-Reset', (string) now()->addMinute()->timestamp)
            ->assertExactJson(['message' => 'Muitas requisições. Tente novamente em instantes.']);
    }

    public function test_not_found_preserves_exception_headers_and_the_standard_message(): void
    {
        Route::get('/api/test-not-found', fn () => throw new NotFoundHttpException(
            headers: ['Cache-Control' => 'no-store'],
        ));

        $this->getJson('/api/test-not-found')
            ->assertNotFound()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertExactJson(['message' => 'Recurso não encontrado.']);
    }

    public function test_generic_http_exception_preserves_headers_status_and_message(): void
    {
        Route::get('/api/test-unavailable', fn () => throw new HttpException(
            503,
            'Manutenção em andamento.',
            headers: ['Retry-After' => '120', 'X-Service-State' => 'maintenance'],
        ));

        $this->getJson('/api/test-unavailable')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '120')
            ->assertHeader('X-Service-State', 'maintenance')
            ->assertExactJson(['message' => 'Manutenção em andamento.']);
    }
}
