<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductJourneyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stage' => $this->stage,
            'stage_name' => $this->stage_name,
            'sequence_order' => $this->sequence_order,

            // Location
            'location' => [
                'name' => $this->location_name,
                'coordinates' => $this->location_coordinates,
            ],

            // Time
            'arrived_at' => $this->arrived_at?->toIso8601String(),
            'departed_at' => $this->departed_at?->toIso8601String(),
            'duration_hours' => $this->duration_hours,
            'formatted_duration' => $this->formatted_duration,
            'is_completed' => $this->isCompleted(),

            // Handler
            'handler' => [
                'id' => $this->handler_id,
                'name' => $this->handler_name,
                'type' => $this->handler_type,
                'organization' => $this->organization,
            ],

            // Environmental data
            'environment' => [
                'temperature' => $this->temperature_range,
                'humidity' => $this->humidity_range,
                'storage_conditions' => $this->storage_conditions,
            ],

            // Transport
            'transport' => $this->when($this->transport_method, fn() => [
                'method' => $this->transport_method,
                'distance_km' => $this->distance_km,
                'fuel_type' => $this->fuel_type,
            ]),

            // Carbon
            'carbon_emission' => $this->stage_carbon_emission,

            // Quality
            'quality_status' => $this->quality_status,
            'quality_checks_count' => $this->whenCounted('qualityCheckpoints'),

            // Documentation
            'notes' => $this->notes,
            'photos' => $this->photos,
            'documents' => $this->documents,

            // Blockchain
            'blockchain_hash' => $this->blockchain_hash,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
