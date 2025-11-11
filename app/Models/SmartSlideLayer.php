<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartSlideLayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'slide_id',
        'type',
        'content',
        'position',
        'style',
        'responsive',
        'animation',
        'link_url',
        'link_target',
        'parent_id',
        'order',
        'is_visible',
    ];

    protected $casts = [
        'position' => 'array',
        'style' => 'array',
        'responsive' => 'array',
        'animation' => 'array',
        'is_visible' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Layer types
     */
    const TYPE_HEADING = 'heading';
    const TYPE_TEXT = 'text';
    const TYPE_IMAGE = 'image';
    const TYPE_BUTTON = 'button';
    const TYPE_VIDEO_YOUTUBE = 'video_youtube';
    const TYPE_VIDEO_VIMEO = 'video_vimeo';
    const TYPE_VIDEO_UPLOAD = 'video_upload';
    const TYPE_HTML = 'html';

    /**
     * Get default position
     */
    public static function getDefaultPosition($type = 'default'): array
    {
        return [
            'mode' => $type, // default (flex), absolute
            'x' => 0,
            'y' => 0,
            'width' => 'auto',
            'height' => 'auto',
            'align' => 'center', // left, center, right
            'justify' => 'center', // top, center, bottom
            'z_index' => 1,
        ];
    }

    /**
     * Get default style by layer type
     */
    public static function getDefaultStyle(string $layerType): array
    {
        $baseStyle = [
            'font_family' => 'inherit',
            'font_size' => 16,
            'font_weight' => 400,
            'line_height' => 1.5,
            'color' => '#000000',
            'background_color' => 'transparent',
            'padding' => [0, 0, 0, 0], // top, right, bottom, left
            'margin' => [0, 0, 0, 0],
            'border' => [
                'width' => 0,
                'style' => 'solid',
                'color' => '#000000',
                'radius' => 0,
            ],
            'shadow' => [
                'x' => 0,
                'y' => 0,
                'blur' => 0,
                'color' => 'rgba(0,0,0,0)',
            ],
        ];

        // Custom styles per type
        switch ($layerType) {
            case self::TYPE_HEADING:
                $baseStyle['font_size'] = 48;
                $baseStyle['font_weight'] = 700;
                $baseStyle['line_height'] = 1.2;
                break;

            case self::TYPE_TEXT:
                $baseStyle['font_size'] = 18;
                $baseStyle['line_height'] = 1.6;
                break;

            case self::TYPE_BUTTON:
                $baseStyle['font_size'] = 16;
                $baseStyle['font_weight'] = 600;
                $baseStyle['background_color'] = '#3B82F6';
                $baseStyle['color'] = '#FFFFFF';
                $baseStyle['padding'] = [12, 24, 12, 24];
                $baseStyle['border']['radius'] = 8;
                break;

            case self::TYPE_IMAGE:
                $baseStyle['width'] = 'auto';
                $baseStyle['height'] = 'auto';
                $baseStyle['object_fit'] = 'cover';
                break;
        }

        return $baseStyle;
    }

    /**
     * Get default responsive
     */
    public static function getDefaultResponsive(): array
    {
        return [
            'desktop' => [
                'visible' => true,
                'font_size_scale' => 1,
            ],
            'tablet' => [
                'visible' => true,
                'font_size_scale' => 0.8,
            ],
            'mobile' => [
                'visible' => true,
                'font_size_scale' => 0.6,
            ],
        ];
    }

    /**
     * Get default animation
     */
    public static function getDefaultAnimation(): array
    {
        return [
            'animation_in' => 'fadeIn', // fadeIn, slideInLeft, slideInRight, zoomIn, etc.
            'animation_out' => 'fadeOut',
            'delay' => 0, // milliseconds
            'duration' => 800,
            'easing' => 'ease',
        ];
    }

    /**
     * Relationships
     */
    public function slide()
    {
        return $this->belongsTo(SmartSlide::class, 'slide_id');
    }

    public function parent()
    {
        return $this->belongsTo(SmartSlideLayer::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(SmartSlideLayer::class, 'parent_id')->orderBy('order');
    }

    /**
     * Scopes
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeRootLayers($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($layer) {
            if (empty($layer->position)) {
                $layer->position = self::getDefaultPosition();
            }
            if (empty($layer->style)) {
                $layer->style = self::getDefaultStyle($layer->type);
            }
            if (empty($layer->responsive)) {
                $layer->responsive = self::getDefaultResponsive();
            }
            if (empty($layer->animation)) {
                $layer->animation = self::getDefaultAnimation();
            }
        });
    }

    /**
     * Duplicate layer to another slide
     */
    public function duplicateToSlide($slideId, $parentId = null)
    {
        $newLayer = $this->replicate();
        $newLayer->slide_id = $slideId;
        $newLayer->parent_id = $parentId;
        $newLayer->save();

        // Duplicate children recursively
        foreach ($this->children as $child) {
            $child->duplicateToSlide($slideId, $newLayer->id);
        }

        return $newLayer;
    }

    /**
     * Get content URL for media types
     */
    public function getContentUrl()
    {
        if (in_array($this->type, [self::TYPE_IMAGE, self::TYPE_VIDEO_UPLOAD])) {
            if (filter_var($this->content, FILTER_VALIDATE_URL)) {
                return $this->content;
            }
            return asset('storage/' . $this->content);
        }

        return $this->content;
    }
}
