<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineBotKeyword;
use Illuminate\Support\Facades\DB;

/**
 * LINE Bot Keyword Analytics Controller
 *
 * วิเคราะห์การใช้งาน Keywords และประสิทธิภาพของ Hybrid Bot
 */
class LineBotKeywordAnalyticsController extends Controller
{
    /**
     * แสดงแดชบอร์ด Analytics
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $keywords = LineBotKeyword::all();

        // คำนวณสถิติ
        $stats = [
            'total_keywords' => $keywords->count(),
            'active_keywords' => $keywords->where('is_active', true)->count(),
            'by_category' => $keywords->groupBy('category')->map->count(),
            'avg_priority' => $keywords->avg('priority'),
            'by_response_type' => $keywords->groupBy('response_type')->map->count(),
        ];

        // สร้างข้อมูลสำหรับกราฟ
        $categoryData = [
            'labels' => array_keys($stats['by_category']->toArray()),
            'data' => array_values($stats['by_category']->toArray()),
        ];

        $responseTypeData = [
            'labels' => array_keys($stats['by_response_type']->toArray()),
            'data' => array_values($stats['by_response_type']->toArray()),
        ];

        // Priority distribution
        $priorityDistribution = $keywords
            ->groupBy(function ($keyword) {
                if ($keyword->priority >= 80) return 'High (80-100)';
                if ($keyword->priority >= 50) return 'Medium (50-79)';
                return 'Low (1-49)';
            })
            ->map->count();

        $priorityData = [
            'labels' => $priorityDistribution->keys()->toArray(),
            'data' => $priorityDistribution->values()->toArray(),
        ];

        return view('admin.line-bot.keywords.analytics', [
            'stats' => $stats,
            'keywords' => $keywords,
            'categoryData' => $categoryData,
            'responseTypeData' => $responseTypeData,
            'priorityData' => $priorityData,
            'pageTitle' => 'Keyword Analytics & Performance',
        ]);
    }

    /**
     * Export keywords to JSON
     *
     * @return \Illuminate\Http\Response
     */
    public function export()
    {
        $keywords = LineBotKeyword::all();

        $data = $keywords->map(function ($keyword) {
            return [
                'keyword' => $keyword->keyword,
                'description' => $keyword->description,
                'trigger_words' => $keyword->trigger_words,
                'response_type' => $keyword->response_type,
                'response_text' => $keyword->response_text,
                'response_flex_json' => $keyword->response_flex_json,
                'quick_reply_options' => $keyword->quick_reply_options,
                'category' => $keyword->category,
                'priority' => $keyword->priority,
                'is_active' => $keyword->is_active,
                'notes' => $keyword->notes,
            ];
        })->toArray();

        $filename = 'keywords_export_' . now()->format('Y-m-d_H-i-s') . '.json';

        return response()->json($data)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Import keywords from JSON
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function import(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json|max:5120', // 5MB max
        ]);

        try {
            $file = $request->file('file');
            $content = file_get_contents($file->getRealPath());
            $data = json_decode($content, true);

            if (!is_array($data)) {
                throw new \Exception('ไฟล์ JSON ต้องเป็น array');
            }

            $imported = 0;
            $skipped = 0;

            foreach ($data as $item) {
                // ตรวจสอบว่า keyword มีอยู่แล้วหรือไม่
                $existing = LineBotKeyword::where('keyword', $item['keyword'] ?? null)->first();

                if ($existing && $request->input('skip_existing')) {
                    $skipped++;
                    continue;
                }

                $keywordData = [
                    'keyword' => $item['keyword'] ?? null,
                    'description' => $item['description'] ?? null,
                    'trigger_words' => $item['trigger_words'] ?? [],
                    'response_type' => $item['response_type'] ?? 'text',
                    'response_text' => $item['response_text'] ?? null,
                    'response_flex_json' => $item['response_flex_json'] ?? null,
                    'quick_reply_options' => $item['quick_reply_options'] ?? null,
                    'category' => $item['category'] ?? 'custom',
                    'priority' => $item['priority'] ?? 50,
                    'is_active' => $item['is_active'] ?? true,
                    'notes' => $item['notes'] ?? null,
                ];

                if ($existing && !$request->input('skip_existing')) {
                    $existing->update($keywordData);
                } else {
                    LineBotKeyword::create($keywordData);
                }

                $imported++;
            }

            \Illuminate\Support\Facades\Log::info('Import Keywords', [
                'imported' => $imported,
                'skipped' => $skipped,
                'imported_by' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.line-bot.keywords.analytics')
                ->with('success', "นำเข้า $imported Keywords สำเร็จ" . ($skipped > 0 ? " (ข้าม $skipped keywords)" : ''));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Import Keywords Error', [
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.line-bot.keywords.analytics')
                ->with('error', 'เกิดข้อผิดพลาดในการนำเข้า: ' . $e->getMessage());
        }
    }

    /**
     * Clone keyword
     *
     * @param LineBotKeyword $keyword
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clone(LineBotKeyword $keyword)
    {
        try {
            // สร้างสำเนาใหม่
            $newKeyword = $keyword->replicate();
            $newKeyword->keyword = $keyword->keyword . '_copy_' . time();
            $newKeyword->is_active = false; // ปิดใช้งานสำเนาที่สร้างใหม่
            $newKeyword->save();

            return redirect()
                ->route('admin.line-bot.keywords.edit', $newKeyword)
                ->with('success', "Clone Keyword '{$keyword->keyword}' สำเร็จ");

        } catch (\Exception $e) {
            return redirect()
                ->route('admin.line-bot.keywords.index')
                ->with('error', 'เกิดข้อผิดพลาดในการ Clone: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update status
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateStatus(\Illuminate\Http\Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            $status = $request->input('status', false);

            if (empty($ids)) {
                return response()->json(['success' => false, 'error' => 'ไม่มี keywords ที่เลือก'], 400);
            }

            $count = LineBotKeyword::whereIn('id', $ids)->update(['is_active' => $status]);

            \Illuminate\Support\Facades\Log::info('Bulk Update Keyword Status', [
                'count' => $count,
                'status' => $status,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "อัปเดต $count keywords สำเร็จ",
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Bulk delete
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(\Illuminate\Http\Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json(['success' => false, 'error' => 'ไม่มี keywords ที่เลือก'], 400);
            }

            $count = LineBotKeyword::whereIn('id', $ids)->delete();

            \Illuminate\Support\Facades\Log::info('Bulk Delete Keywords', [
                'count' => $count,
                'deleted_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "ลบ $count keywords สำเร็จ",
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
