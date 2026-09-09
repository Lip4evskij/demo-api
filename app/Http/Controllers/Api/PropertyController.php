<?php

namespace App\Http\Controllers\Api;

use App\DTO\SearchOffersDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchOffersRequest;
use App\Http\Resources\PropertyCollection;
use App\Repositories\OfferRepository;

class PropertyController extends Controller
{
    public function __construct(private readonly OfferRepository $offers) {}

    public function index(SearchOffersRequest $request): PropertyCollection
    {
        $dto = SearchOffersDTO::fromArray($request->validated());

        $properties = $this
            ->offers
            ->findCheapestActiveOffersPerProperty($dto);

        return new PropertyCollection($properties);
    }
}
