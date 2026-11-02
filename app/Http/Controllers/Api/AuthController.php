<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\TokenResource;
use App\Services\AuthService;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\Unauthenticated;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;

#[Group('Auth', 'Registration, login and logout with JWT tokens.')]
class AuthController extends Controller
{
    public function __construct(public readonly AuthService $authService) {}

    #[Endpoint('Log in', 'Exchange email and password for a JWT access token.')]
    #[Unauthenticated]
    #[
        Response('{"access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...", "token_type": "Bearer", "expires_in": 3600}', ResponseCode::HTTP_OK, 'Success'),
        Response('{"success": false, "status": "error", "message": "Unauthorized. Invalid credentials"}', ResponseCode::HTTP_UNAUTHORIZED, 'Invalid credentials'),
        Response('{"message": "The email field is required.", "errors": {"email": ["The email field is required."]}}', ResponseCode::HTTP_UNPROCESSABLE_ENTITY, 'Validation error')
    ]
    public function login(LoginRequest $request): TokenResource|MessageResource
    {
        try {
            return TokenResource::make($this->authService->login($request->toDto()));
        } catch (Throwable $exception) {
            return MessageResource::error($exception->getMessage(), (int) $exception->getCode());
        }
    }

    #[Endpoint('Register', 'Create a new user and return a JWT access token.')]
    #[Unauthenticated]
    #[BodyParam('password_confirmation', 'string', 'Must match `prd`.', example: 'Secret123!')]
    #[
        Response('{"access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...", "token_type": "Bearer", "expires_in": 3600}', ResponseCode::HTTP_CREATED, 'Created'),
        Response('{"message": "The email has already been taken.", "errors": {"email": ["The email has already been taken."]}}', ResponseCode::HTTP_UNPROCESSABLE_ENTITY, 'Validation error')
    ]
    public function register(RegisterRequest $request)
    {
        try {
            return TokenResource::make($this->authService->register($request->toDto()))
                ->response()
                ->setStatusCode(ResponseCode::HTTP_CREATED);
        } catch (Throwable $exception) {
            return MessageResource::error($exception->getMessage(), (int) $exception->getCode());
        }
    }

    #[Endpoint('Log out', 'Revoke the current JWT token. The token cannot be used again.')]
    #[Authenticated]
    #[
        Response('{"success": true, "status": "success", "message": "Successfully logged out."}', ResponseCode::HTTP_OK, 'Success'),
        Response('{"message": "Unauthenticated."}', ResponseCode::HTTP_UNAUTHORIZED, 'Missing, invalid or revoked token')
    ]
    public function logout(Request $request, JwtService $jwt): MessageResource
    {
        $jwt->revoke((string) $request->bearerToken());

        return MessageResource::success('Successfully logged out.');
    }
}
