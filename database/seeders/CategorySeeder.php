<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Electronics', 'slug' => 'electronics', 'order' => 1],
            ['name' => 'Fashion', 'slug' => 'fashion', 'order' => 2],
            ['name' => 'Home & Garden', 'slug' => 'home-garden', 'order' => 3],
            ['name' => 'Health & Beauty', 'slug' => 'health-beauty', 'order' => 4],
            ['name' => 'Sports & Outdoors', 'slug' => 'sports-outdoors', 'order' => 5],
            ['name' => 'Books & Media', 'slug' => 'books-media', 'order' => 6],
            ['name' => 'Toys & Games', 'slug' => 'toys-games', 'order' => 7],
            ['name' => 'Food & Beverages', 'slug' => 'food-beverages', 'order' => 8],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
