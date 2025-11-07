<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Theme extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_default',
        'is_system',
        'is_active',
        'colors',
        'typography',
        'spacing',
        'borders',
        'shadows',
        'animations',
        'custom_css',
        'dark_colors',
        'preview_light',
        'preview_dark',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'colors' => 'array',
        'typography' => 'array',
        'spacing' => 'array',
        'borders' => 'array',
        'shadows' => 'array',
        'animations' => 'array',
        'custom_css' => 'array',
        'dark_colors' => 'array',
    ];

    /**
     * Get the creator of the theme
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the last updater of the theme
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get users using this theme
     */
    public function users()
    {
        return $this->hasMany(UserTheme::class);
    }

    /**
     * Get active users count
     */
    public function getActiveUsersCountAttribute()
    {
        return $this->users()->where('is_active', true)->count();
    }

    /**
     * Get default theme
     */
    public static function getDefault()
    {
        return self::where('is_default', true)->where('is_active', true)->first()
            ?? self::where('is_system', true)->where('is_active', true)->first()
            ?? self::where('is_active', true)->first();
    }

    /**
     * Set as default theme
     */
    public function setAsDefault()
    {
        // Remove default from all themes
        self::where('is_default', true)->update(['is_default' => false]);

        // Set this theme as default
        $this->is_default = true;
        $this->save();

        return $this;
    }

    /**
     * Get CSS variables for this theme
     */
    public function getCssVariables($mode = 'light')
    {
        $colors = $mode === 'dark' ? ($this->dark_colors ?? $this->colors) : $this->colors;
        $variables = [];

        // Colors
        if ($colors) {
            foreach ($colors as $key => $value) {
                $variables["--color-{$key}"] = $value;
            }
        }

        // Typography
        if ($this->typography) {
            foreach ($this->typography as $key => $value) {
                $variables["--font-{$key}"] = $value;
            }
        }

        // Spacing
        if ($this->spacing) {
            foreach ($this->spacing as $key => $value) {
                $variables["--spacing-{$key}"] = $value;
            }
        }

        // Borders
        if ($this->borders) {
            foreach ($this->borders as $key => $value) {
                $variables["--border-{$key}"] = $value;
            }
        }

        // Shadows
        if ($this->shadows) {
            foreach ($this->shadows as $key => $value) {
                $variables["--shadow-{$key}"] = $value;
            }
        }

        return $variables;
    }

    /**
     * Generate CSS string
     */
    public function generateCss($mode = 'light')
    {
        $variables = $this->getCssVariables($mode);
        $css = ":root {\n";

        foreach ($variables as $key => $value) {
            $css .= "  {$key}: {$value};\n";
        }

        $css .= "}\n";

        // Add custom CSS if exists
        if ($this->custom_css && isset($this->custom_css[$mode])) {
            $css .= "\n" . $this->custom_css[$mode];
        }

        return $css;
    }

    /**
     * Default theme configuration
     */
    public static function defaultConfig()
    {
        return [
            'colors' => [
                // Primary colors (Line OA style)
                'primary' => '#06C755', // Line green
                'primary-dark' => '#00B900',
                'primary-light' => '#06D863',

                // Secondary colors
                'secondary' => '#00B2FF',
                'secondary-dark' => '#0099E5',
                'secondary-light' => '#33C2FF',

                // Accent
                'accent' => '#FF6B00',
                'accent-dark' => '#E55D00',
                'accent-light' => '#FF8533',

                // Neutral colors
                'gray-50' => '#F9FAFB',
                'gray-100' => '#F3F4F6',
                'gray-200' => '#E5E7EB',
                'gray-300' => '#D1D5DB',
                'gray-400' => '#9CA3AF',
                'gray-500' => '#6B7280',
                'gray-600' => '#4B5563',
                'gray-700' => '#374151',
                'gray-800' => '#1F2937',
                'gray-900' => '#111827',

                // Semantic colors
                'success' => '#10B981',
                'warning' => '#F59E0B',
                'error' => '#EF4444',
                'info' => '#3B82F6',

                // Background
                'bg-primary' => '#FFFFFF',
                'bg-secondary' => '#F9FAFB',
                'bg-tertiary' => '#F3F4F6',

                // Text
                'text-primary' => '#111827',
                'text-secondary' => '#6B7280',
                'text-tertiary' => '#9CA3AF',
            ],

            'dark_colors' => [
                // Primary colors (ปรับให้สบายตาในโหมดมืด)
                'primary' => '#06D863',
                'primary-dark' => '#06C755',
                'primary-light' => '#3DE580',

                // Secondary colors
                'secondary' => '#33C2FF',
                'secondary-dark' => '#00B2FF',
                'secondary-light' => '#66D4FF',

                // Accent
                'accent' => '#FF8533',
                'accent-dark' => '#FF6B00',
                'accent-light' => '#FFA366',

                // Neutral colors (inverted)
                'gray-50' => '#1F2937',
                'gray-100' => '#111827',
                'gray-200' => '#374151',
                'gray-300' => '#4B5563',
                'gray-400' => '#6B7280',
                'gray-500' => '#9CA3AF',
                'gray-600' => '#D1D5DB',
                'gray-700' => '#E5E7EB',
                'gray-800' => '#F3F4F6',
                'gray-900' => '#F9FAFB',

                // Semantic colors (ปรับความสว่าง)
                'success' => '#34D399',
                'warning' => '#FBBF24',
                'error' => '#F87171',
                'info' => '#60A5FA',

                // Background
                'bg-primary' => '#111827',
                'bg-secondary' => '#1F2937',
                'bg-tertiary' => '#374151',

                // Text
                'text-primary' => '#F9FAFB',
                'text-secondary' => '#D1D5DB',
                'text-tertiary' => '#9CA3AF',
            ],

            'typography' => [
                'family-primary' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif',
                'family-secondary' => 'Inter, -apple-system, BlinkMacSystemFont, sans-serif',
                'family-mono' => '"Fira Code", "Courier New", monospace',

                'size-xs' => '0.75rem',
                'size-sm' => '0.875rem',
                'size-base' => '1rem',
                'size-lg' => '1.125rem',
                'size-xl' => '1.25rem',
                'size-2xl' => '1.5rem',
                'size-3xl' => '1.875rem',
                'size-4xl' => '2.25rem',

                'weight-light' => '300',
                'weight-normal' => '400',
                'weight-medium' => '500',
                'weight-semibold' => '600',
                'weight-bold' => '700',
            ],

            'spacing' => [
                '0' => '0',
                '1' => '0.25rem',
                '2' => '0.5rem',
                '3' => '0.75rem',
                '4' => '1rem',
                '5' => '1.25rem',
                '6' => '1.5rem',
                '8' => '2rem',
                '10' => '2.5rem',
                '12' => '3rem',
                '16' => '4rem',
                '20' => '5rem',
                '24' => '6rem',
            ],

            'borders' => [
                'radius-none' => '0',
                'radius-sm' => '0.125rem',
                'radius-base' => '0.375rem',
                'radius-md' => '0.5rem',
                'radius-lg' => '0.75rem',
                'radius-xl' => '1rem',
                'radius-2xl' => '1.5rem',
                'radius-full' => '9999px',

                'width-thin' => '1px',
                'width-base' => '2px',
                'width-thick' => '4px',
            ],

            'shadows' => [
                'sm' => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                'base' => '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)',
                'md' => '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
                'lg' => '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                'xl' => '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                '2xl' => '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
                'inner' => 'inset 0 2px 4px 0 rgba(0, 0, 0, 0.06)',
                'none' => 'none',
            ],

            'animations' => [
                'duration-fast' => '150ms',
                'duration-base' => '300ms',
                'duration-slow' => '500ms',
                'easing' => 'cubic-bezier(0.4, 0, 0.2, 1)',
            ],
        ];
    }
}
