<?php

namespace App\Http\Controllers\Api;

use App\DTO\CreateReservationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Offer;
use App\Repositories\ReservationRepository;
use Illuminate\Http\JsonResponse;

class ReservationController extends Controller
{
    public function __construct(private readonly ReservationRepository $reservations) {}

    public function store(CreateReservationRequest $request, Offer $offer): JsonResponse
    {
        $dto = CreateReservationDTO::fromArray($request->validated());

        $reservation = $this->reservations->createForOffer($offer, $dto);

        return (new ReservationResource($reservation))->response()->setStatusCode(201);
    }
}
