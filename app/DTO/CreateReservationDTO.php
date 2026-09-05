<?php

namespace App\DTO;

readonly class CreateReservationDTO
{
    public function __construct(
        public string $clientReference,
        public string $customerName,
        public string $customerEmail,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            clientReference: $data['client_reference'],
            customerName: $data['customer_name'],
            customerEmail: $data['customer_email'],
        );
    }
}
