<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductSearchController;
use Illuminate\Support\Facades\Route;

Route::pattern('product', '[0-9]{1,18}');

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('register', 'register');
        Route::post('login', 'login');

        // O refresh precisa aceitar um token já expirado (mas dentro do
        // refresh_ttl), então fica fora do middleware auth:api: este exigiria
        // um token ainda válido, o que inviabilizaria o próprio propósito da
        // rota. A validação do token acontece dentro do AuthService::refresh().
        Route::post('refresh', 'refresh');

        Route::middleware('auth:api')->post('logout', 'logout');
    });

    Route::middleware('auth:api')->group(function () {
        // Precisa vir antes do apiResource: caso contrário "search" seria
        // interpretado como {product} pela rota show.
        Route::get('products/search', ProductSearchController::class);

        Route::apiResource('products', ProductController::class);
    });
});
