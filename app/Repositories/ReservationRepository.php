<?php

namespace App\Repositories;

use App\DTO\CreateReservationDTO;
use App\Exceptions\OfferNotAvailableException;
use App\Models\Offer;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

class ReservationRepository
{
    /**
     * Locks the offer row for the duration of the transaction so two
     * concurrent requests for the last unit can't both succeed: the
     * second request blocks on lockForUpdate() until the first commits,
     * then re-reads available_units and finds it already decremented.
     *
     * @throws OfferNotAvailableException
     */
    public function createForOffer(Offer $offer, CreateReservationDTO $dto): Reservation
    {
        return DB::transaction(function () use ($offer, $dto) {
            $locked = Offer::query()->lockForUpdate()->find($offer->id);

            if ($locked === null || $locked->available_units <= 0) {
                throw new OfferNotAvailableException('No available units for this offer.');
            }

            $locked->decrement('available_units');

            return Reservation::query()->create([
                'offer_id' => $locked->id,
                'client_reference' => $dto->clientReference,
                'customer_name' => $dto->customerName,
                'customer_email' => $dto->customerEmail,
            ]);
        });
    }
}
