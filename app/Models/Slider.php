<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'image',
        'link',
        'order',
        'is_active',
        'media_type',
        'video_url',
        'video_file',
        'video_type',
        'video_settings',
        'text_overlay',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
            'video_settings' => 'array',
            'text_overlay' => 'array',
        ];
    }

    /**
     * Scope a query to only include active sliders.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order sliders by order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Get default video settings
     */
    public function getDefaultVideoSettings(): array
    {
        return [
            'autoplay' => true,
            'muted' => true,
            'loop' => false,
            'controls' => false,
        ];
    }

    /**
     * Get merged video settings with defaults
     */
    public function getVideoSettingsAttribute($value): array
    {
        $settings = $value ? json_decode($value, true) : [];
        return array_merge($this->getDefaultVideoSettings(), $settings);
    }

    /**
     * Check if this is a video slide
     */
    public function isVideo(): bool
    {
        return $this->media_type === 'video';
    }

    /**
     * Check if this is an image slide
     */
    public function isImage(): bool
    {
        return $this->media_type === 'image';
    }

    /**
     * Get the embed URL for video (YouTube, Vimeo)
     */
    public function getVideoEmbedUrl(): ?string
    {
        if (!$this->video_url) {
            return null;
        }

        switch ($this->video_type) {
            case 'youtube':
                // Extract YouTube video ID and create embed URL
                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $this->video_url, $matches);
                if (isset($matches[1])) {
                    $autoplay = $this->video_settings['autoplay'] ? '1' : '0';
                    $muted = $this->video_settings['muted'] ? '1' : '0';
                    $loop = $this->video_settings['loop'] ? '1' : '0';
                    $controls = $this->video_settings['controls'] ? '1' : '0';
                    return "https://www.youtube.com/embed/{$matches[1]}?autoplay={$autoplay}&mute={$muted}&loop={$loop}&controls={$controls}&rel=0&modestbranding=1&playsinline=1&enablejsapi=1";
                }
                break;

            case 'vimeo':
                // Extract Vimeo video ID and create embed URL
                preg_match('/(?:vimeo\.com\/)(\d+)/', $this->video_url, $matches);
                if (isset($matches[1])) {
                    $autoplay = $this->video_settings['autoplay'] ? '1' : '0';
                    $muted = $this->video_settings['muted'] ? '1' : '0';
                    $loop = $this->video_settings['loop'] ? '1' : '0';
                    return "https://player.vimeo.com/video/{$matches[1]}?autoplay={$autoplay}&muted={$muted}&loop={$loop}&controls=0&title=0&byline=0&portrait=0";
                }
                break;

            case 'other':
                return $this->video_url;
        }

        return null;
    }

    /**
     * Get default text overlay settings
     */
    public function getDefaultTextOverlay(): array
    {
        return [
            'text' => '',
            'position' => 'bottom-left',
            'style' => 'elegant',
            'fontSize' => 'text-4xl',
            'color' => '#ffffff',
            'backgroundColor' => 'rgba(0, 0, 0, 0.5)',
            'animation' => 'fade-in',
        ];
    }

    /**
     * Get merged text overlay with defaults
     */
    public function getMergedTextOverlay(): array
    {
        return array_merge($this->getDefaultTextOverlay(), $this->text_overlay ?? []);
    }
}
