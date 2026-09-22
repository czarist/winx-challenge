<?php

namespace App\Services\Contracts;

use App\DataTransferObjects\AuthResult;

interface AuthServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): AuthResult;

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function login(array $credentials): AuthResult;

    public function logout(): void;

    public function refresh(): AuthResult;
}
