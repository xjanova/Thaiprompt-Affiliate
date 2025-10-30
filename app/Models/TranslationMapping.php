<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TranslationMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'source_lang',
        'target_lang',
        'translation',
        'context',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        // ล้าง cache เมื่อมีการเปลี่ยนแปลง
        static::saved(function () {
            Cache::forget('translation_mappings_active');
        });

        static::deleted(function () {
            Cache::forget('translation_mappings_active');
        });
    }

    /**
     * ค้นหาคำแปลที่กำหนดเอง
     */
    public static function findTranslation(string $text, string $sourceLang, string $targetLang): ?string
    {
        $mapping = self::where('key', $text)
            ->where('source_lang', $sourceLang)
            ->where('target_lang', $targetLang)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->first();

        return $mapping?->translation;
    }

    /**
     * ดึงคำแปลที่ active ทั้งหมด (พร้อม cache)
     */
    public static function getAllActive()
    {
        return Cache::remember('translation_mappings_active', 3600, function () {
            return self::where('is_active', true)
                ->orderBy('priority', 'desc')
                ->get();
        });
    }

    /**
     * สร้างหรืออัพเดทคำแปล
     */
    public static function createOrUpdateMapping(
        string $key,
        string $sourceLang,
        string $targetLang,
        string $translation,
        ?string $context = null,
        int $priority = 0
    ) {
        return self::updateOrCreate(
            [
                'key' => $key,
                'source_lang' => $sourceLang,
                'target_lang' => $targetLang,
            ],
            [
                'translation' => $translation,
                'context' => $context,
                'priority' => $priority,
                'is_active' => true,
            ]
        );
    }

    /**
     * Format สำหรับ API response
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'source_lang' => $this->source_lang,
            'target_lang' => $this->target_lang,
            'translation' => $this->translation,
            'context' => $this->context,
            'is_active' => $this->is_active,
            'priority' => $this->priority,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Scope: ดึงเฉพาะที่ active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: ค้นหาตามภาษา
     */
    public function scopeForLanguagePair($query, string $sourceLang, string $targetLang)
    {
        return $query->where('source_lang', $sourceLang)
            ->where('target_lang', $targetLang);
    }
}
