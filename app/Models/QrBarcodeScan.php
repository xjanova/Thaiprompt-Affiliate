<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QrBarcodeScan extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'qr_barcode_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'country',
        'city',
        'referrer',
        'scanned_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'scanned_at' => 'datetime',
    ];

    /**
     * Get the QR/Barcode that was scanned
     */
    public function qrBarcode()
    {
        return $this->belongsTo(QrBarcode::class);
    }

    /**
     * Get scans by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('scanned_at', [$startDate, $endDate]);
    }

    /**
     * Get scans by device type
     */
    public function scopeByDevice($query, $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Get scans by country
     */
    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }
}
