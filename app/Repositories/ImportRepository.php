<?php

namespace App\Repositories;

use App\DTO\ImportPayloadDTO;
use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;

class ImportRepository
{
    public function findExisting(string $supplierSlug, string $externalImportId): ?Import
    {
        return Import::query()
            ->where('external_import_id', $externalImportId)
            ->whereHas('supplier', fn ($query) => $query->where('slug', $supplierSlug))
            ->first();
    }

    public function createFromPayload(ImportPayloadDTO $dto): Import
    {
        $supplier = Supplier::query()->where('slug', $dto->supplierSlug)->firstOrFail();

        return Import::query()->create([
            'supplier_id' => $supplier->id,
            'external_import_id' => $dto->externalImportId,
            'sent_at' => $dto->sentAt,
            'status' => Import::STATUS_PENDING,
            'raw_payload' => $dto->toArray(),
            'total_offers' => count($dto->offers),
        ]);
    }

    /**
     * Find-or-create the property for each offer and upsert the offer by
     * (supplier_id, external_id). Returns the number of offers processed.
     */
    public function upsertOffers(Supplier $supplier, ImportPayloadDTO $dto): int
    {
        $processed = 0;

        foreach ($dto->offers as $offerPayload) {
            $property = Property::query()->firstOrCreate(
                ['code' => $offerPayload->property->code],
                [
                    'name' => $offerPayload->property->name,
                    'city' => $offerPayload->property->city,
                ],
            );

            Offer::query()->updateOrCreate(
                [
                    'supplier_id' => $supplier->id,
                    'external_id' => $offerPayload->externalId,
                ],
                [
                    'property_id' => $property->id,
                    'check_in' => $offerPayload->checkIn->toDateString(),
                    'check_out' => $offerPayload->checkOut->toDateString(),
                    'max_guests' => $offerPayload->maxGuests,
                    'price' => $offerPayload->price,
                    'currency' => $offerPayload->currency,
                    'available_units' => $offerPayload->availableUnits,
                    'expires_at' => $offerPayload->expiresAt,
                ],
            );

            $processed++;
        }

        return $processed;
    }
}
