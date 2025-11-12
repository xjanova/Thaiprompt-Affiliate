<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class TicketAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'attachable_id',
        'attachable_type',
        'uploaded_by',
        'filename',
        'original_filename',
        'path',
        'mime_type',
        'size',
        'thumbnail_path',
        'hash',
        'is_scanned',
    ];

    protected $casts = [
        'size' => 'integer',
        'is_scanned' => 'boolean',
    ];

    protected $appends = ['url', 'thumbnail_url', 'formatted_size'];

    /**
     * Get the parent attachable model (Ticket or TicketReply)
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded this file
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the full URL to the file
     */
    public function getUrlAttribute()
    {
        return Storage::url($this->path);
    }

    /**
     * Get the full URL to the thumbnail
     */
    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail_path) {
            return Storage::url($this->thumbnail_path);
        }

        // Return default thumbnail based on file type
        return $this->getDefaultThumbnail();
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
     * Check if file is an image
     */
    public function isImage()
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if file is a PDF
     */
    public function isPdf()
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Check if file is a document
     */
    public function isDocument()
    {
        $documentMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
        ];

        return in_array($this->mime_type, $documentMimes);
    }

    /**
     * Get default thumbnail based on file type
     */
    private function getDefaultThumbnail()
    {
        if ($this->isImage()) {
            return $this->url; // Use the image itself
        }

        if ($this->isPdf()) {
            return '/images/file-types/pdf.svg';
        }

        if ($this->isDocument()) {
            return '/images/file-types/document.svg';
        }

        return '/images/file-types/file.svg';
    }

    /**
     * Get file extension
     */
    public function getExtensionAttribute()
    {
        return pathinfo($this->original_filename, PATHINFO_EXTENSION);
    }

    /**
     * Get file icon class
     */
    public function getIconClassAttribute()
    {
        $extension = strtolower($this->extension);

        return match($extension) {
            'pdf' => 'fa-file-pdf text-red-500',
            'doc', 'docx' => 'fa-file-word text-blue-500',
            'xls', 'xlsx' => 'fa-file-excel text-green-500',
            'ppt', 'pptx' => 'fa-file-powerpoint text-orange-500',
            'zip', 'rar', '7z' => 'fa-file-archive text-yellow-500',
            'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp' => 'fa-file-image text-purple-500',
            'mp4', 'avi', 'mov' => 'fa-file-video text-pink-500',
            'mp3', 'wav' => 'fa-file-audio text-indigo-500',
            'txt' => 'fa-file-alt text-gray-500',
            default => 'fa-file text-gray-400',
        };
    }

    /**
     * Delete file from storage
     */
    public function deleteFile()
    {
        if (Storage::exists($this->path)) {
            Storage::delete($this->path);
        }

        if ($this->thumbnail_path && Storage::exists($this->thumbnail_path)) {
            Storage::delete($this->thumbnail_path);
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

            if ($attachment->thumbnail_path && Storage::exists($attachment->thumbnail_path)) {
                Storage::delete($attachment->thumbnail_path);
            }
        });
    }
}
