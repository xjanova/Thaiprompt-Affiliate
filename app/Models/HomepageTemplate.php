<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * HomepageTemplate Model
 *
 * จัดการเทมเพลตสำเร็จรูปสำหรับหน้าแรก
 * ช่วยให้ admin สามารถใช้เทมเพลตที่ออกแบบไว้แล้วได้ทันที
 *
 * @property int $id
 * @property string $name ชื่อเทมเพลต
 * @property string $slug Slug สำหรับ reference
 * @property string $category หมวดหมู่
 * @property string|null $description คำอธิบาย
 * @property string|null $thumbnail รูปตัวอย่าง
 * @property array|null $sections_data ข้อมูล sections และ elements
 * @property bool $is_premium เป็นเทมเพลต Premium
 * @property bool $is_active เปิดใช้งาน
 * @property int $usage_count จำนวนครั้งที่ใช้
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class HomepageTemplate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ชื่อตารางในฐานข้อมูล
     *
     * @var string
     */
    protected $table = 'homepage_templates';

    /**
     * หมวดหมู่เทมเพลตที่รองรับ
     *
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'general' => 'ทั่วไป',
        'business' => 'ธุรกิจ',
        'ecommerce' => 'E-Commerce',
        'portfolio' => 'Portfolio',
        'landing' => 'Landing Page',
        'startup' => 'Startup',
        'agency' => 'Agency',
        'saas' => 'SaaS',
        'crypto' => 'Crypto/Blockchain',
        'minimal' => 'Minimal',
    ];

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'thumbnail',
        'sections_data',
        'is_premium',
        'is_active',
        'usage_count',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sections_data' => 'array',
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้นของ attributes
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'category' => 'general',
        'is_premium' => false,
        'is_active' => true,
        'usage_count' => 0,
    ];

    /**
     * Boot method สำหรับ auto-generate slug
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = \Str::slug($template->name);
            }
        });
    }

    /**
     * Scope: ดึงเฉพาะ templates ที่ active
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: ดึงเฉพาะ templates ฟรี
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    /**
     * Scope: ดึงเฉพาะ templates Premium
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    /**
     * Scope: ดึงตามหมวดหมู่
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: เรียงตามความนิยม
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query)
    {
        return $query->orderBy('usage_count', 'desc');
    }

    /**
     * เพิ่มจำนวนการใช้งาน
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * ดึงข้อมูล sections จากเทมเพลต
     */
    public function getSectionsData(): array
    {
        return $this->sections_data ?? [];
    }

    /**
     * นำเข้า sections จากเทมเพลตไปยังหน้าแรก
     */
    public function importToHomepage(): \Illuminate\Support\Collection
    {
        $sections = collect();
        $sectionsData = $this->getSectionsData();

        foreach ($sectionsData as $order => $sectionData) {
            // สร้าง section
            $section = HomepageSection::create([
                'name' => $sectionData['name'] ?? 'Section '.($order + 1),
                'type' => $sectionData['type'] ?? 'custom',
                'order' => $order,
                'is_active' => $sectionData['is_active'] ?? true,
                'is_fullwidth' => $sectionData['is_fullwidth'] ?? true,
                'min_height' => $sectionData['min_height'] ?? '400px',
                'max_height' => $sectionData['max_height'] ?? null,
                'padding' => $sectionData['padding'] ?? '60px 20px',
                'background_type' => $sectionData['background_type'] ?? 'color',
                'background_color' => $sectionData['background_color'] ?? '#ffffff',
                'background_color_dark' => $sectionData['background_color_dark'] ?? '#1f2937',
                'background_gradient' => $sectionData['background_gradient'] ?? null,
                'background_image' => $sectionData['background_image'] ?? null,
                'background_video' => $sectionData['background_video'] ?? null,
                'background_overlay' => $sectionData['background_overlay'] ?? null,
                'background_position' => $sectionData['background_position'] ?? 'center center',
                'background_size' => $sectionData['background_size'] ?? 'cover',
                'background_fixed' => $sectionData['background_fixed'] ?? false,
                'animation' => $sectionData['animation'] ?? null,
                'animation_delay' => $sectionData['animation_delay'] ?? 0,
                'settings' => $sectionData['settings'] ?? null,
                'responsive_settings' => $sectionData['responsive_settings'] ?? null,
            ]);

            // สร้าง elements ใน section
            if (! empty($sectionData['elements'])) {
                foreach ($sectionData['elements'] as $elemOrder => $elemData) {
                    $section->elements()->create([
                        'name' => $elemData['name'] ?? null,
                        'type' => $elemData['type'] ?? 'text',
                        'order' => $elemOrder,
                        'is_active' => $elemData['is_active'] ?? true,
                        'position_type' => $elemData['position_type'] ?? 'relative',
                        'position_x' => $elemData['position_x'] ?? 0,
                        'position_y' => $elemData['position_y'] ?? 0,
                        'position_x_unit' => $elemData['position_x_unit'] ?? '%',
                        'position_y_unit' => $elemData['position_y_unit'] ?? '%',
                        'anchor_x' => $elemData['anchor_x'] ?? 'left',
                        'anchor_y' => $elemData['anchor_y'] ?? 'top',
                        'width' => $elemData['width'] ?? 'auto',
                        'height' => $elemData['height'] ?? 'auto',
                        'max_width' => $elemData['max_width'] ?? null,
                        'content' => $elemData['content'] ?? null,
                        'content_en' => $elemData['content_en'] ?? null,
                        'image_url' => $elemData['image_url'] ?? null,
                        'image_url_dark' => $elemData['image_url_dark'] ?? null,
                        'video_url' => $elemData['video_url'] ?? null,
                        'link_url' => $elemData['link_url'] ?? null,
                        'link_target' => $elemData['link_target'] ?? '_self',
                        'icon_class' => $elemData['icon_class'] ?? null,
                        'font_family' => $elemData['font_family'] ?? null,
                        'font_size' => $elemData['font_size'] ?? '16px',
                        'font_weight' => $elemData['font_weight'] ?? '400',
                        'line_height' => $elemData['line_height'] ?? '1.5',
                        'letter_spacing' => $elemData['letter_spacing'] ?? 'normal',
                        'text_align' => $elemData['text_align'] ?? 'left',
                        'text_transform' => $elemData['text_transform'] ?? 'none',
                        'color' => $elemData['color'] ?? '#000000',
                        'color_dark' => $elemData['color_dark'] ?? '#ffffff',
                        'background_color' => $elemData['background_color'] ?? null,
                        'background_color_dark' => $elemData['background_color_dark'] ?? null,
                        'border_color' => $elemData['border_color'] ?? null,
                        'border_color_dark' => $elemData['border_color_dark'] ?? null,
                        'border_width' => $elemData['border_width'] ?? '0',
                        'border_style' => $elemData['border_style'] ?? 'solid',
                        'border_radius' => $elemData['border_radius'] ?? '0',
                        'padding' => $elemData['padding'] ?? '0',
                        'margin' => $elemData['margin'] ?? '0',
                        'box_shadow' => $elemData['box_shadow'] ?? null,
                        'text_shadow' => $elemData['text_shadow'] ?? null,
                        'animation' => $elemData['animation'] ?? null,
                        'animation_delay' => $elemData['animation_delay'] ?? 0,
                        'animation_duration' => $elemData['animation_duration'] ?? 500,
                        'hover_effects' => $elemData['hover_effects'] ?? null,
                        'responsive_settings' => $elemData['responsive_settings'] ?? null,
                        'settings' => $elemData['settings'] ?? null,
                    ]);
                }
            }

            $sections->push($section);
        }

        // เพิ่มจำนวนการใช้งาน
        $this->incrementUsage();

        return $sections;
    }

    /**
     * แปลงเป็น array สำหรับ API response
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category,
            'category_label' => self::CATEGORIES[$this->category] ?? $this->category,
            'description' => $this->description,
            'thumbnail' => $this->thumbnail,
            'is_premium' => $this->is_premium,
            'is_active' => $this->is_active,
            'usage_count' => $this->usage_count,
            'sections_count' => count($this->sections_data ?? []),
        ];
    }
}
