<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PageBuilderTemplate;

class PageBuilderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Hero Templates
            [
                'template_type' => 'hero',
                'name' => 'Hero - Modern Gradient',
                'slug' => 'hero-modern-gradient',
                'description' => 'Modern hero section with animated gradient background',
                'category' => 'gradient',
                'sort_order' => 1,
                'is_active' => true,
                'default_settings' => [
                    'gradient_from' => '#667eea',
                    'gradient_to' => '#764ba2',
                    'text_align' => 'center',
                    'padding_y' => 'py-32',
                ],
                'default_content' => [
                    'title' => 'Welcome to Our Platform',
                    'subtitle' => 'Build amazing things with our powerful tools',
                    'cta_primary_text' => 'Get Started',
                    'cta_primary_url' => '/register',
                    'cta_secondary_text' => 'Learn More',
                    'cta_secondary_url' => '/about',
                ],
            ],
            [
                'template_type' => 'hero',
                'name' => 'Hero - Minimal White',
                'slug' => 'hero-minimal-white',
                'description' => 'Clean and minimal hero section with white background',
                'category' => 'minimal',
                'sort_order' => 2,
                'is_active' => true,
                'default_settings' => [
                    'gradient_from' => '#ffffff',
                    'gradient_to' => '#f7fafc',
                    'text_align' => 'left',
                    'padding_y' => 'py-24',
                ],
                'default_content' => [
                    'title' => 'Simple & Powerful',
                    'subtitle' => 'Everything you need, nothing you don\'t',
                    'cta_primary_text' => 'Start Free Trial',
                    'cta_primary_url' => '/register',
                ],
            ],

            // Feature Templates
            [
                'template_type' => 'features',
                'name' => 'Features - 3 Column Grid',
                'slug' => 'features-grid-3',
                'description' => '3-column feature grid with icons',
                'category' => 'modern',
                'sort_order' => 10,
                'is_active' => true,
                'default_settings' => [
                    'columns' => 3,
                    'icon_color' => '#667eea',
                ],
                'default_content' => [
                    'title' => 'Why Choose Us',
                    'subtitle' => 'Powerful features to help you succeed',
                    'features' => [
                        [
                            'icon' => 'zap',
                            'title' => 'Lightning Fast',
                            'description' => 'Optimized for speed and performance',
                        ],
                        [
                            'icon' => 'shield',
                            'title' => 'Secure & Safe',
                            'description' => 'Bank-level security for your data',
                        ],
                        [
                            'icon' => 'heart',
                            'title' => 'Easy to Use',
                            'description' => 'Intuitive interface anyone can master',
                        ],
                    ],
                ],
            ],
            [
                'template_type' => 'features',
                'name' => 'Features - 4 Column Grid',
                'slug' => 'features-grid-4',
                'description' => '4-column feature grid layout',
                'category' => 'modern',
                'sort_order' => 11,
                'is_active' => true,
                'default_settings' => [
                    'columns' => 4,
                    'icon_color' => '#10b981',
                ],
                'default_content' => [
                    'title' => 'Everything You Need',
                    'subtitle' => 'All the tools in one place',
                    'features' => [
                        [
                            'icon' => 'zap',
                            'title' => 'Fast Performance',
                            'description' => 'Blazing fast load times',
                        ],
                        [
                            'icon' => 'shield',
                            'title' => 'Secure',
                            'description' => 'Your data is safe',
                        ],
                        [
                            'icon' => 'heart',
                            'title' => 'User Friendly',
                            'description' => 'Easy to understand',
                        ],
                        [
                            'icon' => 'check',
                            'title' => 'Reliable',
                            'description' => '99.9% uptime',
                        ],
                    ],
                ],
            ],

            // Statistics Templates
            [
                'template_type' => 'statistics',
                'name' => 'Statistics - Gradient Counter',
                'slug' => 'statistics-gradient',
                'description' => 'Animated statistics with gradient background',
                'category' => 'gradient',
                'sort_order' => 20,
                'is_active' => true,
                'default_settings' => [
                    'gradient_from' => '#667eea',
                    'gradient_to' => '#764ba2',
                    'columns' => 4,
                ],
                'default_content' => [
                    'stats' => [
                        ['label' => 'Active Users', 'value' => 10000, 'suffix' => '+'],
                        ['label' => 'Total Revenue', 'value' => 1000000, 'prefix' => '$'],
                        ['label' => 'Success Rate', 'value' => 99, 'suffix' => '%'],
                        ['label' => 'Countries', 'value' => 50, 'suffix' => '+'],
                    ],
                ],
            ],
            [
                'template_type' => 'statistics',
                'name' => 'Statistics - 3 Column',
                'slug' => 'statistics-3col',
                'description' => '3-column statistics layout',
                'category' => 'minimal',
                'sort_order' => 21,
                'is_active' => true,
                'default_settings' => [
                    'gradient_from' => '#1e40af',
                    'gradient_to' => '#3b82f6',
                    'columns' => 3,
                ],
                'default_content' => [
                    'stats' => [
                        ['label' => 'Customers', 'value' => 5000, 'suffix' => '+'],
                        ['label' => 'Projects', 'value' => 200, 'suffix' => '+'],
                        ['label' => 'Satisfaction', 'value' => 98, 'suffix' => '%'],
                    ],
                ],
            ],

            // CTA Templates
            [
                'template_type' => 'cta',
                'name' => 'CTA - Centered Gradient',
                'slug' => 'cta-centered-gradient',
                'description' => 'Centered call-to-action with gradient',
                'category' => 'gradient',
                'sort_order' => 30,
                'is_active' => true,
                'default_settings' => [
                    'gradient_from' => '#667eea',
                    'gradient_to' => '#764ba2',
                    'text_align' => 'center',
                    'padding_y' => 'py-20',
                ],
                'default_content' => [
                    'title' => 'Ready to Get Started?',
                    'description' => 'Join thousands of satisfied users today',
                    'cta_text' => 'Sign Up Now',
                    'cta_url' => '/register',
                ],
            ],
            [
                'template_type' => 'cta',
                'name' => 'CTA - Simple Blue',
                'slug' => 'cta-simple-blue',
                'description' => 'Simple blue call-to-action',
                'category' => 'minimal',
                'sort_order' => 31,
                'is_active' => true,
                'default_settings' => [
                    'gradient_from' => '#3b82f6',
                    'gradient_to' => '#2563eb',
                    'text_align' => 'center',
                    'padding_y' => 'py-16',
                ],
                'default_content' => [
                    'title' => 'Start Your Free Trial',
                    'description' => 'No credit card required',
                    'cta_text' => 'Get Started',
                    'cta_url' => '/register',
                ],
            ],

            // Content Block Templates
            [
                'template_type' => 'content_block',
                'name' => 'Content - White Background',
                'slug' => 'content-white',
                'description' => 'Simple content block with white background',
                'category' => 'minimal',
                'sort_order' => 40,
                'is_active' => true,
                'default_settings' => [
                    'background_color' => '#ffffff',
                    'padding_y' => 'py-16',
                ],
                'default_content' => [
                    'title' => 'About Us',
                    'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                ],
            ],

            // Spacer Template
            [
                'template_type' => 'spacer',
                'name' => 'Spacer - Medium',
                'slug' => 'spacer-medium',
                'description' => 'Medium height spacer (80px)',
                'category' => 'minimal',
                'sort_order' => 50,
                'is_active' => true,
                'default_settings' => [
                    'height' => '80',
                ],
                'default_content' => [],
            ],
        ];

        foreach ($templates as $template) {
            PageBuilderTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }

        $this->command->info('Page Builder templates seeded successfully!');
    }
}
