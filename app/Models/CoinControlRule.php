<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinControlRule extends Model
{
    use HasFactory;

    protected $table = 'coin_control_rules';

    protected $fillable = [
        'token_id',
        'rule_type',
        'conditions',
        'actions',
        'priority',
        'is_active',
        'executed_count',
        'last_executed_at',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
        'last_executed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function token()
    {
        return $this->belongsTo(TPIXToken::class, 'token_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('rule_type', $type);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Helpers
     */
    public function checkConditions(array $context): bool
    {
        if (!$this->is_active) {
            return false;
        }

        foreach ($this->conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            if (!$field || !isset($context[$field])) {
                continue;
            }

            $contextValue = $context[$field];

            $result = match($operator) {
                '=' => $contextValue == $value,
                '!=' => $contextValue != $value,
                '>' => $contextValue > $value,
                '<' => $contextValue < $value,
                '>=' => $contextValue >= $value,
                '<=' => $contextValue <= $value,
                'in' => in_array($contextValue, (array)$value),
                'not_in' => !in_array($contextValue, (array)$value),
                default => false,
            };

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    public function execute()
    {
        $this->increment('executed_count');
        $this->update(['last_executed_at' => now()]);
    }

    public function getDescription(): string
    {
        $descriptions = [
            'auto_mint' => 'Automatically mint tokens when conditions are met',
            'auto_burn' => 'Automatically burn tokens when conditions are met',
            'freeze_on_threshold' => 'Freeze address when threshold is exceeded',
            'pause_on_volume' => 'Pause contract when volume exceeds limit',
        ];

        return $descriptions[$this->rule_type] ?? 'Custom rule';
    }
}
