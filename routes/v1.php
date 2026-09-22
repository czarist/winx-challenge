<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

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
