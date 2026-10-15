<?php

namespace App\DTO\Auth;

readonly class LoginDto
{
    public function __construct(public string $email, public string $password)
    {
    }

}
