<?php

namespace App\Http\Requests\Api;

use App\DTO\Auth\RegisterDto;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class, 'email'),
            ],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }

    public function toDto(): RegisterDto
    {
        return new RegisterDto(
            username: $this->string('name')->toString(),
            email: $this->string('email')->toString(),
            password: $this->string('password')->toString(),
        );
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Display name.', 'example' => 'john'],
            'email' => ['description' => 'Unique user email.', 'example' => 'john@example.com'],
            'password' => ['description' => 'At least 8 characters.', 'example' => 'Secret123!'],
        ];
    }
}
