<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class PropertyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->property_code,
            'name' => $this->property_name,
            'city' => $this->property_city,
            'best_offer' => [
                'id' => $this->offer_id,
                'supplier' => $this->supplier_slug,
                'price' => $this->price,
                'currency' => $this->currency,
                'available_units' => $this->available_units,
                'expires_at' => Carbon::parse($this->expires_at)->toISOString(),
            ],
        ];
    }
}
