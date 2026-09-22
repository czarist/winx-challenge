<?php

namespace App\DataTransferObjects;

use App\Models\User;

final readonly class AuthResult
{
    public function __construct(
        public User $user,
        public string $token,
    ) {}
}
