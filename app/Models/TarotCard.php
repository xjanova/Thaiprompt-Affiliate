<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarotCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_th',
        'type',
        'suit',
        'number',
        'description_en',
        'description_th',
        'upright_meaning_en',
        'upright_meaning_th',
        'reversed_meaning_en',
        'reversed_meaning_th',
        'image_url',
        'keywords_en',
        'keywords_th',
        'is_active',
    ];

    protected $casts = [
        'keywords_en' => 'array',
        'keywords_th' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get readings that used this card
     */
    public function readingCards()
    {
        return $this->hasMany(TarotReadingCard::class, 'card_id');
    }

    /**
     * ความสัมพันธ์กับคำทำนายตามหมวดหมู่
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function interpretations()
    {
        return $this->hasMany(TarotCardInterpretation::class, 'card_id');
    }

    /**
     * ดึงคำทำนายสำหรับหมวดเฉพาะ
     *
     * @param  int  $categoryId  ID หมวดหมู่
     */
    public function getInterpretationForCategory(int $categoryId): ?TarotCardInterpretation
    {
        return $this->interpretations()->where('category_id', $categoryId)->first();
    }

    /**
     * Get the meaning based on orientation and language
     */
    public function getMeaning(bool $isReversed = false, string $language = 'th'): string
    {
        $meaningField = $isReversed
            ? "reversed_meaning_{$language}"
            : "upright_meaning_{$language}";

        return $this->$meaningField ?? '';
    }

    /**
     * Get card name in specified language
     */
    public function getName(string $language = 'th'): string
    {
        $nameField = "name_{$language}";

        return $this->$nameField ?? $this->name_en;
    }

    /**
     * Get keywords in specified language
     */
    public function getKeywords(string $language = 'th'): array
    {
        $keywordsField = "keywords_{$language}";

        return $this->$keywordsField ?? $this->keywords_en ?? [];
    }

    /**
     * Scope for active cards
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for major arcana
     */
    public function scopeMajorArcana($query)
    {
        return $query->where('type', 'major_arcana');
    }

    /**
     * Scope for minor arcana
     */
    public function scopeMinorArcana($query)
    {
        return $query->where('type', 'minor_arcana');
    }

    /**
     * Scope for specific suit
     */
    public function scopeBySuit($query, string $suit)
    {
        return $query->where('suit', $suit);
    }

    /**
     * Get raw image_url value from database (for debugging)
     */
    public function getRawImageUrl(): ?string
    {
        return $this->attributes['image_url'] ?? null;
    }

    /**
     * Get image URL with fallback
     *
     * รูปไพ่เก็บใน storage/app/public/tarot/cards/
     * และเข้าถึงผ่าน /storage/tarot/cards/xxx.webp
     */
    public function getImageUrlAttribute($value)
    {
        // ถ้าไม่มีค่า ใช้ default
        if (! $value) {
            return asset('images/tarot/default-card.svg');
        }

        // ถ้าเป็น full URL อยู่แล้ว (http/https) ใช้ตรงๆ
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        // ถ้าเป็น URL แบบ /storage/... (จาก upload ใหม่)
        // Trust database value และ return asset URL
        if (str_starts_with($value, '/storage/')) {
            return asset($value);
        }

        // ถ้าเป็น path แบบไม่มี / นำหน้า (เช่น tarot/cards/xxx.webp)
        // แปลงเป็น storage URL
        if (str_starts_with($value, 'tarot/')) {
            return asset('storage/'.$value);
        }

        // ถ้าเป็น path แบบเดิม (เช่น /images/tarot/xxx.png)
        if (str_starts_with($value, '/images/') || str_starts_with($value, 'images/')) {
            return asset($value);
        }

        // สำหรับ path อื่นๆ ที่ไม่รู้จัก ลอง return ตรงๆ
        return asset($value);
    }
}
