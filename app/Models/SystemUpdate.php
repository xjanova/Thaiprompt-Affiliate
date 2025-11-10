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

    /**
     * Parse changelog from markdown to structured array
     */
    public function getParsedChangelogAttribute()
    {
        if (!$this->changelog) {
            return [];
        }

        $sections = [];
        $currentSection = null;
        $lines = explode("\n", $this->changelog);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines
            if (empty($line)) {
                continue;
            }

            // Check for section headers (## New Features, ## Bug Fixes, etc.)
            if (preg_match('/^##\s+(.+)$/i', $line, $matches)) {
                $sectionName = trim($matches[1]);
                $currentSection = $this->normalizeSectionName($sectionName);
                $sections[$currentSection] = [];
                continue;
            }

            // Check for list items (- item or * item)
            if (preg_match('/^[\-\*]\s+(.+)$/i', $line, $matches)) {
                $item = trim($matches[1]);
                if ($currentSection) {
                    $sections[$currentSection][] = $item;
                } else {
                    // If no section defined, add to 'changes'
                    if (!isset($sections['changes'])) {
                        $sections['changes'] = [];
                    }
                    $sections['changes'][] = $item;
                }
            }
        }

        return $sections;
    }

    /**
     * Normalize section name
     */
    protected function normalizeSectionName($name)
    {
        $normalized = strtolower($name);

        $mappings = [
            'new features' => 'features',
            'features' => 'features',
            'additions' => 'features',
            'bug fixes' => 'fixes',
            'fixes' => 'fixes',
            'bugfixes' => 'fixes',
            'improvements' => 'improvements',
            'enhancements' => 'improvements',
            'changes' => 'changes',
            'breaking changes' => 'breaking',
            'security' => 'security',
            'deprecated' => 'deprecated',
            'removed' => 'removed',
        ];

        return $mappings[$normalized] ?? 'other';
    }

    /**
     * Get formatted changelog for display
     */
    public function getFormattedChangelogAttribute()
    {
        $parsed = $this->parsed_changelog;

        if (empty($parsed)) {
            return $this->changelog;
        }

        $formatted = '';

        $sectionTitles = [
            'features' => '🎉 New Features',
            'fixes' => '🐛 Bug Fixes',
            'improvements' => '✨ Improvements',
            'security' => '🔒 Security',
            'breaking' => '⚠️ Breaking Changes',
            'deprecated' => '⚠️ Deprecated',
            'removed' => '🗑️ Removed',
            'changes' => '📝 Changes',
            'other' => '📌 Other',
        ];

        foreach ($parsed as $section => $items) {
            $title = $sectionTitles[$section] ?? ucfirst($section);
            $formatted .= "**{$title}**\n";
            foreach ($items as $item) {
                $formatted .= "- {$item}\n";
            }
            $formatted .= "\n";
        }

        return $formatted;
    }
}
