<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TranslationCache Model
 *
 * Cache สำหรับ Google Translate API
 *
 * @property int $id
 * @property string $source_language
 * @property string $target_language
 * @property string $source_text
 * @property string $translated_text
 * @property string $text_hash
 * @property string $api_provider
 * @property \Carbon\Carbon|null $api_call_timestamp
 * @property int $hit_count
 * @property \Carbon\Carbon $last_used_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class TranslationCache extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'translation_cache';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'source_language',
        'target_language',
        'source_text',
        'translated_text',
        'text_hash',
        'api_provider',
        'api_call_timestamp',
        'hit_count',
        'last_used_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hit_count' => 'integer',
        'api_call_timestamp' => 'datetime',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * สร้าง hash สำหรับ cache key
     *
     * @param string $text
     * @param string $sourceLang
     * @param string $targetLang
     * @return string
     */
    public static function generateHash(string $text, string $sourceLang, string $targetLang): string
    {
        return hash('sha256', $text . '|' . $sourceLang . '|' . $targetLang);
    }

    /**
     * ดึง cache ถ้ามี
     *
     * @param string $text
     * @param string $sourceLang
     * @param string $targetLang
     * @return static|null
     */
    public static function getCache(string $text, string $sourceLang, string $targetLang): ?static
    {
        $hash = static::generateHash($text, $sourceLang, $targetLang);

        $cache = static::where('text_hash', $hash)
            ->where('source_language', $sourceLang)
            ->where('target_language', $targetLang)
            ->first();

        if ($cache) {
            // อัปเดต hit count และ last_used_at
            $cache->increment('hit_count');
            $cache->update(['last_used_at' => now()]);
        }

        return $cache;
    }

    /**
     * บันทึก cache ใหม่
     *
     * @param string $sourceText
     * @param string $translatedText
     * @param string $sourceLang
     * @param string $targetLang
     * @return static
     */
    public static function saveCache(
        string $sourceText,
        string $translatedText,
        string $sourceLang,
        string $targetLang
    ): static {
        $hash = static::generateHash($sourceText, $sourceLang, $targetLang);

        return static::create([
            'source_language' => $sourceLang,
            'target_language' => $targetLang,
            'source_text' => $sourceText,
            'translated_text' => $translatedText,
            'text_hash' => $hash,
            'api_provider' => 'google_translate',
            'api_call_timestamp' => now(),
            'hit_count' => 1,
            'last_used_at' => now(),
        ]);
    }

    /**
     * ล้าง cache เก่าที่ไม่ได้ใช้งาน
     *
     * @param int $days
     * @return int
     */
    public static function clearOldCache(int $days = 30): int
    {
        $cutoffDate = now()->subDays($days);

        return static::where('last_used_at', '<', $cutoffDate)->delete();
    }
}
