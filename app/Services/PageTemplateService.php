<?php

namespace App\Services;

use App\Models\PageBuilderTemplate;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class PageTemplateService
{
    /**
     * Get all active templates
     */
    public function getAllTemplates(): Collection
    {
        return PageBuilderTemplate::active()->ordered()->get();
    }

    /**
     * Get templates by type
     */
    public function getTemplatesByType(string $type): Collection
    {
        return PageBuilderTemplate::active()
            ->byType($type)
            ->ordered()
            ->get();
    }

    /**
     * Get templates by category
     */
    public function getTemplatesByCategory(string $category): Collection
    {
        return PageBuilderTemplate::active()
            ->byCategory($category)
            ->ordered()
            ->get();
    }

    /**
     * Create a new template
     */
    public function createTemplate(array $data): PageBuilderTemplate
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return PageBuilderTemplate::create($data);
    }

    /**
     * Update template
     */
    public function updateTemplate(PageBuilderTemplate $template, array $data): PageBuilderTemplate
    {
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $template->update($data);

        return $template->fresh();
    }

    /**
     * Delete template
     */
    public function deleteTemplate(PageBuilderTemplate $template): bool
    {
        return $template->delete();
    }

    /**
     * Seed default templates
     */
    public function seedDefaultTemplates(): void
    {
        $defaultTemplates = $this->getDefaultTemplatesData();

        foreach ($defaultTemplates as $template) {
            PageBuilderTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }

    /**
     * Get default templates data
     */
    protected function getDefaultTemplatesData(): array
    {
        return [
            // Hero Templates
            [
                'template_type' => 'hero',
                'name' => 'Hero - Modern Gradient',
                'slug' => 'hero-modern-gradient',
                'description' => 'Modern hero section with animated gradient background',
                'category' => 'gradient',
                'sort_order' => 1,
                'default_settings' => [
                    'background' => 'gradient',
                    'gradient_from' => '#667eea',
                    'gradient_to' => '#764ba2',
                    'text_align' => 'center',
                    'padding_y' => 'py-32',
                    'animation' => 'fade-in',
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
                'name' => 'Hero - Minimal',
                'slug' => 'hero-minimal',
                'description' => 'Clean and minimal hero section',
                'category' => 'minimal',
                'sort_order' => 2,
                'default_settings' => [
                    'background' => 'solid',
                    'background_color' => '#ffffff',
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
                'name' => 'Features - Grid 3 Columns',
                'slug' => 'features-grid-3',
                'description' => '3-column feature grid with icons',
                'category' => 'modern',
                'sort_order' => 10,
                'default_settings' => [
                    'columns' => 3,
                    'icon_style' => 'rounded',
                    'icon_color' => '#667eea',
                    'card_style' => 'bordered',
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

            // Statistics Template
            [
                'template_type' => 'statistics',
                'name' => 'Statistics - Counter',
                'slug' => 'statistics-counter',
                'description' => 'Animated statistics counters',
                'category' => 'animated',
                'sort_order' => 20,
                'default_settings' => [
                    'background' => 'gradient',
                    'gradient_from' => '#667eea',
                    'gradient_to' => '#764ba2',
                    'text_color' => 'white',
                    'animation' => 'count-up',
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

            // CTA Template
            [
                'template_type' => 'cta',
                'name' => 'CTA - Centered',
                'slug' => 'cta-centered',
                'description' => 'Centered call-to-action section',
                'category' => 'modern',
                'sort_order' => 30,
                'default_settings' => [
                    'background' => 'gradient',
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
        ];
    }
}
