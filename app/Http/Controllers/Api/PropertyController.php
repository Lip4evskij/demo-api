<?php

namespace App\Http\Controllers\Api;

use App\DTO\SearchOffersDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Repositories\OfferRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PropertyController extends Controller
{
    public function __construct(private readonly OfferRepository $offers) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'city' => ['nullable', 'string'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['required', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $dto = SearchOffersDTO::fromArray($validated);

        return PropertyResource::collection($this->offers->findCheapestActiveOffersPerProperty($dto));
    }
}
