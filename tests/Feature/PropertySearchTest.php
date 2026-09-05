<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PropertySearchTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $checkIn;

    private Carbon $checkOut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->checkIn = Carbon::now()->addMonth()->startOfDay();
        $this->checkOut = $this->checkIn->copy()->addDays(5);
    }

    /**
     * @return array<string, mixed>
     */
    private function searchParams(array $overrides = []): array
    {
        return array_merge([
            'check_in' => $this->checkIn->toDateString(),
            'check_out' => $this->checkOut->toDateString(),
            'guests' => 2,
        ], $overrides);
    }

    private function makeOffer(Property $property, Supplier $supplier, array $overrides = []): Offer
    {
        return Offer::factory()->create(array_merge([
            'property_id' => $property->id,
            'supplier_id' => $supplier->id,
            'check_in' => $this->checkIn->toDateString(),
            'check_out' => $this->checkOut->toDateString(),
            'max_guests' => 4,
            'available_units' => 2,
            'expires_at' => Carbon::now()->addWeek(),
            'currency' => 'EUR',
        ], $overrides));
    }

    public function test_returns_the_cheapest_active_offer_per_property(): void
    {
        $supplierA = Supplier::factory()->create(['slug' => 'supplier-a']);
        $supplierB = Supplier::factory()->create(['slug' => 'supplier-b']);
        $property = Property::factory()->create(['code' => 'BCN-0001', 'city' => 'Barcelona']);

        $this->makeOffer($property, $supplierA, ['price' => 70000]);
        $cheapest = $this->makeOffer($property, $supplierB, ['price' => 50000]);

        $response = $this->getJson('/api/properties?'.http_build_query($this->searchParams(['city' => 'Barcelona'])));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.code', 'BCN-0001');
        $response->assertJsonPath('data.0.best_offer.id', $cheapest->id);
        $response->assertJsonPath('data.0.best_offer.price', 50000);
        $response->assertJsonPath('data.0.best_offer.supplier', 'supplier-b');
    }

    public function test_filters_out_offers_that_do_not_match_search_criteria(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);

        $matching = Property::factory()->create(['code' => 'MATCH-1', 'city' => 'Barcelona']);
        $this->makeOffer($matching, $supplier);

        // Different city
        $wrongCity = Property::factory()->create(['code' => 'WRONG-CITY', 'city' => 'Madrid']);
        $this->makeOffer($wrongCity, $supplier);

        // Different dates
        $wrongDates = Property::factory()->create(['code' => 'WRONG-DATES', 'city' => 'Barcelona']);
        $this->makeOffer($wrongDates, $supplier, [
            'check_in' => $this->checkIn->copy()->addDay()->toDateString(),
            'check_out' => $this->checkOut->copy()->addDay()->toDateString(),
        ]);

        // Not enough capacity
        $tooFewGuests = Property::factory()->create(['code' => 'TOO-FEW-GUESTS', 'city' => 'Barcelona']);
        $this->makeOffer($tooFewGuests, $supplier, ['max_guests' => 1]);

        // Sold out
        $soldOut = Property::factory()->create(['code' => 'SOLD-OUT', 'city' => 'Barcelona']);
        $this->makeOffer($soldOut, $supplier, ['available_units' => 0]);

        // Expired
        $expired = Property::factory()->create(['code' => 'EXPIRED', 'city' => 'Barcelona']);
        $this->makeOffer($expired, $supplier, ['expires_at' => Carbon::now()->subDay()]);

        $response = $this->getJson('/api/properties?'.http_build_query($this->searchParams(['city' => 'Barcelona'])));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.code', 'MATCH-1');
    }

    public function test_search_without_city_filter_returns_offers_from_any_city(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);

        $barcelona = Property::factory()->create(['code' => 'BCN-0001', 'city' => 'Barcelona']);
        $this->makeOffer($barcelona, $supplier);

        $madrid = Property::factory()->create(['code' => 'MAD-0001', 'city' => 'Madrid']);
        $this->makeOffer($madrid, $supplier);

        $response = $this->getJson('/api/properties?'.http_build_query($this->searchParams()));

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_pagination_is_handled_by_the_query_builder(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);

        foreach (range(1, 20) as $i) {
            $property = Property::factory()->create([
                'code' => sprintf('PAG-%04d', $i),
                'city' => 'Barcelona',
            ]);
            $this->makeOffer($property, $supplier, ['price' => $i * 1000]);
        }

        $firstPage = $this->getJson('/api/properties?'.http_build_query($this->searchParams(['city' => 'Barcelona'])));
        $firstPage->assertOk();
        $firstPage->assertJsonCount(15, 'data');
        $firstPage->assertJsonPath('meta.per_page', 15);
        $firstPage->assertJsonPath('meta.total', 20);
        $firstPage->assertJsonPath('meta.current_page', 1);

        $secondPage = $this->getJson('/api/properties?'.http_build_query($this->searchParams(['city' => 'Barcelona', 'page' => 2])));
        $secondPage->assertOk();
        $secondPage->assertJsonCount(5, 'data');
        $secondPage->assertJsonPath('meta.current_page', 2);
    }

    public function test_search_requires_check_in_check_out_and_guests(): void
    {
        $response = $this->getJson('/api/properties?city=Barcelona');

        $response->assertStatus(422);
    }
}
