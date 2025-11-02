<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineLoginLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'line_user_id',
        'action',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the log
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a LINE action
     */
    public static function logAction(
        string $lineUserId,
        string $action,
        ?int $userId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'line_user_id' => $lineUserId,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
