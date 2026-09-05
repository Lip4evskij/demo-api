<?php

namespace App\DTO;

use Carbon\CarbonImmutable;

readonly class OfferPayloadDTO
{
    public function __construct(
        public string $externalId,
        public PropertyPayloadDTO $property,
        public CarbonImmutable $checkIn,
        public CarbonImmutable $checkOut,
        public int $maxGuests,
        public int $price,
        public string $currency,
        public int $availableUnits,
        public CarbonImmutable $expiresAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            externalId: $data['external_id'],
            property: PropertyPayloadDTO::fromArray($data['property']),
            checkIn: CarbonImmutable::parse($data['check_in']),
            checkOut: CarbonImmutable::parse($data['check_out']),
            maxGuests: (int) $data['max_guests'],
            price: (int) $data['price'],
            currency: $data['currency'],
            availableUnits: (int) $data['available_units'],
            expiresAt: CarbonImmutable::parse($data['expires_at']),
        );
    }

    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'property' => $this->property->toArray(),
            'check_in' => $this->checkIn->toDateString(),
            'check_out' => $this->checkOut->toDateString(),
            'max_guests' => $this->maxGuests,
            'price' => $this->price,
            'currency' => $this->currency,
            'available_units' => $this->availableUnits,
            'expires_at' => $this->expiresAt->toISOString(),
        ];
    }
}
