<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingFlowaccountConnection extends Model
{
    protected $fillable = [
        'user_id',
        'client_id',
        'client_secret',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'flowaccount_company_id',
        'is_active',
        'auto_sync',
        'last_sync_at',
        'sync_settings',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'auto_sync' => 'boolean',
        'last_sync_at' => 'datetime',
        'sync_settings' => 'array',
    ];

    protected $hidden = [
        'client_secret',
        'access_token',
        'refresh_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function isConnected(): bool
    {
        return $this->is_active && $this->access_token && !$this->isTokenExpired();
    }
}
