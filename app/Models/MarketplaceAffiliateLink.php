<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MarketplaceAffiliateLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'platform_id',
        'short_code',
        'original_url',
        'affiliate_url',
        'tracking_url',
        'click_count',
        'unique_click_count',
        'conversion_count',
        'total_sales',
        'total_commission',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'custom_params',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'total_sales' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'custom_params' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($link) {
            if (empty($link->short_code)) {
                $link->short_code = static::generateUniqueShortCode();
            }
        });
    }

    /**
     * Generate a unique short code
     */
    protected static function generateUniqueShortCode(): string
    {
        do {
            $code = Str::random(10);
        } while (static::where('short_code', $code)->exists());

        return $code;
    }

    /**
     * Get the user that owns this link
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product for this link
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'product_id');
    }

    /**
     * Get the platform for this link
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(MarketplacePlatform::class, 'platform_id');
    }

    /**
     * Get all clicks for this link
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(MarketplaceLinkClick::class, 'affiliate_link_id');
    }

    /**
     * Get all orders generated from this link
     */
    public function orders(): HasMany
    {
        return $this->hasMany(MarketplaceOrder::class, 'affiliate_link_id');
    }

    /**
     * Get all commissions generated from this link
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(MarketplaceCommission::class, 'affiliate_link_id');
    }

    /**
     * Scope to get only active links
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    /**
     * Scope by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get full tracking URL
     */
    public function getFullTrackingUrlAttribute(): string
    {
        return url("/marketplace/redirect/{$this->short_code}");
    }

    /**
     * Increment click count
     */
    public function incrementClicks(bool $isUnique = false)
    {
        $this->increment('click_count');

        if ($isUnique) {
            $this->increment('unique_click_count');
        }
    }

    /**
     * Increment conversion count
     */
    public function incrementConversions()
    {
        $this->increment('conversion_count');
    }

    /**
     * Calculate conversion rate
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->click_count == 0) {
            return 0;
        }

        return ($this->conversion_count / $this->click_count) * 100;
    }
}
