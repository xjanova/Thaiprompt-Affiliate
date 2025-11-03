<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeBase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bot_profile_id',
        'name',
        'description',
        'type',
        'source_url',
        'file_path',
        'file_name',
        'file_size',
        'raw_content',
        'total_chunks',
        'total_tokens',
        'status',
        'error_message',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'processed_at' => 'datetime',
        'file_size' => 'integer',
        'total_chunks' => 'integer',
        'total_tokens' => 'integer',
    ];

    /**
     * Get the bot profile that owns this knowledge base
     */
    public function botProfile(): BelongsTo
    {
        return $this->belongsTo(AiBotProfile::class, 'bot_profile_id');
    }

    /**
     * Get all chunks for this knowledge base
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class);
    }

    /**
     * Get RAG usage logs
     */
    public function ragUsageLogs(): HasMany
    {
        return $this->hasMany(RagUsageLog::class);
    }

    /**
     * Scope: Completed knowledge bases
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Failed knowledge bases
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Processing knowledge bases
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Check if knowledge base is ready to use
     */
    public function isReady(): bool
    {
        return $this->status === 'completed' && $this->total_chunks > 0;
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $unit = 0;

        while ($bytes >= 1024 && $unit < count($units) - 1) {
            $bytes /= 1024;
            $unit++;
        }

        return round($bytes, 2) . ' ' . $units[$unit];
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'green',
            'processing' => 'blue',
            'failed' => 'red',
            'pending' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'เสร็จสมบูรณ์',
            'processing' => 'กำลังประมวลผล',
            'failed' => 'ล้มเหลว',
            'pending' => 'รอดำเนินการ',
            default => 'ไม่ทราบสถานะ',
        };
    }

    /**
     * Get type icon
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'text' => '📝',
            'pdf' => '📄',
            'docx' => '📘',
            'url' => '🔗',
            'file' => '📁',
            default => '📄',
        };
    }
}
