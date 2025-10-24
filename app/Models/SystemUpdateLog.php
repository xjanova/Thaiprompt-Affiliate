<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemUpdateLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'version_from',
        'version_to',
        'update_status',
        'updated_by',
        'started_at',
        'completed_at',
        'error_message',
        'update_steps',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'update_steps' => 'array',
    ];

    // Relationships
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('update_status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('update_status', 'failed');
    }
}
