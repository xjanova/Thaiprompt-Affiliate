<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemInfo extends Model
{
    use HasFactory;

    protected $table = 'system_info';

    protected $fillable = [
        'version',
        'build_number',
        'installed_at',
        'last_updated_at',
        'php_version',
        'database_version',
        'extensions',
        'is_setup_completed',
        'setup_notes',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'php_version' => 'array',
        'database_version' => 'array',
        'extensions' => 'array',
        'is_setup_completed' => 'boolean',
    ];

    /**
     * Get current system version
     */
    public static function getCurrentVersion(): ?string
    {
        return self::latest()->first()?->version;
    }

    /**
     * Check if system is setup
     */
    public static function isSetupCompleted(): bool
    {
        return self::latest()->first()?->is_setup_completed ?? false;
    }

    /**
     * Mark setup as completed
     */
    public static function markSetupCompleted(): void
    {
        $info = self::latest()->first();
        if ($info) {
            $info->update(['is_setup_completed' => true]);
        }
    }
}
