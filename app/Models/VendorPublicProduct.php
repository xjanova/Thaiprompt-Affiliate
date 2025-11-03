<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPublicProduct extends Model
{
    protected $fillable = [
        'store_id',
        'product_id',
        'requested_by',
        'request_note',
        'reviewed_by',
        'review_note',
        'reviewed_at',
        'status',
        'rejection_reason',
        'is_featured',
        'display_order',
        'featured_until',
        'views_count',
        'clicks_count',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'is_featured' => 'boolean',
        'display_order' => 'integer',
        'featured_until' => 'datetime',
        'views_count' => 'integer',
        'clicks_count' => 'integer',
    ];

    /**
     * Get the store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the user who requested
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the admin who reviewed
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope: Pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Approved products
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope: Rejected products
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope: Featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
            ->where(function($q) {
                $q->whereNull('featured_until')
                  ->orWhere('featured_until', '>', now());
            });
    }

    /**
     * Approve the request
     */
    public function approve(int $reviewerId, string $note = null): bool
    {
        $this->status = 'approved';
        $this->reviewed_by = $reviewerId;
        $this->review_note = $note;
        $this->reviewed_at = now();

        // Update product
        if ($this->product) {
            $this->product->is_public_approved = true;
            $this->product->public_approved_at = now();
            $this->product->public_approved_by = $reviewerId;
            $this->product->save();
        }

        return $this->save();
    }

    /**
     * Reject the request
     */
    public function reject(int $reviewerId, string $reason): bool
    {
        $this->status = 'rejected';
        $this->reviewed_by = $reviewerId;
        $this->rejection_reason = $reason;
        $this->reviewed_at = now();

        return $this->save();
    }

    /**
     * Remove from public listing
     */
    public function removeFromPublic(string $reason = null): bool
    {
        $this->status = 'removed';
        $this->rejection_reason = $reason;

        // Update product
        if ($this->product) {
            $this->product->is_public_approved = false;
            $this->product->save();
        }

        return $this->save();
    }

    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Increment click count
     */
    public function incrementClicks(): void
    {
        $this->increment('clicks_count');
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            'removed' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get status display text
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'รออนุมัติ',
            'approved' => 'อนุมัติแล้ว',
            'rejected' => 'ถูกปฏิเสธ',
            'removed' => 'ถูกลบออก',
            default => $this->status,
        };
    }
}
