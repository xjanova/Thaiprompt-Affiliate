<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'default_days_per_year',
        'is_paid',
        'requires_approval',
        'requires_document',
        'max_consecutive_days',
        'min_advance_notice_days',
        'carry_forward',
        'max_carry_forward_days',
        'color',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_days_per_year' => 'decimal:2',
        'is_paid' => 'boolean',
        'requires_approval' => 'boolean',
        'requires_document' => 'boolean',
        'max_consecutive_days' => 'integer',
        'min_advance_notice_days' => 'integer',
        'carry_forward' => 'boolean',
        'max_carry_forward_days' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get all leave requests of this type
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Scope: Active leave types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
