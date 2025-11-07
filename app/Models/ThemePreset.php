<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThemePreset extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'category',
        'config',
        'preview_image',
        'is_featured',
        'usage_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'config' => 'array',
        'is_featured' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Increment usage count
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
        return $this;
    }

    /**
     * Get featured presets
     */
    public static function getFeatured($limit = 6)
    {
        return self::where('is_featured', true)
            ->orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get presets by category
     */
    public static function getByCategory($category, $limit = null)
    {
        $query = self::where('category', $category)
            ->orderBy('usage_count', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Convert preset to theme
     */
    public function toTheme($displayName = null, $isDefault = false)
    {
        $theme = new Theme();
        $theme->name = $this->name . '_' . time();
        $theme->display_name = $displayName ?? $this->display_name;
        $theme->description = $this->description;
        $theme->is_default = $isDefault;
        $theme->is_active = true;

        // Apply configuration
        if ($this->config) {
            foreach ($this->config as $key => $value) {
                if (in_array($key, $theme->getFillable())) {
                    $theme->$key = $value;
                }
            }
        }

        $theme->save();
        $this->incrementUsage();

        return $theme;
    }

    /**
     * Default presets
     */
    public static function defaultPresets()
    {
        return [
            // Line OA Default
            [
                'name' => 'line_oa_default',
                'display_name' => 'Line OA (Default)',
                'description' => 'ธีมแบบ Line Official Account สีเขียว Line สดใส',
                'category' => 'professional',
                'is_featured' => true,
                'config' => Theme::defaultConfig(),
            ],

            // Ocean Blue
            [
                'name' => 'ocean_blue',
                'display_name' => 'Ocean Blue',
                'description' => 'ธีมสีน้ำเงินมหาสมุทร สงบ สบายตา',
                'category' => 'colorful',
                'is_featured' => true,
                'config' => array_merge(Theme::defaultConfig(), [
                    'colors' => array_merge(Theme::defaultConfig()['colors'], [
                        'primary' => '#0EA5E9',
                        'primary-dark' => '#0284C7',
                        'primary-light' => '#38BDF8',
                        'secondary' => '#06B6D4',
                        'accent' => '#F59E0B',
                    ]),
                ]),
            ],

            // Sunset Orange
            [
                'name' => 'sunset_orange',
                'display_name' => 'Sunset Orange',
                'description' => 'ธีมสีส้มพระอาทิตย์ตก อบอุ่น พลังงานบวก',
                'category' => 'colorful',
                'is_featured' => true,
                'config' => array_merge(Theme::defaultConfig(), [
                    'colors' => array_merge(Theme::defaultConfig()['colors'], [
                        'primary' => '#F97316',
                        'primary-dark' => '#EA580C',
                        'primary-light' => '#FB923C',
                        'secondary' => '#EF4444',
                        'accent' => '#FBBF24',
                    ]),
                ]),
            ],

            // Purple Dream
            [
                'name' => 'purple_dream',
                'display_name' => 'Purple Dream',
                'description' => 'ธีมสีม่วงฝัน หรูหรา สง่างาม',
                'category' => 'colorful',
                'is_featured' => true,
                'config' => array_merge(Theme::defaultConfig(), [
                    'colors' => array_merge(Theme::defaultConfig()['colors'], [
                        'primary' => '#A855F7',
                        'primary-dark' => '#9333EA',
                        'primary-light' => '#C084FC',
                        'secondary' => '#EC4899',
                        'accent' => '#F59E0B',
                    ]),
                ]),
            ],

            // Minimal Dark
            [
                'name' => 'minimal_dark',
                'display_name' => 'Minimal Dark',
                'description' => 'ธีมมืดแบบมินิมอล เน้นเนื้อหา สบายตา',
                'category' => 'minimal',
                'is_featured' => true,
                'config' => array_merge(Theme::defaultConfig(), [
                    'colors' => array_merge(Theme::defaultConfig()['colors'], [
                        'primary' => '#6B7280',
                        'primary-dark' => '#4B5563',
                        'primary-light' => '#9CA3AF',
                        'secondary' => '#374151',
                        'accent' => '#F59E0B',
                    ]),
                ]),
            ],

            // Forest Green
            [
                'name' => 'forest_green',
                'display_name' => 'Forest Green',
                'description' => 'ธีมสีเขียวป่า ผ่อนคลาย ธรรมชาติ',
                'category' => 'colorful',
                'is_featured' => false,
                'config' => array_merge(Theme::defaultConfig(), [
                    'colors' => array_merge(Theme::defaultConfig()['colors'], [
                        'primary' => '#10B981',
                        'primary-dark' => '#059669',
                        'primary-light' => '#34D399',
                        'secondary' => '#14B8A6',
                        'accent' => '#F59E0B',
                    ]),
                ]),
            ],
        ];
    }
}
