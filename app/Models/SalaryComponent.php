<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryComponent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'component_name',
        'component_type',
        'calculation_type',
        'amount',
        'percentage',
        'is_taxable',
        'is_recurring',
        'effective_from',
        'effective_to',
        'is_active',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_recurring' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Calculate component value based on basic salary
     */
    public function calculateValue($basicSalary)
    {
        return match($this->calculation_type) {
            'fixed' => $this->amount,
            'percentage' => $basicSalary * ($this->percentage / 100),
            default => $this->amount
        };
    }

    /**
     * Check if component is currently active
     */
    public function isCurrentlyActive()
    {
        $now = now();

        if (!$this->is_active) {
            return false;
        }

        if ($this->effective_from && $now->lessThan($this->effective_from)) {
            return false;
        }

        if ($this->effective_to && $now->greaterThan($this->effective_to)) {
            return false;
        }

        return true;
    }

    /**
     * Scope: Active components
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', now());
                    })
                    ->where('effective_from', '<=', now());
    }

    /**
     * Scope: Earnings
     */
    public function scopeEarnings($query)
    {
        return $query->where('component_type', 'earning');
    }

    /**
     * Scope: Deductions
     */
    public function scopeDeductions($query)
    {
        return $query->where('component_type', 'deduction');
    }
}
