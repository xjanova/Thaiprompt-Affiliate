<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebPConversionStat extends Model
{
    protected $table = 'webp_conversion_stats';

    protected $fillable = [
        'total_converted',
        'total_from_uploads',
        'total_from_batch',
        'last_upload_conversion',
        'last_batch_conversion',
    ];

    protected $casts = [
        'last_upload_conversion' => 'datetime',
        'last_batch_conversion' => 'datetime',
    ];

    /**
     * Get the single instance (singleton pattern)
     */
    public static function getInstance()
    {
        return self::first() ?? self::create([
            'total_converted' => 0,
            'total_from_uploads' => 0,
            'total_from_batch' => 0,
        ]);
    }

    /**
     * Increment upload conversion count
     */
    public static function incrementUploadConversion(int $count = 1)
    {
        $stat = self::getInstance();
        $stat->increment('total_from_uploads', $count);
        $stat->increment('total_converted', $count);
        $stat->update(['last_upload_conversion' => now()]);
        return $stat;
    }

    /**
     * Increment batch conversion count
     */
    public static function incrementBatchConversion(int $count = 1)
    {
        $stat = self::getInstance();
        $stat->increment('total_from_batch', $count);
        $stat->increment('total_converted', $count);
        $stat->update(['last_batch_conversion' => now()]);
        return $stat;
    }

    /**
     * Get total conversion stats
     */
    public static function getStats(): array
    {
        $stat = self::getInstance();
        return [
            'total_converted' => $stat->total_converted,
            'total_from_uploads' => $stat->total_from_uploads,
            'total_from_batch' => $stat->total_from_batch,
            'last_upload_conversion' => $stat->last_upload_conversion,
            'last_batch_conversion' => $stat->last_batch_conversion,
        ];
    }
}
