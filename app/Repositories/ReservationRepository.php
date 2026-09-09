<?php

namespace App\Repositories;

use App\DTO\CreateReservationDTO;
use App\Models\Offer;
use App\Models\Reservation;

class ReservationRepository
{
    public function lockOfferForUpdate(int $offerId): ?Offer
    {
        return Offer::query()
            ->whereKey($offerId)
            ->lockForUpdate()
            ->first();
    }


    public function create(CreateReservationDTO $dto): Reservation
    {
        return Reservation::query()->create([
            'offer_id' => $dto->offerId,
            'client_reference' => $dto->clientReference,
            'customer_name' => $dto->customerName,
            'customer_email' => $dto->customerEmail,
        ]);
    }
}
