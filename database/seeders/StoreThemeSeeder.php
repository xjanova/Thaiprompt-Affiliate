<?php

namespace Database\Seeders;

use App\Models\StoreTheme;
use Illuminate\Database\Seeder;

class StoreThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $themes = [
            [
                'name' => 'Modern Minimalist',
                'slug' => 'modern-minimalist',
                'description' => 'Clean and simple design with focus on products',
                'preview_image' => '/themes/modern-minimalist/preview.jpg',
                'screenshots' => ['/themes/modern-minimalist/screen1.jpg', '/themes/modern-minimalist/screen2.jpg'],
                'color_scheme' => [
                    'primary' => '#2c3e50',
                    'secondary' => '#ecf0f1',
                    'accent' => '#3498db',
                ],
                'typography' => [
                    'heading' => 'Montserrat',
                    'body' => 'Open Sans',
                ],
                'layout_options' => [
                    'style' => 'grid',
                    'sidebar' => true,
                ],
                'category' => 'general',
                'has_custom_header' => true,
                'has_custom_footer' => true,
                'has_slider' => true,
                'has_product_filters' => true,
                'has_mega_menu' => false,
                'is_responsive' => true,
                'price' => 0,
                'is_premium' => false,
                'is_active' => true,
                'is_featured' => true,
                'popularity_score' => 100,
            ],
            [
                'name' => 'Fashion Boutique',
                'slug' => 'fashion-boutique',
                'description' => 'Elegant design perfect for fashion and clothing stores',
                'preview_image' => '/themes/fashion-boutique/preview.jpg',
                'screenshots' => ['/themes/fashion-boutique/screen1.jpg'],
                'color_scheme' => [
                    'primary' => '#000000',
                    'secondary' => '#ffffff',
                    'accent' => '#d4af37',
                ],
                'typography' => [
                    'heading' => 'Playfair Display',
                    'body' => 'Lato',
                ],
                'layout_options' => [
                    'style' => 'masonry',
                    'sidebar' => false,
                ],
                'category' => 'fashion',
                'has_custom_header' => true,
                'has_custom_footer' => true,
                'has_slider' => true,
                'has_product_filters' => true,
                'has_mega_menu' => true,
                'is_responsive' => true,
                'price' => 999,
                'is_premium' => true,
                'is_active' => true,
                'is_featured' => true,
                'popularity_score' => 85,
            ],
            [
                'name' => 'Hotel Paradise',
                'slug' => 'hotel-paradise',
                'description' => 'Beautiful theme designed specifically for hotels and resorts',
                'preview_image' => '/themes/hotel-paradise/preview.jpg',
                'screenshots' => ['/themes/hotel-paradise/screen1.jpg'],
                'color_scheme' => [
                    'primary' => '#0056b3',
                    'secondary' => '#ffc107',
                    'accent' => '#28a745',
                ],
                'typography' => [
                    'heading' => 'Raleway',
                    'body' => 'Roboto',
                ],
                'layout_options' => [
                    'style' => 'grid',
                    'sidebar' => true,
                ],
                'category' => 'hotel',
                'has_custom_header' => true,
                'has_custom_footer' => true,
                'has_slider' => true,
                'has_product_filters' => true,
                'has_mega_menu' => true,
                'is_responsive' => true,
                'price' => 1499,
                'is_premium' => true,
                'is_active' => true,
                'is_featured' => true,
                'popularity_score' => 90,
            ],
            [
                'name' => 'Electronics Hub',
                'slug' => 'electronics-hub',
                'description' => 'Tech-focused theme for electronics and gadget stores',
                'preview_image' => '/themes/electronics-hub/preview.jpg',
                'screenshots' => [],
                'color_scheme' => [
                    'primary' => '#ff6b6b',
                    'secondary' => '#4ecdc4',
                    'accent' => '#ffe66d',
                ],
                'typography' => [
                    'heading' => 'Ubuntu',
                    'body' => 'Source Sans Pro',
                ],
                'layout_options' => [
                    'style' => 'grid',
                    'sidebar' => true,
                ],
                'category' => 'electronics',
                'has_custom_header' => true,
                'has_custom_footer' => true,
                'has_slider' => true,
                'has_product_filters' => true,
                'has_mega_menu' => true,
                'is_responsive' => true,
                'price' => 799,
                'is_premium' => true,
                'is_active' => true,
                'is_featured' => false,
                'popularity_score' => 60,
            ],
            [
                'name' => 'Food & Restaurant',
                'slug' => 'food-restaurant',
                'description' => 'Delicious theme for food delivery and restaurant services',
                'preview_image' => '/themes/food-restaurant/preview.jpg',
                'screenshots' => [],
                'color_scheme' => [
                    'primary' => '#e74c3c',
                    'secondary' => '#f39c12',
                    'accent' => '#27ae60',
                ],
                'typography' => [
                    'heading' => 'Pacifico',
                    'body' => 'Nunito',
                ],
                'layout_options' => [
                    'style' => 'grid',
                    'sidebar' => false,
                ],
                'category' => 'food',
                'has_custom_header' => true,
                'has_custom_footer' => true,
                'has_slider' => true,
                'has_product_filters' => false,
                'has_mega_menu' => false,
                'is_responsive' => true,
                'price' => 0,
                'is_premium' => false,
                'is_active' => true,
                'is_featured' => true,
                'popularity_score' => 75,
            ],
        ];

        foreach ($themes as $theme) {
            StoreTheme::create($theme);
        }
    }
}
