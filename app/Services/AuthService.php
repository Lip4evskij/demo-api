<?php

namespace App\Services;

use App\DTO\Auth\LoginDto;
use App\DTO\Auth\RegisterDto;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

final readonly class AuthService
{
    public function __construct(private JwtService $jwt) {}

    public function login(LoginDto $loginDto): string
    {
        $user = User::findByEmail($loginDto->email);

        if (! $user || ! Hash::check($loginDto->password, $user->password)) {
            throw new RuntimeException('Unauthorized. Invalid credentials', ResponseCode::HTTP_UNAUTHORIZED);
        }

        return $this->jwt->issue($user->id);
    }

    public function register(RegisterDto $registerDto): string
    {
        $user = User::create([
            'email' => $registerDto->email,
            'name' => $registerDto->username,
            'password' => $registerDto->password,
        ]);

        if (! $user) {
            throw new RuntimeException('Unauthorized. Register error', ResponseCode::HTTP_UNAUTHORIZED);
        }

        return $this->jwt->issue($user->id);
    }
}
