<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'current_version',
        'latest_version',
        'release_notes',
        'download_url',
        'update_available',
        'is_critical',
        'last_checked_at',
        'last_updated_at',
        'github_repo',
        'changelog',
        'breaking_changes',
    ];

    protected $casts = [
        'update_available' => 'boolean',
        'is_critical' => 'boolean',
        'last_checked_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'changelog' => 'array',
        'breaking_changes' => 'array',
    ];

    // Get the current system version
    public static function getCurrentVersion(): string
    {
        return self::first()->current_version ?? '1.0.0';
    }

    // Check if update is available
    public static function hasUpdateAvailable(): bool
    {
        return self::first()->update_available ?? false;
    }

    // Get latest version info
    public static function getLatestVersionInfo(): ?self
    {
        return self::first();
    }

    // Compare versions
    public function isNewerVersion(string $version): bool
    {
        return version_compare($version, $this->current_version, '>');
    }

    // Update logs
    public function updateLogs()
    {
        return $this->hasMany(SystemUpdateLog::class);
    }
}
