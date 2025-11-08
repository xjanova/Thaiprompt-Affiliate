<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_name',
        'app_short_name',
        'app_description',
        'app_version',
        'app_build_number',
        'force_update',
        'min_supported_version',
        'latest_version',
        'update_message_th',
        'update_message_en',
        'app_store_url',
        'play_store_url',
        'support_email',
        'support_phone',
        'support_line_id',
        'privacy_policy_url',
        'terms_url',
        'default_language',
        'supported_languages',
        'currency',
        'timezone',
        'is_active',
    ];

    protected $casts = [
        'force_update' => 'boolean',
        'is_active' => 'boolean',
        'supported_languages' => 'array',
    ];

    /**
     * Get the singleton instance
     */
    public static function getInstance()
    {
        return static::firstOrCreate([], [
            'app_name' => 'TP Affiliate',
            'app_version' => '1.0.0',
            'app_build_number' => '1',
            'default_language' => 'th',
            'currency' => 'THB',
            'timezone' => 'Asia/Bangkok',
            'is_active' => true,
        ]);
    }
}
