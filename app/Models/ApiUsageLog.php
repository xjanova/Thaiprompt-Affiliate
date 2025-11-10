<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_key_id',
        'api_endpoint_id',
        'method',
        'path',
        'ip_address',
        'user_agent',
        'response_code',
        'response_time_ms',
        'request_payload',
        'response_payload',
        'error_message',
    ];

    protected $casts = [
        'response_code' => 'integer',
        'response_time_ms' => 'integer',
    ];

    /**
     * Get the API key
     */
    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class);
    }

    /**
     * Get the API endpoint
     */
    public function apiEndpoint()
    {
        return $this->belongsTo(ApiEndpoint::class);
    }

    /**
     * Scope for successful requests
     */
    public function scopeSuccessful($query)
    {
        return $query->whereBetween('response_code', [200, 299]);
    }

    /**
     * Scope for failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('response_code', '>=', 400);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Scope for today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope for this month
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    /**
     * Check if request was successful
     */
    public function isSuccessful()
    {
        return $this->response_code >= 200 && $this->response_code < 300;
    }

    /**
     * Get response time in seconds
     */
    public function getResponseTimeSecondsAttribute()
    {
        return $this->response_time_ms ? $this->response_time_ms / 1000 : null;
    }
}
