<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Knuckles\Scribe\Attributes\ResponseField;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property string $resource Message text
 */
class MessageResource extends JsonResource
{
    public static $wrap;

    protected int $statusCode = Response::HTTP_OK;
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

    #[ResponseField('success', 'bool')]
    #[ResponseField('status', 'string')]
    #[ResponseField('message', 'string')]
    public function toArray(Request $request): array
    {
        $resource = (array) $this->resource;

        return [
            'success' => (bool)Arr::get($resource, 'success'),
            'status' => (string)Arr::get($resource, 'status') ?: self::STATUS_ERROR,
            'message' => (string)Arr::get($resource, 'message'),
        ];
    }

    public static function structure(): array
    {
        $structure = [
            'success',
            'status',
            'message',
        ];

        if (self::$wrap) {
            return [self::$wrap => $structure];
        }

        return $structure;
    }

    public static function error(?string $message = null, int $statusCode = Response::HTTP_OK): self
    {
        $resource = self::make([
            'success' => false,
            'status' => self::STATUS_ERROR,
            'message' => $message,
        ]);

        $resource->statusCode = $statusCode;

        return $resource;
    }

    public static function success(?string $message = null, int $statusCode = Response::HTTP_OK): self
    {
        $resource = self::make([
            'success' => true,
            'status' => self::STATUS_SUCCESS,
            'message' => $message,
        ]);

        $resource->statusCode = $statusCode;

        return $resource;
    }

    public function withResponse($request, $response): void
    {
        $response->setStatusCode($this->statusCode);
    }
}
