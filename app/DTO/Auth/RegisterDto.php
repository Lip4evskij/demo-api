<?php

namespace App\DTO\Auth;

readonly class RegisterDto
{
    public function __construct(
        public string $username,
        public string $email,
        public string $password
    ) {}

}
