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
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;

class AuthController extends Controller
{

    public function __construct(readonly AuthService $authService)
    {
    }

    public function login(LoginRequest $request): TokenResource|MessageResource
    {
        try {
            return TokenResource::make($this->authService->login($request->toDto()));
        } catch (Throwable $exception) {
            return MessageResource::error($exception->getMessage(), (int)$exception->getCode());
        }
    }

    public function register(RegisterRequest $request)
    {
        try {
            return TokenResource::make($this->authService->register($request->toDto()))
                ->response()
                ->setStatusCode(ResponseCode::HTTP_CREATED);
        } catch (Throwable $exception) {
            return MessageResource::error($exception->getMessage(), (int)$exception->getCode());
        }
    }

    public function logout(Request $request, JwtService $jwt): MessageResource
    {
        $jwt->revoke((string)$request->bearerToken());
        return MessageResource::success('Successfully logged out.');
    }
}
