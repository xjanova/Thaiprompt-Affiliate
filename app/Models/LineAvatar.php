<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class LineAvatar extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'file_path',
        'file_url',
        'file_size',
        'source_type',
        'description',
        'is_active',
        'is_default',
        'created_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get public URL
     */
    public function getPublicUrl(): string
    {
        if ($this->file_url) {
            return $this->file_url;
        }

        return Storage::url($this->file_path);
    }

    /**
     * Get file size in human readable format
     */
    public function getHumanReadableSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Set as default
     */
    public function setAsDefault(): void
    {
        static::where('is_default', true)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    /**
     * Get type options
     */
    public static function getTypes(): array
    {
        return [
            'image' => 'รูปภาพ (PNG, JPG)',
            'gif' => 'GIF Animation',
            'lottie' => 'Lottie Animation',
            'video' => 'วิดีโอ',
        ];
    }

    /**
     * Scope: Active avatars
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Default avatar
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
