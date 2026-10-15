<?php

namespace App\Http\Requests\Api;

use App\DTO\Auth\LoginDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Knuckles\Scribe\Attributes\ResponseField;

class LoginRequest extends FormRequest
{
    #[ResponseField('email', 'string')]
    #[ResponseField('password', 'string')]
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', Password::min(8)],
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
