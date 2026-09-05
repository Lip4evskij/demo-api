<?php

namespace App\DTO;

use Carbon\CarbonImmutable;

readonly class ImportPayloadDTO
{
    /**
     * @param OfferPayloadDTO[] $offers
     */
    public function __construct(
        public string $supplierSlug,
        public string $externalImportId,
        public CarbonImmutable $sentAt,
        public array $offers,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            supplierSlug: $data['supplier'],
            externalImportId: $data['external_import_id'],
            sentAt: CarbonImmutable::parse($data['sent_at']),
            offers: array_map(
                static fn (array $offer): OfferPayloadDTO => OfferPayloadDTO::fromArray($offer),
                $data['offers'],
            ),
        );
    }
}
