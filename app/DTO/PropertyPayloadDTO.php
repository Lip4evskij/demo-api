<?php

namespace App\DTO;

readonly class PropertyPayloadDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public string $city,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            name: $data['name'],
            city: $data['city'],
        );
    }
}
