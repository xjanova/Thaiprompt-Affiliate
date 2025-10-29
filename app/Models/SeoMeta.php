<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoMeta extends Model
{
    use HasFactory;

    protected $table = 'seo_meta';

    protected $fillable = [
        'page_type',
        'language',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'structured_data',
        'canonical_url',
        'index',
        'follow',
    ];

    protected $casts = [
        'index' => 'boolean',
        'follow' => 'boolean',
        'structured_data' => 'array',
    ];

    /**
     * Get SEO meta by page type and language
     */
    public static function getByPage(string $pageType, string $language = null): ?self
    {
        $language = $language ?? app()->getLocale();

        return self::where('page_type', $pageType)
            ->where('language', $language)
            ->first();
    }

    /**
     * Get or create SEO meta for a page
     */
    public static function getOrCreateForPage(string $pageType, string $language = null): self
    {
        $language = $language ?? app()->getLocale();

        return self::firstOrCreate(
            [
                'page_type' => $pageType,
                'language' => $language,
            ],
            [
                'meta_title' => config('app.name'),
                'meta_description' => '',
                'index' => true,
                'follow' => true,
            ]
        );
    }

    /**
     * Get robots meta tag value
     */
    public function getRobotsAttribute(): string
    {
        $parts = [];

        if ($this->index) {
            $parts[] = 'index';
        } else {
            $parts[] = 'noindex';
        }

        if ($this->follow) {
            $parts[] = 'follow';
        } else {
            $parts[] = 'nofollow';
        }

        return implode(', ', $parts);
    }
}
