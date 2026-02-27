<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Store Layout Setting Model
 *
 * จัดการการตั้งค่า layout ของร้านค้าผู้เช่า (Seller Store)
 * รองรับ: สี, แบนเนอร์, สไลด์, sections, และ custom styles
 *
 * @property int $id
 * @property int $user_id
 * @property string $primary_color
 * @property string $secondary_color
 * @property string $accent_color
 * @property string $text_color
 * @property string $background_color
 * @property string|null $header_bg_color
 * @property string|null $footer_bg_color
 * @property string $header_style
 * @property string|null $header_image
 * @property int $header_height
 * @property bool $show_store_logo
 * @property bool $show_store_name
 * @property bool $show_store_description
 * @property bool $show_store_stats
 * @property bool $slider_enabled
 * @property array|null $slider_images
 * @property int $slider_autoplay_speed
 * @property bool $slider_show_arrows
 * @property bool $slider_show_dots
 * @property string $slider_effect
 * @property string $layout_style
 * @property string $product_card_style
 * @property int $products_per_row
 * @property string $sidebar_position
 * @property bool $show_sidebar
 * @property array|null $sections_order
 * @property array|null $sections_visibility
 * @property bool $show_featured_products
 * @property string $featured_title
 * @property int $featured_products_count
 * @property bool $show_categories
 * @property string $categories_title
 * @property string $categories_style
 * @property bool $show_promotion_banner
 * @property string|null $promotion_image
 * @property string|null $promotion_link
 * @property string|null $promotion_text
 * @property array|null $social_links
 * @property string|null $custom_css
 * @property string|null $custom_js
 * @property bool $show_footer
 * @property string|null $footer_content
 * @property bool $show_contact_info
 * @property bool $show_social_links
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property bool $is_published
 * @property \Carbon\Carbon|null $published_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class StoreLayoutSetting extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'store_layout_settings';

    /**
     * Fields ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        // Theme Colors
        'primary_color',
        'secondary_color',
        'accent_color',
        'text_color',
        'background_color',
        'header_bg_color',
        'footer_bg_color',
        // Header Section
        'header_style',
        'header_image',
        'header_height',
        'show_store_logo',
        'show_store_name',
        'show_store_description',
        'show_store_stats',
        // Banner Slider
        'slider_enabled',
        'slider_images',
        'slider_autoplay_speed',
        'slider_show_arrows',
        'slider_show_dots',
        'slider_effect',
        // Layout Style
        'layout_style',
        'product_card_style',
        'products_per_row',
        'sidebar_position',
        'show_sidebar',
        // Sections
        'sections_order',
        'sections_visibility',
        // Featured Section
        'show_featured_products',
        'featured_title',
        'featured_products_count',
        // Categories Section
        'show_categories',
        'categories_title',
        'categories_style',
        // Promotions
        'show_promotion_banner',
        'promotion_image',
        'promotion_link',
        'promotion_text',
        // Social Links
        'social_links',
        // Custom CSS/JS
        'custom_css',
        'custom_js',
        // Footer
        'show_footer',
        'footer_content',
        'show_contact_info',
        'show_social_links',
        // SEO
        'meta_title',
        'meta_description',
        'meta_keywords',
        // Status
        'is_published',
        'published_at',
    ];

    /**
     * Attribute casting
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Booleans
        'show_store_logo' => 'boolean',
        'show_store_name' => 'boolean',
        'show_store_description' => 'boolean',
        'show_store_stats' => 'boolean',
        'slider_enabled' => 'boolean',
        'slider_show_arrows' => 'boolean',
        'slider_show_dots' => 'boolean',
        'show_sidebar' => 'boolean',
        'show_featured_products' => 'boolean',
        'show_categories' => 'boolean',
        'show_promotion_banner' => 'boolean',
        'show_footer' => 'boolean',
        'show_contact_info' => 'boolean',
        'show_social_links' => 'boolean',
        'is_published' => 'boolean',
        // Integers
        'header_height' => 'integer',
        'slider_autoplay_speed' => 'integer',
        'products_per_row' => 'integer',
        'featured_products_count' => 'integer',
        // JSON
        'slider_images' => 'array',
        'sections_order' => 'array',
        'sections_visibility' => 'array',
        'social_links' => 'array',
        // Dates
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Default values สำหรับ attributes
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'primary_color' => '#6366f1',
        'secondary_color' => '#8b5cf6',
        'accent_color' => '#ec4899',
        'text_color' => '#1f2937',
        'background_color' => '#ffffff',
        'header_style' => 'gradient',
        'header_height' => 200,
        'show_store_logo' => true,
        'show_store_name' => true,
        'show_store_description' => true,
        'show_store_stats' => true,
        'slider_enabled' => false,
        'slider_autoplay_speed' => 5000,
        'slider_show_arrows' => true,
        'slider_show_dots' => true,
        'slider_effect' => 'slide',
        'layout_style' => 'modern',
        'product_card_style' => 'default',
        'products_per_row' => 4,
        'sidebar_position' => 'left',
        'show_sidebar' => true,
        'show_featured_products' => true,
        'featured_title' => 'สินค้าแนะนำ',
        'featured_products_count' => 8,
        'show_categories' => true,
        'categories_title' => 'หมวดหมู่สินค้า',
        'categories_style' => 'grid',
        'show_promotion_banner' => false,
        'show_footer' => true,
        'show_contact_info' => true,
        'show_social_links' => true,
        'is_published' => false,
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * ความสัมพันธ์กับ User (เจ้าของร้าน)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * ดึง URL ของ header image
     */
    public function getHeaderImageUrlAttribute(): ?string
    {
        if (! $this->header_image) {
            return null;
        }

        if (str_starts_with($this->header_image, 'http')) {
            return $this->header_image;
        }

        return Storage::url($this->header_image);
    }

    /**
     * ดึง URL ของ promotion image
     */
    public function getPromotionImageUrlAttribute(): ?string
    {
        if (! $this->promotion_image) {
            return null;
        }

        if (str_starts_with($this->promotion_image, 'http')) {
            return $this->promotion_image;
        }

        return Storage::url($this->promotion_image);
    }

    /**
     * ดึง slider images พร้อม URLs
     */
    public function getSliderImagesWithUrlsAttribute(): array
    {
        if (! $this->slider_images || ! is_array($this->slider_images)) {
            return [];
        }

        return collect($this->slider_images)->map(function ($slide) {
            if (isset($slide['image']) && ! str_starts_with($slide['image'], 'http')) {
                $slide['image_url'] = Storage::url($slide['image']);
            } else {
                $slide['image_url'] = $slide['image'] ?? null;
            }

            return $slide;
        })->toArray();
    }

    /**
     * ดึง CSS variables สำหรับ inline styles
     */
    public function getCssVariablesAttribute(): string
    {
        return "
            --store-primary: {$this->primary_color};
            --store-secondary: {$this->secondary_color};
            --store-accent: {$this->accent_color};
            --store-text: {$this->text_color};
            --store-bg: {$this->background_color};
        ";
    }

    /**
     * ดึง grid classes ตามจำนวนสินค้าต่อแถว
     */
    public function getProductGridClassesAttribute(): string
    {
        return match ($this->products_per_row) {
            2 => 'grid-cols-1 sm:grid-cols-2',
            3 => 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3',
            4 => 'grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
            5 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5',
            6 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6',
            default => 'grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
        };
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope: เฉพาะที่เผยแพร่แล้ว
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope: ตาม user_id
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ==========================================
    // STATIC METHODS
    // ==========================================

    /**
     * ดึงหรือสร้าง settings สำหรับ user
     *
     * ถ้าสร้างใหม่ จะ inherit สีจาก VendorStore อัตโนมัติ
     */
    public static function getOrCreateForUser(int $userId): static
    {
        $existing = static::where('user_id', $userId)->first();

        if ($existing) {
            return $existing;
        }

        // สร้างใหม่ — inherit สีจาก VendorStore ถ้ามี
        $defaults = ['is_published' => false];
        $store = VendorStore::where('user_id', $userId)->first();

        if ($store) {
            if ($store->primary_color) {
                $defaults['primary_color'] = $store->primary_color;
            }
            if ($store->secondary_color) {
                $defaults['secondary_color'] = $store->secondary_color;
            }
        }

        return static::create(array_merge(['user_id' => $userId], $defaults));
    }

    /**
     * ดึง settings สำหรับ user (ไม่ว่าจะ publish หรือยัง)
     */
    public static function getForUser(int $userId): ?static
    {
        return static::forUser($userId)->first();
    }

    /**
     * ดึง settings ที่เผยแพร่แล้วสำหรับ user
     */
    public static function getPublishedForUser(int $userId): ?static
    {
        return static::forUser($userId)->published()->first();
    }

    /**
     * ดึง default sections order
     */
    public static function getDefaultSectionsOrder(): array
    {
        return [
            'header',
            'slider',
            'promotion',
            'featured_products',
            'categories',
            'all_products',
            'footer',
        ];
    }

    /**
     * ดึง default sections visibility
     */
    public static function getDefaultSectionsVisibility(): array
    {
        return [
            'header' => true,
            'slider' => false,
            'promotion' => false,
            'featured_products' => true,
            'categories' => true,
            'all_products' => true,
            'footer' => true,
        ];
    }

    /**
     * ดึง layout styles ที่รองรับ
     */
    public static function getLayoutStyles(): array
    {
        return [
            'modern' => [
                'name' => 'Modern',
                'description' => 'สไตล์โมเดิร์น สะอาดตา เน้นสินค้า',
                'preview' => '/images/layouts/modern.jpg',
            ],
            'classic' => [
                'name' => 'Classic',
                'description' => 'สไตล์คลาสสิก เรียบง่าย ใช้งานง่าย',
                'preview' => '/images/layouts/classic.jpg',
            ],
            'minimal' => [
                'name' => 'Minimal',
                'description' => 'สไตล์มินิมอล เรียบหรู ดูดี',
                'preview' => '/images/layouts/minimal.jpg',
            ],
            'bold' => [
                'name' => 'Bold',
                'description' => 'สไตล์โดดเด่น สีสันสดใส',
                'preview' => '/images/layouts/bold.jpg',
            ],
        ];
    }

    /**
     * ดึง header styles ที่รองรับ
     */
    public static function getHeaderStyles(): array
    {
        return [
            'gradient' => 'Gradient สีไล่ระดับ',
            'solid' => 'Solid สีเดียว',
            'image' => 'รูปภาพ Background',
            'transparent' => 'โปร่งใส',
        ];
    }

    /**
     * ดึง slider effects ที่รองรับ
     */
    public static function getSliderEffects(): array
    {
        return [
            'slide' => 'Slide เลื่อน',
            'fade' => 'Fade จางหาย',
            'cube' => 'Cube 3D',
        ];
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * เผยแพร่ layout
     */
    public function publish(): bool
    {
        $this->is_published = true;
        $this->published_at = now();

        return $this->save();
    }

    /**
     * ยกเลิกการเผยแพร่ layout
     */
    public function unpublish(): bool
    {
        $this->is_published = false;

        return $this->save();
    }

    /**
     * ตรวจสอบว่า section นี้แสดงหรือไม่
     */
    public function isSectionVisible(string $section): bool
    {
        $visibility = $this->sections_visibility ?? self::getDefaultSectionsVisibility();

        return $visibility[$section] ?? true;
    }

    /**
     * ดึง sections ที่เรียงลำดับแล้ว
     */
    public function getOrderedSections(): array
    {
        $order = $this->sections_order ?? self::getDefaultSectionsOrder();
        $visibility = $this->sections_visibility ?? self::getDefaultSectionsVisibility();

        return collect($order)
            ->filter(fn ($section) => $visibility[$section] ?? true)
            ->values()
            ->toArray();
    }
}
