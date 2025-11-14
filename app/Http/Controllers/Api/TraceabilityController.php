<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductJourney;
use App\Services\FoodTraceabilityService;
use App\Http\Requests\FoodPassport\AddJourneyStageRequest;
use App\Http\Resources\ProductJourneyResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TraceabilityController extends Controller
{
    public function __construct(
        protected FoodTraceabilityService $traceabilityService
    ) {}

    /**
     * Add new journey stage
     * POST /api/v1/food-passport/journey/stages
     */
    public function addStage(AddJourneyStageRequest $request): JsonResponse
    {
        try {
            $journey = $this->traceabilityService->addJourneyStage(
                $request->input('food_product_id'),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Journey stage added successfully',
                'data' => new ProductJourneyResource($journey),
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Failed to add journey stage', [
                'error' => $e->getMessage(),
                'data' => $request->validated(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add journey stage',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update journey stage
     * PUT /api/v1/food-passport/journey/stages/{id}
     */
    public function updateStage(AddJourneyStageRequest $request, int $id): JsonResponse
    {
        $journey = ProductJourney::findOrFail($id);

        // Authorization check
        $this->authorize('update', $journey);

        $journey->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Journey stage updated successfully',
            'data' => new ProductJourneyResource($journey->fresh()),
        ]);
    }

    /**
     * Complete journey stage (mark as departed)
     * POST /api/v1/food-passport/journey/stages/{id}/complete
     */
    public function completeStage(int $id): JsonResponse
    {
        $journey = $this->traceabilityService->completeJourneyStage($id);

        return response()->json([
            'success' => true,
            'message' => 'Journey stage completed',
            'data' => new ProductJourneyResource($journey),
        ]);
    }

    /**
     * Get journey stages for a product
     * GET /api/v1/food-passport/journey/products/{productId}
     */
    public function getProductJourney(int $productId): JsonResponse
    {
        $journey = $this->traceabilityService->getFullJourney($productId);

        return response()->json([
            'success' => true,
            'data' => ProductJourneyResource::collection($journey),
        ]);
    }

    /**
     * IOT SENSOR: Record sensor data for journey stage
     * POST /api/v1/food-passport/journey/stages/{id}/sensor-data
     */
    public function recordSensorData(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'temperature' => 'nullable|numeric',
            'humidity' => 'nullable|numeric',
            'location' => 'nullable|array',
            'location.lat' => 'required_with:location|numeric',
            'location.lng' => 'required_with:location|numeric',
            'timestamp' => 'nullable|date',
        ]);

        $journey = ProductJourney::findOrFail($id);

        // Update temperature and humidity data
        if ($request->filled('temperature')) {
            $tempRange = $journey->temperature_range ?? ['min' => null, 'max' => null, 'avg' => null, 'readings' => []];
            $readings = $tempRange['readings'] ?? [];
            $readings[] = [
                'value' => $request->input('temperature'),
                'timestamp' => $request->input('timestamp', now()->toIso8601String()),
            ];

            // Calculate min, max, avg
            $values = array_column($readings, 'value');
            $tempRange['min'] = min($values);
            $tempRange['max'] = max($values);
            $tempRange['avg'] = array_sum($values) / count($values);
            $tempRange['readings'] = array_slice($readings, -100); // Keep last 100 readings

            $journey->temperature_range = $tempRange;
        }

        if ($request->filled('humidity')) {
            $humidityRange = $journey->humidity_range ?? ['min' => null, 'max' => null, 'avg' => null, 'readings' => []];
            $readings = $humidityRange['readings'] ?? [];
            $readings[] = [
                'value' => $request->input('humidity'),
                'timestamp' => $request->input('timestamp', now()->toIso8601String()),
            ];

            $values = array_column($readings, 'value');
            $humidityRange['min'] = min($values);
            $humidityRange['max'] = max($values);
            $humidityRange['avg'] = array_sum($values) / count($values);
            $humidityRange['readings'] = array_slice($readings, -100);

            $journey->humidity_range = $humidityRange;
        }

        // Update location if provided
        if ($request->filled('location')) {
            $journey->location_coordinates = $request->input('location');
        }

        $journey->save();

        return response()->json([
            'success' => true,
            'message' => 'Sensor data recorded successfully',
            'data' => [
                'temperature' => $journey->temperature_range,
                'humidity' => $journey->humidity_range,
                'location' => $journey->location_coordinates,
            ],
        ]);
    }

    /**
     * IOT SENSOR: Bulk record sensor data (from IoT device)
     * POST /api/v1/food-passport/journey/stages/{id}/sensor-data/bulk
     */
    public function bulkRecordSensorData(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'readings' => 'required|array|min:1',
            'readings.*.temperature' => 'nullable|numeric',
            'readings.*.humidity' => 'nullable|numeric',
            'readings.*.location' => 'nullable|array',
            'readings.*.location.lat' => 'required_with:readings.*.location|numeric',
            'readings.*.location.lng' => 'required_with:readings.*.location|numeric',
            'readings.*.timestamp' => 'required|date',
        ]);

        $journey = ProductJourney::findOrFail($id);

        $tempReadings = [];
        $humidityReadings = [];

        foreach ($request->input('readings') as $reading) {
            if (isset($reading['temperature'])) {
                $tempReadings[] = [
                    'value' => $reading['temperature'],
                    'timestamp' => $reading['timestamp'],
                ];
            }

            if (isset($reading['humidity'])) {
                $humidityReadings[] = [
                    'value' => $reading['humidity'],
                    'timestamp' => $reading['timestamp'],
                ];
            }

            // Update location with latest reading
            if (isset($reading['location'])) {
                $journey->location_coordinates = $reading['location'];
            }
        }

        // Update temperature range
        if (!empty($tempReadings)) {
            $tempRange = $journey->temperature_range ?? ['min' => null, 'max' => null, 'avg' => null, 'readings' => []];
            $allReadings = array_merge($tempRange['readings'] ?? [], $tempReadings);
            $allReadings = array_slice($allReadings, -1000); // Keep last 1000 readings

            $values = array_column($allReadings, 'value');
            $tempRange['min'] = min($values);
            $tempRange['max'] = max($values);
            $tempRange['avg'] = array_sum($values) / count($values);
            $tempRange['readings'] = $allReadings;
            $tempRange['last_updated'] = end($tempReadings)['timestamp'];

            $journey->temperature_range = $tempRange;
        }

        // Update humidity range
        if (!empty($humidityReadings)) {
            $humidityRange = $journey->humidity_range ?? ['min' => null, 'max' => null, 'avg' => null, 'readings' => []];
            $allReadings = array_merge($humidityRange['readings'] ?? [], $humidityReadings);
            $allReadings = array_slice($allReadings, -1000);

            $values = array_column($allReadings, 'value');
            $humidityRange['min'] = min($values);
            $humidityRange['max'] = max($values);
            $humidityRange['avg'] = array_sum($values) / count($values);
            $humidityRange['readings'] = $allReadings;
            $humidityRange['last_updated'] = end($humidityReadings)['timestamp'];

            $journey->humidity_range = $humidityRange;
        }

        $journey->save();

        return response()->json([
            'success' => true,
            'message' => sprintf('Recorded %d sensor readings successfully', count($request->input('readings'))),
            'data' => [
                'temperature' => $journey->temperature_range,
                'humidity' => $journey->humidity_range,
                'location' => $journey->location_coordinates,
                'readings_processed' => count($request->input('readings')),
            ],
        ]);
    }

    /**
     * GOOGLE MAPS: Get geocoded address from coordinates
     * POST /api/v1/food-passport/journey/geocode
     */
    public function geocodeLocation(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        try {
            $geocoded = app(GoogleMapsService::class)->reverseGeocode(
                $request->input('lat'),
                $request->input('lng')
            );

            return response()->json([
                'success' => true,
                'data' => $geocoded,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to geocode location',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GOOGLE MAPS: Get coordinates from address
     * POST /api/v1/food-passport/journey/geocode-address
     */
    public function geocodeAddress(Request $request): JsonResponse
    {
        $request->validate([
            'address' => 'required|string',
        ]);

        try {
            $geocoded = app(GoogleMapsService::class)->geocode(
                $request->input('address')
            );

            return response()->json([
                'success' => true,
                'data' => $geocoded,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to geocode address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate distance between two points
     * POST /api/v1/food-passport/journey/calculate-distance
     */
    public function calculateDistance(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|array',
            'from.lat' => 'required|numeric',
            'from.lng' => 'required|numeric',
            'to' => 'required|array',
            'to.lat' => 'required|numeric',
            'to.lng' => 'required|numeric',
        ]);

        $from = $request->input('from');
        $to = $request->input('to');

        $distance = $this->calculateDistanceInKm(
            $from['lat'],
            $from['lng'],
            $to['lat'],
            $to['lng']
        );

        return response()->json([
            'success' => true,
            'data' => [
                'distance_km' => round($distance, 2),
                'distance_m' => round($distance * 1000, 0),
            ],
        ]);
    }

    /**
     * Calculate distance using Haversine formula
     */
    protected function calculateDistanceInKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
