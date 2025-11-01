<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisualBuilderController extends Controller
{
    /**
     * Display the visual builder interface
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 'home');
        $sections = PageSection::getPageSections($page, false);
        $componentLibrary = $this->getComponentLibrary();

        return view('admin.visual-builder.index', compact('page', 'sections', 'componentLibrary'));
    }

    /**
     * Get all available components
     */
    public function getComponents()
    {
        return response()->json($this->getComponentLibrary());
    }

    /**
     * Get component library with templates
     */
    private function getComponentLibrary()
    {
        return [
            'hero' => [
                'name' => 'Hero Section',
                'icon' => '🎯',
                'category' => 'Header',
                'templates' => [
                    'default' => [
                        'name' => 'Default Hero',
                        'preview' => '/images/templates/hero-default.jpg',
                        'content' => [
                            'title' => 'Welcome to Our Platform',
                            'subtitle' => 'Build Amazing Websites',
                            'description' => 'Create professional websites with our powerful visual builder',
                            'cta_text' => 'Get Started',
                            'cta_link' => '/register',
                        ],
                        'styles' => [
                            'bg_type' => 'gradient',
                            'bg_gradient_from' => '#667eea',
                            'bg_gradient_to' => '#764ba2',
                            'text_color' => '#ffffff',
                            'padding_y' => '120',
                        ],
                    ],
                    'centered' => [
                        'name' => 'Centered Hero',
                        'preview' => '/images/templates/hero-centered.jpg',
                        'content' => [
                            'title' => 'Your Success Starts Here',
                            'subtitle' => 'Join Thousands of Happy Users',
                            'description' => 'Everything you need to succeed online',
                            'cta_text' => 'Start Free Trial',
                            'cta_link' => '/register',
                        ],
                        'styles' => [
                            'bg_type' => 'gradient',
                            'bg_gradient_from' => '#f093fb',
                            'bg_gradient_to' => '#f5576c',
                            'text_color' => '#ffffff',
                            'padding_y' => '160',
                            'text_align' => 'center',
                        ],
                    ],
                ],
            ],
            'features' => [
                'name' => 'Features Grid',
                'icon' => '✨',
                'category' => 'Content',
                'templates' => [
                    '3-column' => [
                        'name' => '3 Column Features',
                        'preview' => '/images/templates/features-3col.jpg',
                        'content' => [
                            'title' => 'Our Features',
                            'subtitle' => 'Everything you need',
                            'items' => [
                                [
                                    'icon' => 'fa-rocket',
                                    'title' => 'Fast Performance',
                                    'description' => 'Lightning-fast loading speeds',
                                ],
                                [
                                    'icon' => 'fa-shield',
                                    'title' => 'Secure & Safe',
                                    'description' => 'Bank-level security',
                                ],
                                [
                                    'icon' => 'fa-chart-line',
                                    'title' => 'Analytics',
                                    'description' => 'Detailed insights',
                                ],
                            ],
                        ],
                        'styles' => [
                            'columns' => 3,
                            'gap' => '24',
                            'padding_y' => '80',
                        ],
                    ],
                    '4-column' => [
                        'name' => '4 Column Features',
                        'preview' => '/images/templates/features-4col.jpg',
                        'content' => [
                            'title' => 'Why Choose Us',
                            'subtitle' => 'The best features',
                            'items' => [
                                ['icon' => 'fa-rocket', 'title' => 'Fast', 'description' => 'Lightning speed'],
                                ['icon' => 'fa-shield', 'title' => 'Secure', 'description' => 'Bank-level security'],
                                ['icon' => 'fa-chart-line', 'title' => 'Analytics', 'description' => 'Detailed insights'],
                                ['icon' => 'fa-headset', 'title' => 'Support', 'description' => '24/7 support'],
                            ],
                        ],
                        'styles' => [
                            'columns' => 4,
                            'gap' => '20',
                            'padding_y' => '80',
                        ],
                    ],
                ],
            ],
            'stats' => [
                'name' => 'Statistics Counter',
                'icon' => '📊',
                'category' => 'Content',
                'templates' => [
                    'default' => [
                        'name' => 'Stats Counter',
                        'preview' => '/images/templates/stats-default.jpg',
                        'content' => [
                            'title' => 'By The Numbers',
                            'items' => [
                                ['number' => '10k+', 'label' => 'Active Users'],
                                ['number' => '500+', 'label' => 'Projects'],
                                ['number' => '99%', 'label' => 'Satisfaction'],
                                ['number' => '24/7', 'label' => 'Support'],
                            ],
                        ],
                        'styles' => [
                            'bg_color' => '#f9fafb',
                            'columns' => 4,
                            'padding_y' => '60',
                        ],
                    ],
                ],
            ],
            'faq' => [
                'name' => 'FAQ Accordion',
                'icon' => '❓',
                'category' => 'Content',
                'templates' => [
                    'default' => [
                        'name' => 'FAQ Section',
                        'preview' => '/images/templates/faq-default.jpg',
                        'content' => [
                            'title' => 'Frequently Asked Questions',
                            'subtitle' => 'Got questions? We have answers',
                            'items' => [
                                [
                                    'question' => 'How do I get started?',
                                    'answer' => 'Simply sign up for an account and follow our onboarding wizard.',
                                ],
                                [
                                    'question' => 'What payment methods do you accept?',
                                    'answer' => 'We accept all major credit cards, PayPal, and bank transfers.',
                                ],
                                [
                                    'question' => 'Can I cancel anytime?',
                                    'answer' => 'Yes, you can cancel your subscription at any time with no penalties.',
                                ],
                            ],
                        ],
                        'styles' => [
                            'max_width' => '800',
                            'padding_y' => '80',
                        ],
                    ],
                ],
            ],
            'cta' => [
                'name' => 'Call to Action',
                'icon' => '🚀',
                'category' => 'Marketing',
                'templates' => [
                    'banner' => [
                        'name' => 'CTA Banner',
                        'preview' => '/images/templates/cta-banner.jpg',
                        'content' => [
                            'title' => 'Ready to Get Started?',
                            'subtitle' => 'Join thousands of satisfied users today',
                            'cta_text' => 'Sign Up Now',
                            'cta_link' => '/register',
                        ],
                        'styles' => [
                            'bg_type' => 'gradient',
                            'bg_gradient_from' => '#667eea',
                            'bg_gradient_to' => '#764ba2',
                            'text_color' => '#ffffff',
                            'padding_y' => '80',
                            'text_align' => 'center',
                        ],
                    ],
                ],
            ],
            'testimonials' => [
                'name' => 'Testimonials',
                'icon' => '💬',
                'category' => 'Social Proof',
                'templates' => [
                    'cards' => [
                        'name' => 'Testimonial Cards',
                        'preview' => '/images/templates/testimonials-cards.jpg',
                        'content' => [
                            'title' => 'What Our Customers Say',
                            'items' => [
                                [
                                    'name' => 'John Doe',
                                    'role' => 'CEO, Company Inc',
                                    'avatar' => '',
                                    'rating' => 5,
                                    'text' => 'Amazing product! Highly recommended.',
                                ],
                                [
                                    'name' => 'Jane Smith',
                                    'role' => 'Marketing Manager',
                                    'avatar' => '',
                                    'rating' => 5,
                                    'text' => 'Best decision we ever made for our business.',
                                ],
                            ],
                        ],
                        'styles' => [
                            'columns' => 2,
                            'gap' => '24',
                            'padding_y' => '80',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Store a new section
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'page' => 'required|string',
            'component_type' => 'required|string',
            'name' => 'nullable|string|max:255',
            'content' => 'nullable|array',
            'styles' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        // Get max order for this page
        $maxOrder = PageSection::forPage($validated['page'])->max('order') ?? -1;

        $section = PageSection::create([
            ...$validated,
            'order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'section' => $section,
        ]);
    }

    /**
     * Update a section
     */
    public function update(Request $request, PageSection $section)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'content' => 'nullable|array',
            'styles' => 'nullable|array',
            'settings' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $section->update($validated);

        return response()->json([
            'success' => true,
            'section' => $section->fresh(),
        ]);
    }

    /**
     * Delete a section
     */
    public function destroy(PageSection $section)
    {
        $section->delete();

        return response()->json([
            'success' => true,
            'message' => 'Section deleted successfully',
        ]);
    }

    /**
     * Duplicate a section
     */
    public function duplicate(PageSection $section)
    {
        $newSection = $section->duplicate();

        return response()->json([
            'success' => true,
            'section' => $newSection,
        ]);
    }

    /**
     * Reorder sections
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'page' => 'required|string',
            'sections' => 'required|array',
            'sections.*' => 'required|integer|exists:page_sections,id',
        ]);

        PageSection::reorder($validated['page'], $validated['sections']);

        return response()->json([
            'success' => true,
            'message' => 'Sections reordered successfully',
        ]);
    }

    /**
     * Toggle section visibility
     */
    public function toggle(PageSection $section)
    {
        $section->update(['is_active' => !$section->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $section->is_active,
        ]);
    }

    /**
     * Export sections as template
     */
    public function export(Request $request)
    {
        $page = $request->get('page', 'home');
        $sections = PageSection::getPageSections($page, false);

        $template = [
            'name' => $request->get('name', 'My Template'),
            'page' => $page,
            'sections' => $sections->map(function ($section) {
                return [
                    'component_type' => $section->component_type,
                    'name' => $section->name,
                    'content' => $section->content,
                    'styles' => $section->styles,
                    'settings' => $section->settings,
                ];
            }),
            'exported_at' => now()->toDateTimeString(),
        ];

        return response()->json([
            'success' => true,
            'template' => $template,
        ]);
    }

    /**
     * Import sections from template
     */
    public function import(Request $request)
    {
        $validated = $request->validate([
            'page' => 'required|string',
            'template' => 'required|array',
            'template.sections' => 'required|array',
            'replace_existing' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            // Delete existing sections if replace_existing is true
            if ($validated['replace_existing'] ?? false) {
                PageSection::forPage($validated['page'])->delete();
            }

            $order = PageSection::forPage($validated['page'])->max('order') ?? -1;

            foreach ($validated['template']['sections'] as $sectionData) {
                $order++;
                PageSection::create([
                    'page' => $validated['page'],
                    'component_type' => $sectionData['component_type'],
                    'name' => $sectionData['name'],
                    'order' => $order,
                    'content' => $sectionData['content'] ?? [],
                    'styles' => $sectionData['styles'] ?? [],
                    'settings' => $sectionData['settings'] ?? [],
                    'is_active' => true,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Template imported successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to import template: ' . $e->getMessage(),
            ], 500);
        }
    }
}
