<?php

namespace Database\Seeders;

use App\Models\HotelAmenity;
use Illuminate\Database\Seeder;

class HotelAmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenities = [
            // General Amenities
            ['name' => 'Free WiFi', 'slug' => 'free-wifi', 'icon' => 'wifi', 'category' => 'general', 'sort_order' => 1],
            ['name' => 'Swimming Pool', 'slug' => 'swimming-pool', 'icon' => 'swimming-pool', 'category' => 'general', 'sort_order' => 2],
            ['name' => 'Fitness Center', 'slug' => 'fitness-center', 'icon' => 'dumbbell', 'category' => 'general', 'sort_order' => 3],
            ['name' => 'Restaurant', 'slug' => 'restaurant', 'icon' => 'utensils', 'category' => 'general', 'sort_order' => 4],
            ['name' => 'Bar', 'slug' => 'bar', 'icon' => 'glass-martini', 'category' => 'general', 'sort_order' => 5],
            ['name' => 'Spa', 'slug' => 'spa', 'icon' => 'spa', 'category' => 'general', 'sort_order' => 6],
            ['name' => 'Parking', 'slug' => 'parking', 'icon' => 'parking', 'category' => 'general', 'sort_order' => 7],
            ['name' => '24-Hour Front Desk', 'slug' => '24-hour-front-desk', 'icon' => 'clock', 'category' => 'general', 'sort_order' => 8],
            ['name' => 'Room Service', 'slug' => 'room-service', 'icon' => 'concierge-bell', 'category' => 'general', 'sort_order' => 9],
            ['name' => 'Airport Shuttle', 'slug' => 'airport-shuttle', 'icon' => 'shuttle-van', 'category' => 'general', 'sort_order' => 10],
            ['name' => 'Pet Friendly', 'slug' => 'pet-friendly', 'icon' => 'paw', 'category' => 'general', 'sort_order' => 11],
            ['name' => 'Business Center', 'slug' => 'business-center', 'icon' => 'briefcase', 'category' => 'general', 'sort_order' => 12],
            ['name' => 'Meeting Rooms', 'slug' => 'meeting-rooms', 'icon' => 'users', 'category' => 'general', 'sort_order' => 13],
            ['name' => 'Laundry Service', 'slug' => 'laundry-service', 'icon' => 'tshirt', 'category' => 'general', 'sort_order' => 14],

            // Room Amenities
            ['name' => 'Air Conditioning', 'slug' => 'air-conditioning', 'icon' => 'snowflake', 'category' => 'room', 'sort_order' => 15],
            ['name' => 'Flat-screen TV', 'slug' => 'flat-screen-tv', 'icon' => 'tv', 'category' => 'room', 'sort_order' => 16],
            ['name' => 'Mini Bar', 'slug' => 'mini-bar', 'icon' => 'wine-bottle', 'category' => 'room', 'sort_order' => 17],
            ['name' => 'Safe', 'slug' => 'safe', 'icon' => 'lock', 'category' => 'room', 'sort_order' => 18],
            ['name' => 'Coffee Maker', 'slug' => 'coffee-maker', 'icon' => 'coffee', 'category' => 'room', 'sort_order' => 19],
            ['name' => 'Balcony', 'slug' => 'balcony', 'icon' => 'building', 'category' => 'room', 'sort_order' => 20],
            ['name' => 'Sea View', 'slug' => 'sea-view', 'icon' => 'water', 'category' => 'room', 'sort_order' => 21],
            ['name' => 'City View', 'slug' => 'city-view', 'icon' => 'city', 'category' => 'room', 'sort_order' => 22],
            ['name' => 'Work Desk', 'slug' => 'work-desk', 'icon' => 'desk', 'category' => 'room', 'sort_order' => 23],
            ['name' => 'Iron & Ironing Board', 'slug' => 'iron', 'icon' => 'tshirt', 'category' => 'room', 'sort_order' => 24],

            // Bathroom Amenities
            ['name' => 'Bathtub', 'slug' => 'bathtub', 'icon' => 'bath', 'category' => 'bathroom', 'sort_order' => 25],
            ['name' => 'Shower', 'slug' => 'shower', 'icon' => 'shower', 'category' => 'bathroom', 'sort_order' => 26],
            ['name' => 'Hair Dryer', 'slug' => 'hair-dryer', 'icon' => 'wind', 'category' => 'bathroom', 'sort_order' => 27],
            ['name' => 'Toiletries', 'slug' => 'toiletries', 'icon' => 'soap', 'category' => 'bathroom', 'sort_order' => 28],
            ['name' => 'Bathrobe & Slippers', 'slug' => 'bathrobe-slippers', 'icon' => 'user-tie', 'category' => 'bathroom', 'sort_order' => 29],

            // Kitchen Amenities
            ['name' => 'Kitchen', 'slug' => 'kitchen', 'icon' => 'utensils', 'category' => 'kitchen', 'sort_order' => 30],
            ['name' => 'Microwave', 'slug' => 'microwave', 'icon' => 'microwave', 'category' => 'kitchen', 'sort_order' => 31],
            ['name' => 'Refrigerator', 'slug' => 'refrigerator', 'icon' => 'cube', 'category' => 'kitchen', 'sort_order' => 32],
            ['name' => 'Dishwasher', 'slug' => 'dishwasher', 'icon' => 'glass-water', 'category' => 'kitchen', 'sort_order' => 33],

            // Entertainment
            ['name' => 'Cable TV', 'slug' => 'cable-tv', 'icon' => 'tv', 'category' => 'entertainment', 'sort_order' => 34],
            ['name' => 'Netflix', 'slug' => 'netflix', 'icon' => 'film', 'category' => 'entertainment', 'sort_order' => 35],
            ['name' => 'Game Console', 'slug' => 'game-console', 'icon' => 'gamepad', 'category' => 'entertainment', 'sort_order' => 36],
            ['name' => 'Books & Magazines', 'slug' => 'books-magazines', 'icon' => 'book', 'category' => 'entertainment', 'sort_order' => 37],
        ];

        foreach ($amenities as $amenity) {
            HotelAmenity::create($amenity);
        }
    }
}
