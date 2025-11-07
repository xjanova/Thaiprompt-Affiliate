<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class IconHelper
{
    /**
     * Get icon URL
     *
     * @param string $name Icon name (with or without extension)
     * @param string $category Category: system, theme, custom, social, flags
     * @param string|null $default Default icon if not found
     * @return string
     */
    public static function url(string $name, string $category = 'system', ?string $default = null): string
    {
        // Add extension if not provided
        if (!str_contains($name, '.')) {
            // Try to find file with any extension
            $extensions = ['svg', 'png', 'jpg', 'jpeg', 'webp'];
            foreach ($extensions as $ext) {
                $path = "icons/{$category}/{$name}.{$ext}";
                if (File::exists(public_path($path))) {
                    return asset($path);
                }
            }

            // If not found in public, try storage
            foreach ($extensions as $ext) {
                $path = "icons/{$category}/{$name}.{$ext}";
                if (Storage::disk('public')->exists($path)) {
                    return Storage::url($path);
                }
            }
        } else {
            // Check with provided extension
            $path = "icons/{$category}/{$name}";
            if (File::exists(public_path($path))) {
                return asset($path);
            }

            if (Storage::disk('public')->exists($path)) {
                return Storage::url($path);
            }
        }

        // Return default or placeholder
        return $default ?? self::placeholder();
    }

    /**
     * Get icon path
     *
     * @param string $name
     * @param string $category
     * @return string|null
     */
    public static function path(string $name, string $category = 'system'): ?string
    {
        if (!str_contains($name, '.')) {
            $extensions = ['svg', 'png', 'jpg', 'jpeg', 'webp'];
            foreach ($extensions as $ext) {
                $path = public_path("icons/{$category}/{$name}.{$ext}");
                if (File::exists($path)) {
                    return $path;
                }
            }
        } else {
            $path = public_path("icons/{$category}/{$name}");
            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Check if icon exists
     *
     * @param string $name
     * @param string $category
     * @return bool
     */
    public static function exists(string $name, string $category = 'system'): bool
    {
        return self::path($name, $category) !== null;
    }

    /**
     * Get all icons in a category
     *
     * @param string $category
     * @return array
     */
    public static function list(string $category = 'system'): array
    {
        $icons = [];
        $path = public_path("icons/{$category}");

        if (File::isDirectory($path)) {
            $files = File::files($path);
            foreach ($files as $file) {
                if (in_array($file->getExtension(), ['svg', 'png', 'jpg', 'jpeg', 'webp'])) {
                    $icons[] = [
                        'name' => $file->getFilenameWithoutExtension(),
                        'filename' => $file->getFilename(),
                        'path' => $file->getPathname(),
                        'url' => asset("icons/{$category}/" . $file->getFilename()),
                        'extension' => $file->getExtension(),
                        'size' => $file->getSize(),
                    ];
                }
            }
        }

        return $icons;
    }

    /**
     * Get SVG content
     *
     * @param string $name
     * @param string $category
     * @return string|null
     */
    public static function svg(string $name, string $category = 'system'): ?string
    {
        $path = self::path($name . '.svg', $category);

        if ($path && File::exists($path)) {
            return File::get($path);
        }

        return null;
    }

    /**
     * Get inline SVG with custom attributes
     *
     * @param string $name
     * @param string $category
     * @param array $attributes
     * @return string
     */
    public static function inline(string $name, string $category = 'system', array $attributes = []): string
    {
        $svg = self::svg($name, $category);

        if (!$svg) {
            return self::placeholderSvg($attributes);
        }

        // Add or modify attributes
        if (!empty($attributes)) {
            // Parse SVG
            $svg = preg_replace_callback('/<svg([^>]*)>/', function ($matches) use ($attributes) {
                $svgTag = $matches[1];

                foreach ($attributes as $key => $value) {
                    // Check if attribute exists
                    if (preg_match("/{$key}=[\"']([^\"']*)[\"']/", $svgTag)) {
                        // Replace existing attribute
                        $svgTag = preg_replace("/{$key}=[\"']([^\"']*)[\"']/", "{$key}=\"{$value}\"", $svgTag);
                    } else {
                        // Add new attribute
                        $svgTag .= " {$key}=\"{$value}\"";
                    }
                }

                return '<svg' . $svgTag . '>';
            }, $svg);
        }

        return $svg;
    }

    /**
     * Upload icon
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $category
     * @param string|null $name Custom name (optional)
     * @return string|false Filename on success, false on failure
     */
    public static function upload($file, string $category = 'custom', ?string $name = null)
    {
        try {
            $allowedExtensions = ['svg', 'png', 'jpg', 'jpeg', 'webp'];
            $extension = strtolower($file->getClientOriginalExtension());

            if (!in_array($extension, $allowedExtensions)) {
                return false;
            }

            // Generate filename
            if ($name) {
                $filename = $name . '.' . $extension;
            } else {
                $filename = time() . '_' . uniqid() . '.' . $extension;
            }

            // Ensure directory exists
            $directory = public_path("icons/{$category}");
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            // Move file
            $file->move($directory, $filename);

            return $filename;
        } catch (\Exception $e) {
            \Log::error('Icon upload failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete icon
     *
     * @param string $name
     * @param string $category
     * @return bool
     */
    public static function delete(string $name, string $category = 'custom'): bool
    {
        $path = self::path($name, $category);

        if ($path && File::exists($path)) {
            return File::delete($path);
        }

        return false;
    }

    /**
     * Get placeholder icon URL
     *
     * @return string
     */
    public static function placeholder(): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode(self::placeholderSvg());
    }

    /**
     * Get placeholder SVG
     *
     * @param array $attributes
     * @return string
     */
    public static function placeholderSvg(array $attributes = []): string
    {
        $width = $attributes['width'] ?? '24';
        $height = $attributes['height'] ?? '24';
        $class = $attributes['class'] ?? '';

        return <<<SVG
<svg width="{$width}" height="{$height}" class="{$class}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <rect width="24" height="24" fill="#E5E7EB"/>
    <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
SVG;
    }

    /**
     * Get all categories
     *
     * @return array
     */
    public static function categories(): array
    {
        return [
            'system' => __('System Icons'),
            'theme' => __('Theme Icons'),
            'custom' => __('Custom Icons'),
            'social' => __('Social Media'),
            'flags' => __('Country Flags'),
        ];
    }
}
