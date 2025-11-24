<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GoogleMapsService
{
    protected ?string $apiKey = null;
    protected string $baseUrl = 'https://maps.googleapis.com/maps/api';

    public function __construct()
    {
        $this->apiKey = config('services.google_maps.api_key') ?: null;
    }

    /**
     * ตรวจสอบว่ามี API key หรือไม่
     *
     * @throws \Exception
     */
    protected function ensureApiKey(): void
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Google Maps API key is not configured. Please set GOOGLE_MAPS_API_KEY in your .env file.');
        }
    }

    /**
     * Reverse geocode: Convert coordinates to address
     */
    public function reverseGeocode(float $lat, float $lng): array
    {
        $this->ensureApiKey();

        $cacheKey = "geocode:reverse:{$lat}:{$lng}";

        return Cache::remember($cacheKey, 86400, function () use ($lat, $lng) {
            $response = Http::get("{$this->baseUrl}/geocode/json", [
                'latlng' => "{$lat},{$lng}",
                'key' => $this->apiKey,
                'language' => 'th',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Google Maps API request failed');
            }

            $data = $response->json();

            if ($data['status'] !== 'OK') {
                throw new \Exception("Geocoding failed: {$data['status']}");
            }

            $result = $data['results'][0] ?? null;

            if (!$result) {
                throw new \Exception('No results found');
            }

            return [
                'formatted_address' => $result['formatted_address'],
                'address_components' => $this->parseAddressComponents($result['address_components']),
                'place_id' => $result['place_id'],
                'location' => [
                    'lat' => $result['geometry']['location']['lat'],
                    'lng' => $result['geometry']['location']['lng'],
                ],
            ];
        });
    }

    /**
     * Geocode: Convert address to coordinates
     */
    public function geocode(string $address): array
    {
        $this->ensureApiKey();

        $cacheKey = "geocode:forward:" . md5($address);

        return Cache::remember($cacheKey, 86400, function () use ($address) {
            $response = Http::get("{$this->baseUrl}/geocode/json", [
                'address' => $address,
                'key' => $this->apiKey,
                'language' => 'th',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Google Maps API request failed');
            }

            $data = $response->json();

            if ($data['status'] !== 'OK') {
                throw new \Exception("Geocoding failed: {$data['status']}");
            }

            $result = $data['results'][0] ?? null;

            if (!$result) {
                throw new \Exception('No results found');
            }

            return [
                'formatted_address' => $result['formatted_address'],
                'address_components' => $this->parseAddressComponents($result['address_components']),
                'place_id' => $result['place_id'],
                'location' => [
                    'lat' => $result['geometry']['location']['lat'],
                    'lng' => $result['geometry']['location']['lng'],
                ],
            ];
        });
    }

    /**
     * Get distance and duration between two points
     */
    public function getDirections(array $origin, array $destination, string $mode = 'driving'): array
    {
        $this->ensureApiKey();

        $originStr = "{$origin['lat']},{$origin['lng']}";
        $destStr = "{$destination['lat']},{$destination['lng']}";

        $cacheKey = "directions:{$originStr}:{$destStr}:{$mode}";

        return Cache::remember($cacheKey, 3600, function () use ($originStr, $destStr, $mode) {
            $response = Http::get("{$this->baseUrl}/directions/json", [
                'origin' => $originStr,
                'destination' => $destStr,
                'mode' => $mode,
                'key' => $this->apiKey,
                'language' => 'th',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Google Maps API request failed');
            }

            $data = $response->json();

            if ($data['status'] !== 'OK') {
                throw new \Exception("Directions failed: {$data['status']}");
            }

            $route = $data['routes'][0] ?? null;
            $leg = $route['legs'][0] ?? null;

            if (!$leg) {
                throw new \Exception('No route found');
            }

            return [
                'distance' => [
                    'value' => $leg['distance']['value'], // meters
                    'text' => $leg['distance']['text'],
                ],
                'duration' => [
                    'value' => $leg['duration']['value'], // seconds
                    'text' => $leg['duration']['text'],
                ],
                'start_address' => $leg['start_address'],
                'end_address' => $leg['end_address'],
                'steps' => collect($leg['steps'])->map(fn($step) => [
                    'distance' => $step['distance'],
                    'duration' => $step['duration'],
                    'instruction' => $step['html_instructions'],
                    'start_location' => $step['start_location'],
                    'end_location' => $step['end_location'],
                ]),
            ];
        });
    }

    /**
     * Calculate distance matrix for multiple origins/destinations
     */
    public function getDistanceMatrix(array $origins, array $destinations, string $mode = 'driving'): array
    {
        $this->ensureApiKey();

        $originsStr = collect($origins)->map(fn($o) => "{$o['lat']},{$o['lng']}")->implode('|');
        $destsStr = collect($destinations)->map(fn($d) => "{$d['lat']},{$d['lng']}")->implode('|');

        $response = Http::get("{$this->baseUrl}/distancematrix/json", [
            'origins' => $originsStr,
            'destinations' => $destsStr,
            'mode' => $mode,
            'key' => $this->apiKey,
            'language' => 'th',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Google Maps API request failed');
        }

        $data = $response->json();

        if ($data['status'] !== 'OK') {
            throw new \Exception("Distance matrix failed: {$data['status']}");
        }

        return [
            'origin_addresses' => $data['origin_addresses'],
            'destination_addresses' => $data['destination_addresses'],
            'rows' => collect($data['rows'])->map(fn($row) => [
                'elements' => collect($row['elements'])->map(fn($el) => [
                    'status' => $el['status'],
                    'distance' => $el['distance'] ?? null,
                    'duration' => $el['duration'] ?? null,
                ]),
            ]),
        ];
    }

    /**
     * Get place details by place ID
     */
    public function getPlaceDetails(string $placeId): array
    {
        $this->ensureApiKey();

        $cacheKey = "place:details:{$placeId}";

        return Cache::remember($cacheKey, 86400, function () use ($placeId) {
            $response = Http::get("{$this->baseUrl}/place/details/json", [
                'place_id' => $placeId,
                'key' => $this->apiKey,
                'language' => 'th',
                'fields' => 'name,formatted_address,geometry,address_components,type,rating,user_ratings_total,photos',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Google Maps API request failed');
            }

            $data = $response->json();

            if ($data['status'] !== 'OK') {
                throw new \Exception("Place details failed: {$data['status']}");
            }

            return $data['result'];
        });
    }

    /**
     * Parse address components into structured format
     */
    protected function parseAddressComponents(array $components): array
    {
        $parsed = [
            'street_number' => null,
            'route' => null,
            'sublocality' => null,
            'locality' => null,
            'administrative_area_level_1' => null,
            'administrative_area_level_2' => null,
            'country' => null,
            'postal_code' => null,
        ];

        foreach ($components as $component) {
            foreach ($component['types'] as $type) {
                if (isset($parsed[$type])) {
                    $parsed[$type] = $component['long_name'];
                }
            }
        }

        return [
            'street_number' => $parsed['street_number'],
            'street' => $parsed['route'],
            'subdistrict' => $parsed['sublocality'],
            'district' => $parsed['locality'] ?? $parsed['administrative_area_level_2'],
            'province' => $parsed['administrative_area_level_1'],
            'country' => $parsed['country'],
            'postal_code' => $parsed['postal_code'],
        ];
    }

    /**
     * Search nearby places
     */
    public function searchNearby(float $lat, float $lng, string $type, int $radius = 1000): array
    {
        $this->ensureApiKey();

        $response = Http::get("{$this->baseUrl}/place/nearbysearch/json", [
            'location' => "{$lat},{$lng}",
            'radius' => $radius,
            'type' => $type,
            'key' => $this->apiKey,
            'language' => 'th',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Google Maps API request failed');
        }

        $data = $response->json();

        if ($data['status'] !== 'OK') {
            throw new \Exception("Nearby search failed: {$data['status']}");
        }

        return collect($data['results'])->map(fn($place) => [
            'place_id' => $place['place_id'],
            'name' => $place['name'],
            'vicinity' => $place['vicinity'],
            'location' => $place['geometry']['location'],
            'rating' => $place['rating'] ?? null,
            'user_ratings_total' => $place['user_ratings_total'] ?? null,
        ])->toArray();
    }
}
