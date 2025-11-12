<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class KbArticleAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'filename',
        'path',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    protected $appends = ['url', 'formatted_size'];

    /**
     * Get the article this attachment belongs to
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(KbArticle::class, 'article_id');
    }

    /**
     * Get the full URL to the file
     */
    public function getUrlAttribute()
    {
        return Storage::url($this->path);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Delete file from storage
     */
    public function deleteFile()
    {
        if (Storage::exists($this->path)) {
            Storage::delete($this->path);
        }

        $this->delete();
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Delete files when model is deleted
        static::deleting(function ($attachment) {
            if (Storage::exists($attachment->path)) {
                Storage::delete($attachment->path);
            }
        });
    }
}
