<?php

namespace App\Repositories;

use App\DTO\SearchOffersDTO;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OfferRepository
{
    private const PER_PAGE = 15;

    /**
     * Cheapest active offer per property matching the search criteria.
     * Ranking (window function), filtering, sorting and pagination all
     * happen in SQL — no PHP-side grouping over a full result set.
     */
    public function findCheapestActiveOffersPerProperty(SearchOffersDTO $dto): LengthAwarePaginator
    {
        $ranked = DB::table('offers')
            ->join('properties', 'properties.id', '=', 'offers.property_id')
            ->join('suppliers', 'suppliers.id', '=', 'offers.supplier_id')
            ->where('offers.check_in', $dto->checkIn->toDateString())
            ->where('offers.check_out', $dto->checkOut->toDateString())
            ->where('offers.max_guests', '>=', $dto->guests)
            ->where('offers.available_units', '>', 0)
            ->where('offers.expires_at', '>', now())
            ->when(
                $dto->city,
                fn (Builder $query, string $city) => $query->where('properties.city', $city)
            )
            ->select([
                'offers.id as offer_id',
                'offers.price',
                'offers.currency',
                'offers.available_units',
                'offers.expires_at',
                'properties.code as property_code',
                'properties.name as property_name',
                'properties.city as property_city',
                'suppliers.slug as supplier_slug',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY offers.property_id ORDER BY offers.price ASC) as rn'),
            ]);

        return DB::table(DB::raw('('.$ranked->toSql().') as ranked_offers'))
            ->mergeBindings($ranked)
            ->where('rn', 1)
            ->orderBy('price')
            ->paginate(self::PER_PAGE, ['*'], 'page', $dto->page);
    }
}
