<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class QrBarcode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'format',
        'content',
        'settings',
        'title',
        'description',
        'category',
        'tags',
        'is_favorite',
        'is_public',
        'file_path',
        'thumbnail_path',
        'scan_count',
        'last_scanned_at',
        'scan_analytics',
        'short_url',
    ];

    protected $casts = [
        'settings' => 'array',
        'tags' => 'array',
        'scan_analytics' => 'array',
        'is_favorite' => 'boolean',
        'is_public' => 'boolean',
        'last_scanned_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->short_url) {
                $model->short_url = static::generateUniqueShortUrl();
            }
        });
    }

    /**
     * Generate unique short URL
     */
    protected static function generateUniqueShortUrl(): string
    {
        do {
            $shortUrl = Str::random(8);
        } while (static::where('short_url', $shortUrl)->exists());

        return $shortUrl;
    }

    /**
     * Get the user that owns the QR/Barcode
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get scans for this QR/Barcode
     */
    public function scans()
    {
        return $this->hasMany(QrBarcodeScan::class);
    }

    /**
     * Increment scan count
     */
    public function incrementScanCount()
    {
        $this->increment('scan_count');
        $this->update(['last_scanned_at' => now()]);
    }

    /**
     * Get public QR/Barcodes
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Get favorites
     */
    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Get by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get by category
     */
    public function scopeInCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Search by tags
     */
    public function scopeWithTag($query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * Get popular QR/Barcodes
     */
    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('scan_count', 'desc')->limit($limit);
    }

    /**
     * Get recent QR/Barcodes
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Get full URL for short URL
     */
    public function getShortUrlFullAttribute()
    {
        return route('qr-barcode.redirect', $this->short_url);
    }

    /**
     * Get display name
     */
    public function getDisplayNameAttribute()
    {
        return $this->title ?: ($this->type === 'qrcode' ? 'QR Code' : 'Barcode') . ' #' . $this->id;
    }
}
