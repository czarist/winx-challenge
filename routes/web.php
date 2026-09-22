<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'documentation' => url('/api/documentation'),
]));

// Rota nomeada exigida pelo middleware de autenticação padrão do Laravel
// ao montar o redirect de uma requisição não autenticada. Numa API pura
// nunca é de fato renderizada: o ApiExceptionRenderer intercepta a
// AuthenticationException antes disso e devolve o JSON padronizado.
Route::get('/login', fn () => response()->json(['message' => 'Não autenticado.'], 401))->name('login');
