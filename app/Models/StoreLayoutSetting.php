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
     * ดึง layout styles ที่รองรับ (ใช้ข้อมูลจาก presets)
     */
    public static function getLayoutStyles(): array
    {
        $presets = static::getLayoutPresets();
        $styles = [];
        foreach ($presets as $key => $preset) {
            $styles[$key] = [
                'name' => $preset['name'],
                'description' => $preset['description'],
                'icon' => $preset['icon'],
            ];
        }

        return $styles;
    }

    /**
     * ดึง preset definitions สำหรับ layout templates ทั้ง 7 แบบ
     *
     * แต่ละ preset กำหนด: สี, header style, product card, sections order/visibility
     * ไม่รวม: รูปภาพ, social links, ข้อความ (ไม่ถูกเขียนทับเมื่อ apply)
     */
    public static function getLayoutPresets(): array
    {
        return [
            'modern' => [
                'name' => 'Modern',
                'description' => 'สไตล์โมเดิร์น Glassmorphism การ์ดโปร่งแสง มุมโค้ง',
                'icon' => '🌟',
                'colors' => [
                    'primary_color' => '#6366f1',
                    'secondary_color' => '#8b5cf6',
                    'accent_color' => '#ec4899',
                    'text_color' => '#1f2937',
                    'background_color' => '#ffffff',
                ],
                'header_style' => 'gradient',
                'header_height' => 220,
                'product_card_style' => 'default',
                'products_per_row' => 4,
                'sidebar_position' => 'left',
                'show_sidebar' => true,
                'categories_style' => 'grid',
                'sections_order' => ['header', 'slider', 'featured_products', 'categories', 'all_products', 'promotion', 'footer'],
                'sections_visibility' => [
                    'header' => true, 'slider' => false, 'promotion' => false,
                    'featured_products' => true, 'categories' => true,
                    'all_products' => true, 'footer' => true,
                ],
            ],
            'classic' => [
                'name' => 'Classic',
                'description' => 'สไตล์คลาสสิก เรียบง่าย มุมเหลี่ยม สะอาดตา',
                'icon' => '🏛️',
                'colors' => [
                    'primary_color' => '#2563eb',
                    'secondary_color' => '#1e40af',
                    'accent_color' => '#dc2626',
                    'text_color' => '#111827',
                    'background_color' => '#f9fafb',
                ],
                'header_style' => 'solid',
                'header_height' => 180,
                'product_card_style' => 'minimal',
                'products_per_row' => 4,
                'sidebar_position' => 'left',
                'show_sidebar' => true,
                'categories_style' => 'list',
                'sections_order' => ['header', 'slider', 'categories', 'featured_products', 'all_products', 'promotion', 'footer'],
                'sections_visibility' => [
                    'header' => true, 'slider' => false, 'promotion' => false,
                    'featured_products' => true, 'categories' => true,
                    'all_products' => true, 'footer' => true,
                ],
            ],
            'minimal' => [
                'name' => 'Minimal',
                'description' => 'สไตล์มินิมอล เรียบหรู เน้นช่องว่าง ตัวอักษรบาง',
                'icon' => '✨',
                'colors' => [
                    'primary_color' => '#0f172a',
                    'secondary_color' => '#334155',
                    'accent_color' => '#f59e0b',
                    'text_color' => '#334155',
                    'background_color' => '#ffffff',
                ],
                'header_style' => 'transparent',
                'header_height' => 160,
                'product_card_style' => 'minimal',
                'products_per_row' => 3,
                'sidebar_position' => 'left',
                'show_sidebar' => false,
                'categories_style' => 'list',
                'sections_order' => ['header', 'featured_products', 'all_products', 'footer'],
                'sections_visibility' => [
                    'header' => true, 'slider' => false, 'promotion' => false,
                    'featured_products' => true, 'categories' => false,
                    'all_products' => true, 'footer' => true,
                ],
            ],
            'bold' => [
                'name' => 'Bold',
                'description' => 'สไตล์โดดเด่น สีสันสดใส ตัวอักษรใหญ่ เน้น visual',
                'icon' => '🔥',
                'colors' => [
                    'primary_color' => '#dc2626',
                    'secondary_color' => '#ea580c',
                    'accent_color' => '#facc15',
                    'text_color' => '#0f172a',
                    'background_color' => '#fffbeb',
                ],
                'header_style' => 'gradient',
                'header_height' => 280,
                'product_card_style' => 'detailed',
                'products_per_row' => 3,
                'sidebar_position' => 'right',
                'show_sidebar' => true,
                'categories_style' => 'grid',
                'sections_order' => ['header', 'slider', 'promotion', 'featured_products', 'categories', 'all_products', 'footer'],
                'sections_visibility' => [
                    'header' => true, 'slider' => false, 'promotion' => false,
                    'featured_products' => true, 'categories' => true,
                    'all_products' => true, 'footer' => true,
                ],
            ],

            // ===== Marketplace-Inspired Templates =====

            'lazada' => [
                'name' => 'Lazada Style',
                'description' => 'สไตล์ Lazada — น้ำเงิน/ส้ม หมวดหมู่เด่น แบนเนอร์ใหญ่ สะอาดตา',
                'icon' => '🛒',
                'colors' => [
                    'primary_color' => '#0f146d',
                    'secondary_color' => '#1a1fa0',
                    'accent_color' => '#f57224',
                    'text_color' => '#212121',
                    'background_color' => '#f5f5f5',
                ],
                'header_style' => 'gradient',
                'header_height' => 180,
                'product_card_style' => 'default',
                'products_per_row' => 5,
                'sidebar_position' => 'left',
                'show_sidebar' => true,
                'categories_style' => 'grid',
                'sections_order' => ['header', 'slider', 'categories', 'promotion', 'featured_products', 'all_products', 'footer'],
                'sections_visibility' => [
                    'header' => true, 'slider' => true, 'promotion' => true,
                    'featured_products' => true, 'categories' => true,
                    'all_products' => true, 'footer' => true,
                ],
            ],
            'shopee' => [
                'name' => 'Shopee Style',
                'description' => 'สไตล์ Shopee — ส้ม/แดง การ์ดขาว badge เด่น เน้นโปรโมชั่น',
                'icon' => '🧡',
                'colors' => [
                    'primary_color' => '#ee4d2d',
                    'secondary_color' => '#d0011b',
                    'accent_color' => '#ff6633',
                    'text_color' => '#222222',
                    'background_color' => '#f5f5f5',
                ],
                'header_style' => 'gradient',
                'header_height' => 170,
                'product_card_style' => 'default',
                'products_per_row' => 5,
                'sidebar_position' => 'left',
                'show_sidebar' => false,
                'categories_style' => 'grid',
                'sections_order' => ['header', 'slider', 'promotion', 'categories', 'featured_products', 'all_products', 'footer'],
                'sections_visibility' => [
                    'header' => true, 'slider' => true, 'promotion' => true,
                    'featured_products' => true, 'categories' => true,
                    'all_products' => true, 'footer' => true,
                ],
            ],
            'aliexpress' => [
                'name' => 'AliExpress Style',
                'description' => 'สไตล์ AliExpress — แดง/ดำ สินค้าเยอะ เน้นราคาดีล หนาแน่น',
                'icon' => '🌍',
                'colors' => [
                    'primary_color' => '#e53e3e',
                    'secondary_color' => '#1a1a1a',
                    'accent_color' => '#ff4747',
                    'text_color' => '#1a1a1a',
                    'background_color' => '#ffffff',
                ],
                'header_style' => 'solid',
                'header_height' => 160,
                'product_card_style' => 'minimal',
                'products_per_row' => 6,
                'sidebar_position' => 'left',
                'show_sidebar' => false,
                'categories_style' => 'list',
                'sections_order' => ['header', 'slider', 'promotion', 'featured_products', 'categories', 'all_products', 'footer'],
                'sections_visibility' => [
                    'header' => true, 'slider' => true, 'promotion' => true,
                    'featured_products' => true, 'categories' => true,
                    'all_products' => true, 'footer' => true,
                ],
            ],
        ];
    }

    /**
     * ใช้ preset template — ตั้งค่าสี, header, layout
     * ไม่เขียนทับ: รูปภาพ, social links, ข้อความ, SEO, custom code
     */
    public function applyPreset(string $style): bool
    {
        $presets = static::getLayoutPresets();
        if (! isset($presets[$style])) {
            return false;
        }

        $preset = $presets[$style];

        $fieldsToUpdate = [
            'layout_style' => $style,
            'primary_color' => $preset['colors']['primary_color'],
            'secondary_color' => $preset['colors']['secondary_color'],
            'accent_color' => $preset['colors']['accent_color'],
            'text_color' => $preset['colors']['text_color'],
            'background_color' => $preset['colors']['background_color'],
            'header_style' => $preset['header_style'],
            'header_height' => $preset['header_height'],
            'product_card_style' => $preset['product_card_style'],
            'products_per_row' => $preset['products_per_row'],
            'sidebar_position' => $preset['sidebar_position'],
            'show_sidebar' => $preset['show_sidebar'],
            'categories_style' => $preset['categories_style'],
            'sections_order' => $preset['sections_order'],
            'sections_visibility' => $preset['sections_visibility'],
        ];

        return $this->update($fieldsToUpdate);
    }

    /**
     * ดึง Tailwind class strings ตาม layout_style
     *
     * ใช้ใน section partials เพื่อ render ตาม template ที่เลือก
     */
    public function getLayoutClassesAttribute(): array
    {
        return match ($this->layout_style) {
            'classic' => [
                'card' => 'bg-white dark:bg-gray-800 rounded-none shadow border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-300',
                'card_hover' => 'hover:shadow-md',
                'section_spacing' => 'py-8',
                'heading' => 'text-2xl font-bold',
                'container' => 'container mx-auto px-4',
                'border_radius' => 'rounded-none',
                'header_text' => 'text-3xl md:text-4xl font-bold',
                'badge' => 'bg-green-600 px-3 py-1 rounded-sm text-sm',
                'button' => 'rounded-sm',
                'stats_bg' => 'bg-white/30 px-3 py-1.5 rounded-sm border border-white/30',
                'logo_size' => 'w-24 h-24 md:w-32 md:h-32',
                'logo_radius' => 'rounded-lg',
                'wave_divider' => false,
                'backdrop_blur' => '',
                'category_card' => 'bg-white dark:bg-gray-800 rounded-none p-4 text-center shadow border border-gray-200 dark:border-gray-700',
                'sidebar_card' => 'bg-white dark:bg-gray-800 rounded-none shadow border border-gray-200 dark:border-gray-700 overflow-hidden sticky top-4',
                'footer_style' => 'border-t-2 border-gray-300 dark:border-gray-600',
            ],
            'minimal' => [
                'card' => 'bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-800 overflow-hidden transition-all duration-300',
                'card_hover' => 'hover:shadow-sm hover:border-gray-200',
                'section_spacing' => 'py-12',
                'heading' => 'text-xl font-light tracking-wide uppercase',
                'container' => 'max-w-6xl mx-auto px-6',
                'border_radius' => 'rounded-lg',
                'header_text' => 'text-2xl md:text-3xl font-light tracking-wide',
                'badge' => 'bg-gray-800 px-3 py-1 rounded-full text-xs',
                'button' => 'rounded-full',
                'stats_bg' => 'bg-white/10 px-3 py-1.5 rounded-full border border-white/20',
                'logo_size' => 'w-20 h-20 md:w-24 md:h-24',
                'logo_radius' => 'rounded-full',
                'wave_divider' => false,
                'backdrop_blur' => '',
                'category_card' => 'bg-white dark:bg-gray-800 rounded-lg p-4 text-center border border-gray-100 dark:border-gray-800',
                'sidebar_card' => 'bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-800 overflow-hidden sticky top-4',
                'footer_style' => 'border-t border-gray-100 dark:border-gray-800',
            ],
            'bold' => [
                'card' => 'bg-white dark:bg-gray-800 rounded-2xl shadow-xl border-2 border-transparent overflow-hidden transition-all duration-300',
                'card_hover' => 'hover:shadow-2xl hover:-translate-y-2',
                'section_spacing' => 'py-10',
                'heading' => 'text-3xl md:text-4xl font-black',
                'container' => 'container mx-auto px-4',
                'border_radius' => 'rounded-2xl',
                'header_text' => 'text-4xl md:text-6xl font-black',
                'badge' => 'bg-yellow-500 text-black px-4 py-2 rounded-xl font-black text-sm',
                'button' => 'rounded-xl',
                'stats_bg' => 'bg-white/25 backdrop-blur-sm px-5 py-3 rounded-2xl border-2 border-white/40',
                'logo_size' => 'w-32 h-32 md:w-40 md:h-40',
                'logo_radius' => 'rounded-3xl',
                'wave_divider' => true,
                'backdrop_blur' => 'backdrop-blur-sm',
                'category_card' => 'bg-white dark:bg-gray-800 rounded-2xl p-5 text-center shadow-lg border-2 border-transparent hover:border-yellow-400',
                'sidebar_card' => 'bg-white dark:bg-gray-800 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-gray-700 overflow-hidden sticky top-4',
                'footer_style' => 'border-t-4 border-gray-200 dark:border-gray-700',
            ],
            'lazada' => [
                'card' => 'bg-white dark:bg-gray-800 rounded-sm shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden transition-all duration-200',
                'card_hover' => 'hover:shadow-md hover:-translate-y-0.5',
                'section_spacing' => 'py-4',
                'heading' => 'text-xl md:text-2xl font-bold',
                'container' => 'max-w-[1200px] mx-auto px-3',
                'border_radius' => 'rounded-sm',
                'header_text' => 'text-2xl md:text-4xl font-bold',
                'badge' => 'bg-orange-500 px-3 py-1 rounded-sm text-xs font-bold',
                'button' => 'rounded-sm',
                'stats_bg' => 'bg-white/20 px-3 py-1.5 rounded-sm border border-white/30',
                'logo_size' => 'w-20 h-20 md:w-24 md:h-24',
                'logo_radius' => 'rounded-sm',
                'wave_divider' => false,
                'backdrop_blur' => '',
                'category_card' => 'bg-white dark:bg-gray-800 rounded-sm p-3 text-center shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md',
                'sidebar_card' => 'bg-white dark:bg-gray-800 rounded-sm shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden sticky top-2',
                'footer_style' => 'bg-gray-800 text-gray-300',
            ],
            'shopee' => [
                'card' => 'bg-white dark:bg-gray-800 rounded border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden transition-all duration-200',
                'card_hover' => 'hover:shadow-lg hover:-translate-y-0.5 hover:border-orange-200',
                'section_spacing' => 'py-4',
                'heading' => 'text-xl md:text-2xl font-bold',
                'container' => 'max-w-[1200px] mx-auto px-3',
                'border_radius' => 'rounded',
                'header_text' => 'text-2xl md:text-4xl font-bold',
                'badge' => 'bg-red-600 px-3 py-1 rounded-sm text-xs font-bold',
                'button' => 'rounded',
                'stats_bg' => 'bg-white/20 px-3 py-1.5 rounded border border-white/30',
                'logo_size' => 'w-16 h-16 md:w-20 md:h-20',
                'logo_radius' => 'rounded-lg',
                'wave_divider' => false,
                'backdrop_blur' => '',
                'category_card' => 'bg-white dark:bg-gray-800 rounded p-3 text-center hover:shadow-md transition',
                'sidebar_card' => 'bg-white dark:bg-gray-800 rounded shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden sticky top-2',
                'footer_style' => 'bg-gray-900 text-gray-400',
            ],
            'aliexpress' => [
                'card' => 'bg-white dark:bg-gray-800 rounded shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden transition-all duration-200',
                'card_hover' => 'hover:shadow-md',
                'section_spacing' => 'py-3',
                'heading' => 'text-lg md:text-xl font-bold',
                'container' => 'max-w-[1400px] mx-auto px-2',
                'border_radius' => 'rounded',
                'header_text' => 'text-xl md:text-3xl font-bold',
                'badge' => 'bg-red-600 px-2 py-1 rounded-sm text-xs font-bold',
                'button' => 'rounded',
                'stats_bg' => 'bg-white/20 px-3 py-1 rounded border border-white/20',
                'logo_size' => 'w-14 h-14 md:w-20 md:h-20',
                'logo_radius' => 'rounded',
                'wave_divider' => false,
                'backdrop_blur' => '',
                'category_card' => 'bg-white dark:bg-gray-800 rounded p-2 text-center text-sm hover:shadow transition',
                'sidebar_card' => 'bg-white dark:bg-gray-800 rounded shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden sticky top-2',
                'footer_style' => 'bg-black text-gray-400',
            ],
            default => [ // modern
                'card' => 'bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-xl shadow-lg overflow-hidden transition-all duration-300',
                'card_hover' => 'hover:shadow-xl hover:-translate-y-1',
                'section_spacing' => 'py-8',
                'heading' => 'text-2xl md:text-3xl font-bold',
                'container' => 'container mx-auto px-4',
                'border_radius' => 'rounded-xl',
                'header_text' => 'text-3xl md:text-5xl font-black tracking-tight drop-shadow-lg',
                'badge' => 'bg-emerald-500/80 backdrop-blur-lg px-4 py-2 rounded-full border border-white/30 text-sm',
                'button' => 'rounded-lg',
                'stats_bg' => 'bg-white/20 backdrop-blur-lg px-4 py-2 rounded-xl border border-white/30',
                'logo_size' => 'w-28 h-28 md:w-36 md:h-36',
                'logo_radius' => 'rounded-3xl',
                'wave_divider' => true,
                'backdrop_blur' => 'backdrop-blur-lg',
                'category_card' => 'bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-xl p-4 text-center shadow-md border border-gray-100 dark:border-gray-700',
                'sidebar_card' => 'bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden sticky top-4',
                'footer_style' => 'border-t border-gray-200 dark:border-gray-700',
            ],
        };
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
