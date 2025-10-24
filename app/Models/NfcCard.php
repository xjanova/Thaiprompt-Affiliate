<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NfcCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'card_uid',
        'user_id',
        'wallet_id',
        'card_type',
        'card_name',
        'is_active',
        'last_used_at',
        'linked_at',
        'linked_by',
        'notes',
        'meta_data',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'linked_at' => 'datetime',
        'meta_data' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function linkedBy()
    {
        return $this->belongsTo(User::class, 'linked_by');
    }

    public function transactions()
    {
        return $this->hasMany(NfcCardTransaction::class);
    }

    // Helper Methods
    public function isLinked(): bool
    {
        return $this->user_id !== null;
    }

    public function canBeUsed(): bool
    {
        return $this->is_active && $this->isLinked();
    }

    public function linkToUser(User $user, User $admin): bool
    {
        $this->user_id = $user->id;
        $this->wallet_id = $user->wallet?->id;
        $this->linked_at = now();
        $this->linked_by = $admin->id;

        return $this->save();
    }

    public function unlink(): bool
    {
        $this->user_id = null;
        $this->wallet_id = null;
        $this->linked_at = null;

        return $this->save();
    }

    public function updateLastUsed(): void
    {
        $this->last_used_at = now();
        $this->save();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLinked($query)
    {
        return $query->whereNotNull('user_id');
    }

    public function scopeUnlinked($query)
    {
        return $query->whereNull('user_id');
    }
}
