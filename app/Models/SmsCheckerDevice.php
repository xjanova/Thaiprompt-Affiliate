<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsCheckerDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'device_name',
        'api_key',
        'secret_key',
        'platform',
        'app_version',
        'status',
        'last_active_at',
        'user_id',
        'ip_address',
    ];

    protected $hidden = [
        'api_key',
        'secret_key',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifications()
    {
        return $this->hasMany(SmsPaymentNotification::class, 'device_id', 'device_id');
    }

    public static function findByApiKey(string $apiKey): ?self
    {
        return static::where('api_key', $apiKey)->first();
    }

    public static function generateApiKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function generateSecretKey(): string
    {
        return bin2hex(random_bytes(32));
    }
}
