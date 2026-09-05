<?php

namespace App\DTO;

use Carbon\CarbonImmutable;

readonly class SearchOffersDTO
{
    public function __construct(
        public ?string $city,
        public CarbonImmutable $checkIn,
        public CarbonImmutable $checkOut,
        public int $guests,
        public int $page = 1,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            city: $data['city'] ?? null,
            checkIn: CarbonImmutable::parse($data['check_in']),
            checkOut: CarbonImmutable::parse($data['check_out']),
            guests: (int) $data['guests'],
            page: (int) ($data['page'] ?? 1),
        );
    }
}
