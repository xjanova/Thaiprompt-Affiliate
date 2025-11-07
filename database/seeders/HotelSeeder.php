<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hotel;
use App\Models\HotelFacility;
use App\Models\RoomType;
use App\Services\RoomAvailabilityService;
use Carbon\Carbon;

class HotelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Hotel Facilities first
        $facilities = [
            ['name' => 'Free WiFi', 'icon' => 'wifi', 'category' => 'general'],
            ['name' => 'Swimming Pool', 'icon' => 'swimming-pool', 'category' => 'recreation'],
            ['name' => 'Fitness Center', 'icon' => 'dumbbell', 'category' => 'recreation'],
            ['name' => 'Restaurant', 'icon' => 'restaurant', 'category' => 'food'],
            ['name' => 'Room Service', 'icon' => 'room-service', 'category' => 'food'],
            ['name' => 'Air Conditioning', 'icon' => 'air-conditioner', 'category' => 'room'],
            ['name' => 'Parking', 'icon' => 'parking', 'category' => 'general'],
            ['name' => 'Airport Shuttle', 'icon' => 'shuttle', 'category' => 'general'],
            ['name' => 'Spa', 'icon' => 'spa', 'category' => 'recreation'],
            ['name' => 'Bar/Lounge', 'icon' => 'cocktail', 'category' => 'food'],
            ['name' => 'Business Center', 'icon' => 'briefcase', 'category' => 'business'],
            ['name' => 'Meeting Rooms', 'icon' => 'meeting', 'category' => 'business'],
            ['name' => '24-Hour Front Desk', 'icon' => 'reception', 'category' => 'general'],
            ['name' => 'Laundry Service', 'icon' => 'washing-machine', 'category' => 'general'],
            ['name' => 'Pet Friendly', 'icon' => 'paw', 'category' => 'general'],
        ];

        $createdFacilities = [];
        foreach ($facilities as $index => $facility) {
            $createdFacilities[] = HotelFacility::create([
                'name' => $facility['name'],
                'icon' => $facility['icon'],
                'category' => $facility['category'],
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        // Create Sample Hotels
        $hotels = [
            [
                'name' => 'Grand Palace Hotel Bangkok',
                'slug' => 'grand-palace-hotel-bangkok',
                'description' => 'Experience luxury at its finest in the heart of Bangkok. The Grand Palace Hotel offers world-class amenities and impeccable service.',
                'short_description' => 'Luxury 5-star hotel in downtown Bangkok',
                'address' => '123 Sukhumvit Road',
                'city' => 'Bangkok',
                'state' => 'Bangkok',
                'country' => 'Thailand',
                'postal_code' => '10110',
                'latitude' => 13.736717,
                'longitude' => 100.523186,
                'phone' => '+66 2 123 4567',
                'email' => 'info@grandpalacebkk.com',
                'type' => 'hotel',
                'star_rating' => 5,
                'check_in_time' => '14:00:00',
                'check_out_time' => '12:00:00',
                'is_active' => true,
                'is_featured' => true,
                'commission_rate' => 10,
                'facilities' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13],
            ],
            [
                'name' => 'Phuket Beach Resort',
                'slug' => 'phuket-beach-resort',
                'description' => 'Tropical paradise awaits at Phuket Beach Resort. Enjoy pristine beaches, crystal-clear waters, and breathtaking sunsets.',
                'short_description' => 'Beachfront resort in Phuket',
                'address' => '456 Patong Beach Road',
                'city' => 'Phuket',
                'state' => 'Phuket',
                'country' => 'Thailand',
                'postal_code' => '83150',
                'latitude' => 7.892400,
                'longitude' => 98.296090,
                'phone' => '+66 76 123 456',
                'email' => 'info@phuketbeach.com',
                'type' => 'resort',
                'star_rating' => 4,
                'check_in_time' => '15:00:00',
                'check_out_time' => '11:00:00',
                'is_active' => true,
                'is_featured' => true,
                'commission_rate' => 12,
                'facilities' => [0, 1, 2, 3, 4, 5, 6, 8, 9, 12, 13],
            ],
            [
                'name' => 'Chiang Mai Mountain Lodge',
                'slug' => 'chiang-mai-mountain-lodge',
                'description' => 'Escape to the mountains at Chiang Mai Mountain Lodge. Surrounded by lush forests and stunning mountain views.',
                'short_description' => 'Boutique lodge in the mountains of Chiang Mai',
                'address' => '789 Mountain View Road',
                'city' => 'Chiang Mai',
                'state' => 'Chiang Mai',
                'country' => 'Thailand',
                'postal_code' => '50100',
                'latitude' => 18.787747,
                'longitude' => 98.993128,
                'phone' => '+66 53 123 789',
                'email' => 'info@chiangmailodge.com',
                'type' => 'resort',
                'star_rating' => 4,
                'check_in_time' => '14:00:00',
                'check_out_time' => '12:00:00',
                'is_active' => true,
                'is_featured' => false,
                'commission_rate' => 8,
                'facilities' => [0, 2, 3, 5, 6, 8, 12, 13, 14],
            ],
        ];

        $availabilityService = app(RoomAvailabilityService::class);

        foreach ($hotels as $hotelData) {
            $facilityIndexes = $hotelData['facilities'];
            unset($hotelData['facilities']);

            $hotel = Hotel::create($hotelData);

            // Attach facilities
            $facilityIds = array_map(fn($index) => $createdFacilities[$index]->id, $facilityIndexes);
            $hotel->facilities()->attach($facilityIds);

            // Create Room Types for each hotel
            $roomTypes = [
                [
                    'name' => 'Standard Room',
                    'description' => 'Comfortable standard room with modern amenities',
                    'size_sqm' => 25,
                    'max_adults' => 2,
                    'max_children' => 1,
                    'max_occupancy' => 3,
                    'total_rooms' => 20,
                    'bed_types' => [['type' => 'Queen', 'count' => 1]],
                    'base_price' => 1500,
                    'weekend_price' => 1800,
                    'extra_adult_price' => 500,
                    'extra_child_price' => 300,
                    'amenities' => ['WiFi', 'TV', 'Air Conditioning', 'Mini Bar', 'Safe'],
                    'view_type' => 'city',
                    'smoking_policy' => 'non_smoking',
                    'is_active' => true,
                    'is_popular' => false,
                ],
                [
                    'name' => 'Deluxe Room',
                    'description' => 'Spacious deluxe room with premium amenities and city view',
                    'size_sqm' => 35,
                    'max_adults' => 2,
                    'max_children' => 2,
                    'max_occupancy' => 4,
                    'total_rooms' => 15,
                    'bed_types' => [['type' => 'King', 'count' => 1]],
                    'base_price' => 2500,
                    'weekend_price' => 3000,
                    'extra_adult_price' => 600,
                    'extra_child_price' => 400,
                    'amenities' => ['WiFi', 'TV', 'Air Conditioning', 'Mini Bar', 'Safe', 'Bathtub', 'Coffee Maker'],
                    'view_type' => 'city',
                    'smoking_policy' => 'non_smoking',
                    'is_active' => true,
                    'is_popular' => true,
                ],
                [
                    'name' => 'Suite',
                    'description' => 'Luxurious suite with separate living area and stunning views',
                    'size_sqm' => 60,
                    'max_adults' => 3,
                    'max_children' => 2,
                    'max_occupancy' => 5,
                    'total_rooms' => 5,
                    'bed_types' => [['type' => 'King', 'count' => 1], ['type' => 'Single', 'count' => 1]],
                    'base_price' => 4500,
                    'weekend_price' => 5500,
                    'extra_adult_price' => 800,
                    'extra_child_price' => 500,
                    'amenities' => ['WiFi', 'TV', 'Air Conditioning', 'Mini Bar', 'Safe', 'Bathtub', 'Coffee Maker', 'Living Room', 'Balcony'],
                    'view_type' => $hotel->type === 'resort' ? 'sea' : 'city',
                    'smoking_policy' => 'non_smoking',
                    'is_active' => true,
                    'is_popular' => true,
                ],
            ];

            foreach ($roomTypes as $roomData) {
                $roomData['hotel_id'] = $hotel->id;
                $roomType = RoomType::create($roomData);

                // Initialize availability for next 365 days
                $startDate = Carbon::today();
                $endDate = Carbon::today()->addDays(365);
                $availabilityService->initializeAvailability($roomType, $startDate, $endDate);
            }
        }

        $this->command->info('✓ Hotels, facilities, and room types seeded successfully!');
        $this->command->info('✓ Room availability initialized for next 365 days');
    }
}
