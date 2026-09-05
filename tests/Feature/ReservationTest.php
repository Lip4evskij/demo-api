<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function reservationPayload(array $overrides = []): array
    {
        return array_merge([
            'client_reference' => 'web-order-9f782b1c',
            'customer_name' => 'John Smith',
            'customer_email' => 'john@example.com',
        ], $overrides);
    }

    public function test_reservation_is_created_and_decrements_available_units(): void
    {
        $supplier = Supplier::factory()->create();
        $property = Property::factory()->create();
        $offer = Offer::factory()->create([
            'supplier_id' => $supplier->id,
            'property_id' => $property->id,
            'available_units' => 2,
        ]);

        $response = $this->postJson("/api/offers/{$offer->id}/reservations", $this->reservationPayload());

        $response->assertStatus(201);
        $response->assertJsonPath('data.client_reference', 'web-order-9f782b1c');
        $response->assertJsonPath('data.offer_id', $offer->id);

        $this->assertDatabaseHas('reservations', [
            'offer_id' => $offer->id,
            'client_reference' => 'web-order-9f782b1c',
        ]);

        $this->assertSame(1, $offer->fresh()->available_units);
    }

    public function test_reservation_fails_with_409_when_no_units_available(): void
    {
        $supplier = Supplier::factory()->create();
        $property = Property::factory()->create();
        $offer = Offer::factory()->create([
            'supplier_id' => $supplier->id,
            'property_id' => $property->id,
            'available_units' => 0,
        ]);

        $response = $this->postJson("/api/offers/{$offer->id}/reservations", $this->reservationPayload());

        $response->assertStatus(409);

        $this->assertSame(0, $offer->fresh()->available_units);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_last_unit_can_only_be_booked_once(): void
    {
        $supplier = Supplier::factory()->create();
        $property = Property::factory()->create();
        $offer = Offer::factory()->create([
            'supplier_id' => $supplier->id,
            'property_id' => $property->id,
            'available_units' => 1,
        ]);

        $first = $this->postJson("/api/offers/{$offer->id}/reservations", $this->reservationPayload());
        $second = $this->postJson("/api/offers/{$offer->id}/reservations", $this->reservationPayload([
            'client_reference' => 'web-order-second',
        ]));

        $first->assertStatus(201);
        $second->assertStatus(409);

        $this->assertSame(0, $offer->fresh()->available_units);
        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_reservation_requires_valid_payload(): void
    {
        $offer = Offer::factory()->create();

        $response = $this->postJson("/api/offers/{$offer->id}/reservations", [
            'customer_name' => 'John Smith',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('reservations', 0);
    }
}
