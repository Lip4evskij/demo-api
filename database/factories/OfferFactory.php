<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('+1 week', '+1 month');
        $checkOut = (clone $checkIn)->modify('+'.fake()->numberBetween(2, 10).' days');

        return [
            'supplier_id' => Supplier::factory(),
            'property_id' => Property::factory(),
            'external_id' => fake()->unique()->uuid(),
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
            'max_guests' => fake()->numberBetween(1, 8),
            'price' => fake()->numberBetween(5000, 200000),
            'currency' => 'EUR',
            'available_units' => fake()->numberBetween(1, 5),
            'expires_at' => fake()->dateTimeBetween('+1 day', '+2 months'),
        ];
    }
}
