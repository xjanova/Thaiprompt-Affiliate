<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LineBotKnowledgeBase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ai_setting_id',
        'name',
        'type',
        'source',
        'content',
        'file_path',
        'file_type',
        'priority',
        'is_active',
        'last_synced_at',
        'metadata',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get AI setting
     */
    public function aiSetting()
    {
        return $this->belongsTo(LineBotAiSetting::class, 'ai_setting_id');
    }

    /**
     * Get formatted content based on type
     */
    public function getFormattedContent(): string
    {
        switch ($this->type) {
            case 'url':
                return "URL: {$this->source}\n" . ($this->content ?? '');
            case 'file':
                return "File: {$this->file_path}\n" . ($this->content ?? '');
            case 'text':
            case 'internal':
            default:
                return $this->content ?? '';
        }
    }

    /**
     * Check if needs syncing
     */
    public function needsSync(): bool
    {
        if ($this->type !== 'url') {
            return false;
        }

        if (!$this->last_synced_at) {
            return true;
        }

        // Sync every 24 hours
        return $this->last_synced_at->diffInHours(now()) >= 24;
    }
}
