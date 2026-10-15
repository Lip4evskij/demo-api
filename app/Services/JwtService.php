<?php

namespace App\Services;

use DomainException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;
use UnexpectedValueException;

class JwtService
{
    public function issue(int $userId): string
    {
        $now = time();

        return JWT::encode([
            'iss' => config('jwt.issuer'),
            'sub' => (string) $userId,
            'jti' => (string) Str::uuid(),
            'iat' => $now,
            'exp' => $now + config('jwt.ttl'),
        ], config('jwt.secret'), config('jwt.algo'));
    }

    public function parse(string $token): ?object
    {
        try {
            $claims = JWT::decode($token, new Key(config('jwt.secret'), config('jwt.algo')));
        } catch (UnexpectedValueException|DomainException|InvalidArgumentException) {
            return null;
        }

        if (($claims->iss ?? null) !== config('jwt.issuer') || empty($claims->jti)) {
            return null;
        }

        return $this->isRevoked($claims->jti) ? null : $claims;
    }

    public function revoke(string $token): bool
    {
        $claims = $this->parse($token);

        if (!$claims) {
            return false;
        }

        // Keep the token in the blacklist only until it would expire anyway
        $ttl = max(1, $claims->exp - time());

        return Cache::put($this->revokedKey($claims->jti), true, $ttl);
    }

    private function isRevoked(string $jti): bool
    {
        return Cache::has($this->revokedKey($jti));
    }

    private function revokedKey(string $jti): string
    {
        return 'jwt:revoked:' . $jti;
    }
}
