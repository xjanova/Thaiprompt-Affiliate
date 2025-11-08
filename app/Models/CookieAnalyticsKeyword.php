<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CookieAnalyticsKeyword extends Model
{
    use HasFactory;

    protected $fillable = [
        'keyword',
        'search_count',
        'unique_sessions',
        'related_interests',
        'date',
    ];

    protected $casts = [
        'related_interests' => 'array',
        'date' => 'date',
    ];

    /**
     * เพิ่มหรืออัปเดตคีย์เวิร์ด
     */
    public static function recordKeyword(string $keyword, string $sessionId): void
    {
        $today = now()->toDateString();

        $record = self::firstOrNew([
            'keyword' => strtolower(trim($keyword)),
            'date' => $today,
        ]);

        if ($record->exists) {
            $record->increment('search_count');
        } else {
            $record->search_count = 1;
            $record->unique_sessions = 1;
        }

        $record->save();
    }

    /**
     * ดึงคีย์เวิร์ดยอดนิยม
     */
    public static function getTopKeywords(int $limit = 20, ?string $startDate = null, ?string $endDate = null)
    {
        $query = self::query();

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        return $query->selectRaw('keyword, SUM(search_count) as total_searches, SUM(unique_sessions) as total_sessions')
            ->groupBy('keyword')
            ->orderByDesc('total_searches')
            ->limit($limit)
            ->get();
    }
}
