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

            // Hero Gradient Template
            [
                'template_type' => 'hero_gradient',
                'name' => 'Hero - Gradient Animated',
                'slug' => 'hero-gradient-animated',
                'description' => 'Hero section with animated gradient and badges',
                'category' => 'animated',
                'sort_order' => 3,
                'is_active' => true,
                'default_settings' => [
                    'gradient_from' => '#667eea',
                    'gradient_to' => '#764ba2',
                    'gradient_direction' => '135deg',
                    'text_align' => 'center',
                    'has_animation' => true,
                ],
                'default_content' => [
                    'badge_text' => 'NEW',
                    'title' => 'Build Something Amazing',
                    'subtitle' => 'The best platform for your next project',
                    'cta_primary_text' => 'Get Started Free',
                    'cta_primary_url' => '/register',
                    'trust_badges' => ['✓ 30-day free trial', '✓ No credit card required'],
                ],
            ],

            // Hero Video Template
            [
                'template_type' => 'hero_video',
                'name' => 'Hero - Video Background',
                'slug' => 'hero-video-bg',
                'description' => 'Hero section with video background',
                'category' => 'modern',
                'sort_order' => 4,
                'is_active' => true,
                'default_settings' => [
                    'video_type' => 'mp4',
                    'overlay_opacity' => '0.5',
                    'overlay_color' => '#000000',
                    'text_align' => 'center',
                    'show_scroll_indicator' => true,
                ],
                'default_content' => [
                    'video_url' => '/videos/hero-bg.mp4',
                    'title' => 'Experience Innovation',
                    'subtitle' => 'Watch your business transform',
                    'cta_primary_text' => 'Watch Demo',
                    'cta_primary_url' => '#demo',
                ],
            ],

            // Features List Template
            [
                'template_type' => 'features_list',
                'name' => 'Features - Detailed List',
                'slug' => 'features-detailed-list',
                'description' => 'Detailed features list with icons and descriptions',
                'category' => 'business',
                'sort_order' => 12,
                'is_active' => true,
                'default_settings' => [
                    'layout' => 'two-column',
                    'icon_style' => 'gradient',
                ],
                'default_content' => [
                    'title' => 'Powerful Features',
                    'subtitle' => 'Everything you need to succeed',
                    'features' => [
                        [
                            'icon' => '⚡',
                            'title' => 'Lightning Fast Performance',
                            'description' => 'Optimized for speed and efficiency',
                            'items' => ['Sub-second load times', 'CDN delivery', 'Smart caching'],
                        ],
                    ],
                ],
            ],

            // Testimonials Template
            [
                'template_type' => 'testimonials',
                'name' => 'Testimonials - 3 Column Grid',
                'slug' => 'testimonials-grid-3',
                'description' => 'Customer testimonials in 3-column grid',
                'category' => 'modern',
                'sort_order' => 60,
                'is_active' => true,
                'default_settings' => [
                    'columns' => 3,
                    'show_rating' => true,
                ],
                'default_content' => [
                    'title' => 'What Our Customers Say',
                    'subtitle' => 'Don\'t just take our word for it',
                    'testimonials' => [
                        [
                            'name' => 'John Doe',
                            'role' => 'CEO',
                            'company' => 'Example Corp',
                            'text' => 'This platform transformed our business!',
                            'rating' => 5,
                            'verified' => true,
                        ],
                    ],
                ],
            ],

            // Pricing Template
            [
                'template_type' => 'pricing',
                'name' => 'Pricing - Modern Cards',
                'slug' => 'pricing-modern-cards',
                'description' => 'Modern pricing table with feature comparison',
                'category' => 'business',
                'sort_order' => 70,
                'is_active' => true,
                'default_settings' => [
                    'billing_cycle' => 'monthly',
                    'highlight_popular' => true,
                ],
                'default_content' => [
                    'title' => 'Simple, Transparent Pricing',
                    'subtitle' => 'Choose the plan that fits your needs',
                    'plans' => [
                        [
                            'name' => 'Starter',
                            'price' => 990,
                            'period' => 'month',
                            'currency' => '฿',
                            'features' => ['10 Projects', '100 GB Storage', 'Basic Support'],
                            'cta_text' => 'Get Started',
                            'cta_url' => '/register',
                        ],
                    ],
                ],
            ],

            // Team Template
            [
                'template_type' => 'team',
                'name' => 'Team - 4 Column Grid',
                'slug' => 'team-grid-4',
                'description' => 'Team members showcase in 4-column grid',
                'category' => 'business',
                'sort_order' => 80,
                'is_active' => true,
                'default_settings' => [
                    'columns' => 4,
                    'show_social' => true,
                ],
                'default_content' => [
                    'title' => 'Meet Our Team',
                    'subtitle' => 'The people behind our success',
                    'members' => [
                        [
                            'name' => 'Jane Smith',
                            'position' => 'CEO & Founder',
                            'bio' => 'Leading with vision and innovation',
                            'image' => '/images/team/jane.jpg',
                            'social' => [
                                'twitter' => 'https://twitter.com/janesmith',
                                'linkedin' => 'https://linkedin.com/in/janesmith',
                            ],
                        ],
                    ],
                ],
            ],

            // FAQ Template
            [
                'template_type' => 'faq',
                'name' => 'FAQ - Accordion Style',
                'slug' => 'faq-accordion',
                'description' => 'Frequently asked questions with accordion',
                'category' => 'modern',
                'sort_order' => 90,
                'is_active' => true,
                'default_settings' => [
                    'layout' => 'single',
                    'accordion_style' => 'default',
                ],
                'default_content' => [
                    'title' => 'Frequently Asked Questions',
                    'subtitle' => 'Everything you need to know',
                    'faqs' => [
                        [
                            'question' => 'How do I get started?',
                            'answer' => 'Simply click the Get Started button and follow the onboarding process.',
                        ],
                        [
                            'question' => 'What payment methods do you accept?',
                            'answer' => 'We accept all major credit cards, PayPal, and bank transfers.',
                        ],
                    ],
                ],
            ],

            // Contact Form Template
            [
                'template_type' => 'contact_form',
                'name' => 'Contact - Split Layout',
                'slug' => 'contact-split-layout',
                'description' => 'Contact form with split layout and info',
                'category' => 'business',
                'sort_order' => 100,
                'is_active' => true,
                'default_settings' => [
                    'layout' => 'split',
                    'show_contact_info' => true,
                    'enable_privacy_checkbox' => true,
                ],
                'default_content' => [
                    'title' => 'Get In Touch',
                    'subtitle' => 'We\'d love to hear from you',
                    'contact_email' => 'hello@example.com',
                    'contact_phone' => '+66 2-123-4567',
                    'contact_address' => 'Bangkok, Thailand',
                    'business_hours' => 'Mon-Fri: 9AM-6PM',
                ],
            ],

            // Gallery Template
            [
                'template_type' => 'gallery',
                'name' => 'Gallery - Grid Layout',
                'slug' => 'gallery-grid',
                'description' => 'Image gallery with lightbox',
                'category' => 'modern',
                'sort_order' => 110,
                'is_active' => true,
                'default_settings' => [
                    'layout' => 'grid',
                    'columns' => 3,
                    'enable_lightbox' => true,
                ],
                'default_content' => [
                    'title' => 'Our Gallery',
                    'subtitle' => 'Explore our work',
                    'images' => [
                        [
                            'url' => '/images/gallery/1.jpg',
                            'title' => 'Project 1',
                            'description' => 'Beautiful design',
                        ],
                    ],
                ],
            ],

            // Image Text Template
            [
                'template_type' => 'image_text',
                'name' => 'Image + Text - Split',
                'slug' => 'image-text-split',
                'description' => 'Image and text side by side',
                'category' => 'modern',
                'sort_order' => 120,
                'is_active' => true,
                'default_settings' => [
                    'image_position' => 'left',
                    'image_size' => '50',
                    'vertical_align' => 'center',
                ],
                'default_content' => [
                    'image' => '/images/about.jpg',
                    'title' => 'About Our Company',
                    'subtitle' => 'Building the future',
                    'description' => 'We are passionate about creating innovative solutions.',
                    'features' => ['Industry leaders', '10+ years experience', '1000+ happy clients'],
                    'cta_text' => 'Learn More',
                    'cta_url' => '/about',
                ],
            ],

            // Custom HTML Template
            [
                'template_type' => 'custom_html',
                'name' => 'Custom HTML Section',
                'slug' => 'custom-html-section',
                'description' => 'Custom HTML, CSS, and JavaScript',
                'category' => 'creative',
                'sort_order' => 130,
                'is_active' => true,
                'default_settings' => [
                    'container_class' => 'container mx-auto px-4',
                    'padding_y' => 'py-20',
                    'enable_scripts' => false,
                    'enable_styles' => false,
                ],
                'default_content' => [
                    'html' => '<div class="text-center"><h2 class="text-3xl font-bold">Custom Content</h2><p class="mt-4">Add your custom HTML here</p></div>',
                ],
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
