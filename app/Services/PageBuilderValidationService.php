<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;

class PageBuilderValidationService
{
    /**
     * Validate section content based on section type
     */
    public function validateSectionContent(string $sectionType, array $content): array
    {
        $rules = $this->getValidationRules($sectionType);

        if (empty($rules)) {
            return ['valid' => true];
        }

        $validator = Validator::make($content, $rules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Get validation rules for specific section type
     */
    protected function getValidationRules(string $sectionType): array
    {
        $rules = [
            'hero' => [
                'title' => 'required|string|max:255',
                'subtitle' => 'nullable|string|max:500',
                'cta_primary_text' => 'nullable|string|max:100',
                'cta_primary_url' => 'nullable|url',
            ],
            'hero_gradient' => [
                'title' => 'required|string|max:255',
                'subtitle' => 'nullable|string|max:500',
            ],
            'hero_video' => [
                'title' => 'required|string|max:255',
                'video_url' => 'required|string',
            ],
            'hero_premium' => [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'feature_cards' => 'nullable|array',
            ],
            'features' => [
                'title' => 'required|string|max:255',
                'features' => 'required|array|min:1',
                'features.*.title' => 'required|string|max:100',
                'features.*.description' => 'nullable|string|max:500',
            ],
            'features_list' => [
                'title' => 'required|string|max:255',
                'features' => 'required|array|min:1',
                'features.*.title' => 'required|string|max:100',
            ],
            'statistics' => [
                'stats' => 'required|array|min:1',
                'stats.*.value' => 'required',
                'stats.*.label' => 'required|string',
            ],
            'testimonials' => [
                'title' => 'nullable|string|max:255',
                'testimonials' => 'required|array|min:1',
                'testimonials.*.name' => 'required|string|max:100',
                'testimonials.*.text' => 'required|string',
            ],
            'pricing' => [
                'title' => 'required|string|max:255',
                'plans' => 'required|array|min:1',
                'plans.*.name' => 'required|string|max:100',
                'plans.*.price' => 'required',
            ],
            'team' => [
                'title' => 'nullable|string|max:255',
                'members' => 'required|array|min:1',
                'members.*.name' => 'required|string|max:100',
                'members.*.position' => 'nullable|string|max:100',
            ],
            'faq' => [
                'title' => 'nullable|string|max:255',
                'faqs' => 'required|array|min:1',
                'faqs.*.question' => 'required|string',
                'faqs.*.answer' => 'required|string',
            ],
            'contact_form' => [
                'title' => 'required|string|max:255',
                'form_action' => 'nullable|url',
            ],
            'gallery' => [
                'title' => 'nullable|string|max:255',
                'images' => 'required|array|min:1',
                'images.*.url' => 'required|string',
            ],
            'cta' => [
                'title' => 'required|string|max:255',
                'cta_text' => 'required|string|max:100',
                'cta_url' => 'required|url',
            ],
        ];

        return $rules[$sectionType] ?? [];
    }

    /**
     * Validate section settings
     */
    public function validateSectionSettings(string $sectionType, array $settings): array
    {
        $rules = $this->getSettingsValidationRules($sectionType);

        if (empty($rules)) {
            return ['valid' => true];
        }

        $validator = Validator::make($settings, $rules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Get validation rules for section settings
     */
    protected function getSettingsValidationRules(string $sectionType): array
    {
        $commonRules = [
            'padding_y' => 'nullable|string',
            'bg_color' => 'nullable|string',
        ];

        $typeSpecificRules = [
            'hero' => [
                'gradient_from' => 'nullable|string',
                'gradient_to' => 'nullable|string',
                'text_align' => 'nullable|in:left,center,right',
            ],
            'features' => [
                'columns' => 'nullable|integer|min:1|max:6',
                'icon_color' => 'nullable|string',
            ],
            'testimonials' => [
                'columns' => 'nullable|integer|min:1|max:4',
                'show_rating' => 'nullable|boolean',
            ],
            'gallery' => [
                'layout' => 'nullable|in:grid,masonry,carousel',
                'columns' => 'nullable|integer|min:1|max:6',
                'enable_lightbox' => 'nullable|boolean',
            ],
        ];

        return array_merge($commonRules, $typeSpecificRules[$sectionType] ?? []);
    }

    /**
     * Get required fields for a section type
     */
    public function getRequiredFields(string $sectionType): array
    {
        $requiredFields = config("pagebuilder.validation.required_fields.{$sectionType}", []);

        return $requiredFields;
    }

    /**
     * Check if validation is enabled
     */
    public function isValidationEnabled(): bool
    {
        return config('pagebuilder.validation.enabled', true);
    }

    /**
     * Check if strict mode is enabled
     */
    public function isStrictMode(): bool
    {
        return config('pagebuilder.validation.strict_mode', false);
    }
}
