<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosAdvertisement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'title',
        'description',
        'type',
        'image_url',
        'video_url',
        'html_content',
        'promotion_code',
        'discount_amount',
        'discount_percentage',
        'promotion_start',
        'promotion_end',
        'duration_seconds',
        'order',
        'is_active',
        'show_on_idle_only',
        'start_date',
        'end_date',
        'display_hours',
        'display_days',
        'target_devices',
        'target_conditions',
        'view_count',
        'click_count',
        'last_displayed_at',
        'link_url',
        'link_opens_new_tab',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'promotion_start' => 'datetime',
        'promotion_end' => 'datetime',
        'is_active' => 'boolean',
        'show_on_idle_only' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'display_hours' => 'array',
        'display_days' => 'array',
        'target_devices' => 'array',
        'target_conditions' => 'array',
        'last_displayed_at' => 'datetime',
        'link_opens_new_tab' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeScheduled($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('start_date')
              ->orWhere('start_date', '<=', now());
        })->where(function ($q) {
            $q->whereNull('end_date')
              ->orWhere('end_date', '>=', now());
        });
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeForDevice($query, $deviceId)
    {
        return $query->where(function ($q) use ($deviceId) {
            $q->whereNull('target_devices')
              ->orWhereJsonContains('target_devices', $deviceId);
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Helper Methods
     */
    public function isActiveNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check date range
        if ($this->start_date && $this->start_date->isFuture()) {
            return false;
        }
        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }

        // Check promotion validity
        if ($this->type === 'promotion') {
            if ($this->promotion_start && $this->promotion_start->isFuture()) {
                return false;
            }
            if ($this->promotion_end && $this->promotion_end->isPast()) {
                return false;
            }
        }

        // Check display hours
        if ($this->display_hours && !empty($this->display_hours)) {
            $currentHour = now()->hour;
            if (!in_array($currentHour, $this->display_hours)) {
                return false;
            }
        }

        // Check display days (0 = Sunday, 6 = Saturday)
        if ($this->display_days && !empty($this->display_days)) {
            $currentDay = now()->dayOfWeek;
            if (!in_array($currentDay, $this->display_days)) {
                return false;
            }
        }

        return true;
    }

    public function isPromotionValid(): bool
    {
        if ($this->type !== 'promotion') {
            return false;
        }

        if ($this->promotion_start && $this->promotion_start->isFuture()) {
            return false;
        }

        if ($this->promotion_end && $this->promotion_end->isPast()) {
            return false;
        }

        return true;
    }

    public function canDisplayOnDevice(?int $deviceId = null): bool
    {
        if (!$this->isActiveNow()) {
            return false;
        }

        // Check device targeting
        if ($deviceId && $this->target_devices && !empty($this->target_devices)) {
            return in_array($deviceId, $this->target_devices);
        }

        return true;
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
        $this->update(['last_displayed_at' => now()]);
    }

    public function incrementClickCount(): void
    {
        $this->increment('click_count');
    }

    public function getMediaUrl(): ?string
    {
        return match ($this->type) {
            'image' => $this->image_url,
            'video' => $this->video_url,
            default => null,
        };
    }

    public function getContent(): ?string
    {
        return match ($this->type) {
            'html' => $this->html_content,
            'promotion' => $this->buildPromotionContent(),
            default => $this->description,
        };
    }

    protected function buildPromotionContent(): string
    {
        $discount = $this->discount_percentage
            ? "{$this->discount_percentage}% OFF"
            : "฿{$this->discount_amount} OFF";

        $validity = $this->promotion_end
            ? "Valid until " . $this->promotion_end->format('M d, Y')
            : "Limited time offer";

        return "{$this->title}\n{$discount}\n{$validity}";
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ad) {
            if ($ad->duration_seconds === null) {
                $ad->duration_seconds = 10;
            }
        });
    }
}
