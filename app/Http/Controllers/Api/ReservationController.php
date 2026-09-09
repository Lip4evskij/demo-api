<?php

namespace App\Http\Controllers\Api;

use App\DTO\CreateReservationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Offer;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ReservationController extends Controller
{
    public function __construct(private readonly ReservationService $reservations) {}

    public function store(CreateReservationRequest $request, Offer $offer): JsonResponse
    {
        $dto = CreateReservationDTO::fromArray($offer->id, $request->validated());

        $reservation = $this->reservations->reserve($offer, $dto);

        return (new ReservationResource($reservation))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
