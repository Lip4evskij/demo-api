<?php

namespace App\DTO;

readonly class CreateReservationDTO
{
    public function __construct(
        public int $offerId,
        public string $clientReference,
        public string $customerName,
        public string $customerEmail,
    ) {}

    public static function fromArray(int $offerId, array $data): self
    {
        return new self(
            offerId: $offerId,
            clientReference: $data['client_reference'],
            customerName: $data['customer_name'],
            customerEmail: $data['customer_email'],
        );
    }
}
