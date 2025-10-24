<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorFeatureUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'vendor_feature_id',
        'vendor_feature_purchase_id',
        'action',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the feature
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(VendorFeature::class, 'vendor_feature_id');
    }

    /**
     * Get the purchase
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(VendorFeaturePurchase::class, 'vendor_feature_purchase_id');
    }

    /**
     * Log an action
     */
    public static function logAction(
        int $vendorId,
        int $featureId,
        string $action,
        ?int $purchaseId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'vendor_id' => $vendorId,
            'vendor_feature_id' => $featureId,
            'vendor_feature_purchase_id' => $purchaseId,
            'action' => $action,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Scope by action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
