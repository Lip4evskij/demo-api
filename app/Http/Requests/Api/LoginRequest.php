<?php

namespace App\Http\Requests\Api;

use App\DTO\Auth\LoginDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', Password::min(8)],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => ['description' => 'User email.', 'example' => 'john@example.com'],
            'password' => ['description' => 'User password, at least 8 characters.', 'example' => 'Secret123!'],
        ];
    }

    public function toDto(): LoginDto
    {
        return new LoginDto(
            email: $this->input('email'),
            password: $this->input('password'),
        );
    }
}
