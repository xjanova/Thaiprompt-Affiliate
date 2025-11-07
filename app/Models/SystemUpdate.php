<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'version_name',
        'description',
        'changelog',
        'type',
        'priority',
        'is_pre_release',
        'is_stable',
        'released_at',
        'min_php_version',
        'min_mysql_version',
        'required_extensions',
        'download_url',
        'repository_url',
        'documentation_url',
        'pre_update_instructions',
        'post_update_instructions',
        'breaking_changes',
        'requires_migration',
        'migration_files',
        'download_count',
        'install_count',
    ];

    protected $casts = [
        'is_pre_release' => 'boolean',
        'is_stable' => 'boolean',
        'released_at' => 'datetime',
        'required_extensions' => 'array',
        'breaking_changes' => 'array',
        'requires_migration' => 'boolean',
        'migration_files' => 'array',
        'download_count' => 'integer',
        'install_count' => 'integer',
    ];

    /**
     * Get update logs
     */
    public function logs()
    {
        return $this->hasMany(UpdateLog::class);
    }

    /**
     * Get notifications
     */
    public function notifications()
    {
        return $this->hasMany(UpdateNotification::class);
    }

    /**
     * Increment download count
     */
    public function incrementDownloads()
    {
        $this->increment('download_count');
    }

    /**
     * Increment install count
     */
    public function incrementInstalls()
    {
        $this->increment('install_count');
    }

    /**
     * Check if version is newer than current
     */
    public function isNewerThan($version)
    {
        return version_compare($this->version, $version, '>');
    }

    /**
     * Check if system meets requirements
     */
    public function meetsRequirements()
    {
        // Check PHP version
        if (version_compare(PHP_VERSION, $this->min_php_version, '<')) {
            return false;
        }

        // Check MySQL version (would need actual implementation)
        // This is a placeholder

        // Check required PHP extensions
        if ($this->required_extensions) {
            foreach ($this->required_extensions as $extension) {
                if (!extension_loaded($extension)) {
                    return false;
                }
            }
        }

        return true;
    }
}
