<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CookieConsent extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'ip_address',
        'user_agent',
        'necessary',
        'analytics',
        'marketing',
        'preferences',
        'consented_at',
    ];

    protected $casts = [
        'necessary' => 'boolean',
        'analytics' => 'boolean',
        'marketing' => 'boolean',
        'preferences' => 'boolean',
        'consented_at' => 'datetime',
    ];
}
