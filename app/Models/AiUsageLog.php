<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class AiUsageLog extends Model
{
    protected $fillable = [
        'user_id',
        'bot_profile_id',
        'conversation_id',
        'rental_id',
        'provider_id',
        'model_id',
        'request_type',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost',
        'response_time_ms',
        'status',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'cost' => 'decimal:6',
        'metadata' => 'array',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the bot profile
     */
    public function botProfile(): BelongsTo
    {
        return $this->belongsTo(AiBotProfile::class, 'bot_profile_id');
    }

    /**
     * Get the conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    /**
     * Get the rental (if applicable)
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(AiBotRental::class, 'rental_id');
    }

    /**
     * Get the provider
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }

    /**
     * Get the model
     */
    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'model_id');
    }

    /**
     * Scope: For specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: For specific bot
     */
    public function scopeForBot($query, int $botProfileId)
    {
        return $query->where('bot_profile_id', $botProfileId);
    }

    /**
     * Scope: Successful requests
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope: Failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Scope: Date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get usage statistics for a user
     */
    public static function getUserStatistics(int $userId, $startDate = null, $endDate = null): array
    {
        $query = self::forUser($userId);

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return [
            'total_requests' => $query->count(),
            'successful_requests' => $query->where('status', 'success')->count(),
            'failed_requests' => $query->where('status', 'error')->count(),
            'total_tokens' => $query->sum('total_tokens'),
            'prompt_tokens' => $query->sum('prompt_tokens'),
            'completion_tokens' => $query->sum('completion_tokens'),
            'total_cost' => $query->sum('cost'),
            'avg_response_time' => $query->avg('response_time_ms'),
        ];
    }

    /**
     * Get usage statistics for a bot
     */
    public static function getBotStatistics(int $botProfileId, $startDate = null, $endDate = null): array
    {
        $query = self::forBot($botProfileId);

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return [
            'total_requests' => $query->count(),
            'unique_users' => $query->distinct('user_id')->count('user_id'),
            'successful_requests' => $query->where('status', 'success')->count(),
            'failed_requests' => $query->where('status', 'error')->count(),
            'total_tokens' => $query->sum('total_tokens'),
            'total_cost' => $query->sum('cost'),
            'avg_response_time' => $query->avg('response_time_ms'),
        ];
    }

    /**
     * Get daily usage statistics
     */
    public static function getDailyStatistics($startDate, $endDate): array
    {
        return self::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total_requests'),
            DB::raw('SUM(total_tokens) as total_tokens'),
            DB::raw('SUM(cost) as total_cost'),
            DB::raw('AVG(response_time_ms) as avg_response_time')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Get provider usage breakdown
     */
    public static function getProviderBreakdown($startDate = null, $endDate = null): array
    {
        $query = self::select(
            'provider_id',
            DB::raw('COUNT(*) as total_requests'),
            DB::raw('SUM(total_tokens) as total_tokens'),
            DB::raw('SUM(cost) as total_cost')
        )
            ->groupBy('provider_id');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query->with('provider:id,display_name')
            ->get()
            ->toArray();
    }

    /**
     * Get model usage breakdown
     */
    public static function getModelBreakdown($startDate = null, $endDate = null): array
    {
        $query = self::select(
            'model_id',
            DB::raw('COUNT(*) as total_requests'),
            DB::raw('SUM(total_tokens) as total_tokens'),
            DB::raw('SUM(cost) as total_cost')
        )
            ->groupBy('model_id');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query->with('model:id,display_name')
            ->get()
            ->toArray();
    }
}
