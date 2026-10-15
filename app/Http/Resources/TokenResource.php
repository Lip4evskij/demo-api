<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Knuckles\Scribe\Attributes\ResponseField;


class TokenResource extends JsonResource
{
    public static $wrap;

    #[ResponseField('access_token', 'string')]
    #[ResponseField('token_type', 'string')]
    #[ResponseField('expires_in', 'integer')]
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->resource,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl'),
        ];
    }

    public static function structure(): array
    {
        return [
            self::$wrap => [
                'access_token',
                'token_type',
                'expires_in',
            ]
        ];
    }
}
