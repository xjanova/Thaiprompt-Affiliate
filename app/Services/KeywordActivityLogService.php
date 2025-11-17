<?php

namespace App\Services;

use App\Models\LineBotKeyword;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Keyword Activity Logging Service
 *
 * บันทึกและติดตามการใช้งาน keywords
 */
class KeywordActivityLogService
{
    /**
     * บันทึก keyword match
     *
     * @param LineBotKeyword $keyword
     * @param string $userMessage ข้อความของผู้ใช้
     * @param string $lineUserId LINE User ID
     * @return void
     */
    public function logKeywordMatch(LineBotKeyword $keyword, string $userMessage, string $lineUserId): void
    {
        try {
            DB::table('keyword_activity_logs')->insert([
                'keyword_id' => $keyword->id,
                'keyword_name' => $keyword->keyword,
                'user_message' => $userMessage,
                'line_user_id' => $lineUserId,
                'response_type' => $keyword->response_type,
                'category' => $keyword->category,
                'priority' => $keyword->priority,
                'action_type' => 'matched',
                'timestamp' => now(),
                'created_at' => now(),
            ]);

            // Update keyword usage counter
            $keyword->increment('times_matched');

        } catch (\Exception $e) {
            Log::error('Failed to log keyword match', [
                'keyword_id' => $keyword->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * บันทึก no match (ไปให้ AI)
     *
     * @param string $userMessage ข้อความของผู้ใช้
     * @param string $lineUserId LINE User ID
     * @return void
     */
    public function logNoMatch(string $userMessage, string $lineUserId): void
    {
        try {
            DB::table('keyword_activity_logs')->insert([
                'keyword_id' => null,
                'keyword_name' => null,
                'user_message' => $userMessage,
                'line_user_id' => $lineUserId,
                'response_type' => 'ai_bot',
                'category' => null,
                'priority' => null,
                'action_type' => 'no_match',
                'timestamp' => now(),
                'created_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to log no match', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ดึง keyword statistics
     *
     * @param LineBotKeyword|null $keyword
     * @param int $days จำนวนวันย้อนหลัง
     * @return array
     */
    public function getKeywordStats(LineBotKeyword $keyword = null, int $days = 30): array
    {
        $query = DB::table('keyword_activity_logs')
            ->where('created_at', '>=', now()->subDays($days));

        if ($keyword) {
            $query->where('keyword_id', $keyword->id);
        }

        $logs = $query->get();

        return [
            'total_matches' => $logs->count(),
            'unique_users' => $logs->pluck('line_user_id')->unique()->count(),
            'by_category' => $logs->groupBy('category')->map->count(),
            'by_response_type' => $logs->groupBy('response_type')->map->count(),
            'matches_per_day' => $this->getMatchesPerDay($logs),
            'most_used_keyword' => $this->getMostUsedKeyword($logs),
        ];
    }

    /**
     * ดึง matches per day
     *
     * @param \Illuminate\Support\Collection $logs
     * @return array
     */
    private function getMatchesPerDay($logs): array
    {
        return $logs->groupBy(function ($log) {
            return $log->timestamp->format('Y-m-d');
        })->map->count()->toArray();
    }

    /**
     * ดึง most used keyword
     *
     * @param \Illuminate\Support\Collection $logs
     * @return array|null
     */
    private function getMostUsedKeyword($logs): ?array
    {
        $mostUsed = $logs
            ->where('action_type', 'matched')
            ->groupBy('keyword_name')
            ->map->count()
            ->sortDesc()
            ->first();

        return $mostUsed ? [
            'name' => $logs->where('action_type', 'matched')->pluck('keyword_name')->unique()->first(),
            'count' => $mostUsed,
        ] : null;
    }

    /**
     * ล้าง old logs
     *
     * @param int $days ล้าง logs ที่เก่ากว่า N วัน
     * @return int
     */
    public function clearOldLogs(int $days = 90): int
    {
        return DB::table('keyword_activity_logs')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * Export activity logs to CSV
     *
     * @param int $days จำนวนวันย้อนหลัง
     * @return string CSV content
     */
    public function exportToCSV(int $days = 30): string
    {
        $logs = DB::table('keyword_activity_logs')
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();

        $csv = "Date,Time,Keyword,Category,User Message,Response Type,Line User ID\n";

        foreach ($logs as $log) {
            $timestamp = \Carbon\Carbon::parse($log->timestamp);
            $date = $timestamp->format('Y-m-d');
            $time = $timestamp->format('H:i:s');
            $keyword = $log->keyword_name ?? 'N/A';
            $category = $log->category ?? 'N/A';
            $message = str_replace('"', '""', $log->user_message);
            $responseType = $log->response_type;

            $csv .= "{$date},{$time},{$keyword},{$category},\"{$message}\",{$responseType},{$log->line_user_id}\n";
        }

        return $csv;
    }

    /**
     * Get user conversation history with keywords
     *
     * @param string $lineUserId
     * @param int $limit
     * @return array
     */
    public function getUserHistory(string $lineUserId, int $limit = 20): array
    {
        return DB::table('keyword_activity_logs')
            ->where('line_user_id', $lineUserId)
            ->orderBy('timestamp', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
