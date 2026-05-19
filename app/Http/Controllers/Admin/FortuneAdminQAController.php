<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneAdminQA;
use App\Models\FortuneTellingSetting;
use App\Services\GeminiEmbeddingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * จัดการระบบ RAG Admin Q&A
 *
 * แอดมินดูคู่ "คำถามลูกค้า ↔ คำตอบแอดมิน" ที่ระบบจับมาจาก FB Page Inbox
 * + จัดการ:
 *   - แก้ Q/A
 *   - เปิด/ปิดเป็นรายตัว (is_active)
 *   - ลบ
 *   - ปรับ threshold per-row
 *   - re-embed (ถ้า embedding ว่างจาก capture fail)
 */
class FortuneAdminQAController extends Controller
{
    /**
     * แสดงรายการ Q&A
     */
    public function index(Request $request)
    {
        $query = FortuneAdminQA::query();

        // 🔍 ค้นหาด้วย q_text หรือ a_text
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('q_text', 'LIKE', "%{$search}%")
                    ->orWhere('a_text', 'LIKE', "%{$search}%");
            });
        }

        // กรองตาม platform
        $platform = $request->input('platform');
        if (in_array($platform, ['facebook', 'line', 'manual'], true)) {
            $query->where('source_platform', $platform);
        }

        // กรอง active
        $activeFilter = $request->input('active');
        if ($activeFilter === '1') {
            $query->where('is_active', true);
        } elseif ($activeFilter === '0') {
            $query->where('is_active', false);
        }

        $rows = $query->latest()->paginate(20)->withQueryString();

        $settings = FortuneTellingSetting::getSettings();

        $stats = [
            'total' => FortuneAdminQA::count(),
            'active' => FortuneAdminQA::where('is_active', true)->count(),
            'with_embedding' => FortuneAdminQA::whereNotNull('q_embedding')->count(),
            'total_hits' => (int) FortuneAdminQA::sum('hit_count'),
        ];

        return view('admin.fortune.admin-qa.index', [
            'rows' => $rows,
            'settings' => $settings,
            'stats' => $stats,
            'search' => $search,
            'platform' => $platform,
            'activeFilter' => $activeFilter,
            'pageTitle' => 'RAG Admin Q&A',
        ]);
    }

    /**
     * แสดงฟอร์มแก้ไข Q&A
     */
    public function edit(FortuneAdminQA $qa)
    {
        return view('admin.fortune.admin-qa.edit', [
            'qa' => $qa,
            'pageTitle' => 'แก้ไข Admin Q&A #'.$qa->id,
        ]);
    }

    /**
     * บันทึกการแก้ไข Q&A
     *
     * ถ้า q_text เปลี่ยน → re-embed อัตโนมัติ
     */
    public function update(Request $request, FortuneAdminQA $qa)
    {
        $validated = $request->validate([
            'q_text' => 'required|string|min:1|max:10000',
            'a_text' => 'required|string|min:1|max:20000',
            'is_active' => 'nullable|boolean',
            'similarity_threshold' => 'nullable|numeric|min:0|max:1',
        ]);

        $shouldReembed = trim($qa->q_text) !== trim($validated['q_text']);

        $qa->q_text = $validated['q_text'];
        $qa->a_text = $validated['a_text'];
        $qa->is_active = (bool) ($validated['is_active'] ?? false);
        $qa->similarity_threshold = $validated['similarity_threshold'] ?? null;

        // Re-embed ถ้า q_text เปลี่ยน
        if ($shouldReembed) {
            try {
                $embeddingService = new GeminiEmbeddingService;
                $embedding = $embeddingService->embed($validated['q_text']);
                $qa->q_embedding = $embedding;

                Log::info('FortuneAdminQA: re-embedded หลัง edit', [
                    'qa_id' => $qa->id,
                    'has_embedding' => $embedding !== null,
                ]);
            } catch (\Throwable $e) {
                Log::warning('FortuneAdminQA: re-embed ล้มเหลว', [
                    'qa_id' => $qa->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $qa->save();

        return redirect()
            ->route('admin.fortune.admin-qa.index')
            ->with('success', 'บันทึกการแก้ไขสำเร็จ');
    }

    /**
     * Toggle active flag
     */
    public function toggleActive(FortuneAdminQA $qa)
    {
        $qa->is_active = ! $qa->is_active;
        $qa->save();

        return back()->with('success', $qa->is_active ? 'เปิดใช้งานแล้ว' : 'ปิดใช้งานแล้ว');
    }

    /**
     * Re-embed (สำหรับ row ที่ embedding ว่าง — capture fail)
     */
    public function reembed(FortuneAdminQA $qa)
    {
        try {
            $embeddingService = new GeminiEmbeddingService;
            $embedding = $embeddingService->embed($qa->q_text);

            if ($embedding === null) {
                return back()->with('error', 'Embed ล้มเหลว — ตรวจสอบ Gemini Pool key');
            }

            $qa->q_embedding = $embedding;
            $qa->save();

            return back()->with('success', 'Re-embed สำเร็จ');
        } catch (\Throwable $e) {
            Log::warning('FortuneAdminQA: re-embed action ล้มเหลว', [
                'qa_id' => $qa->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Re-embed ล้มเหลว: '.$e->getMessage());
        }
    }

    /**
     * ลบ Q&A
     */
    public function destroy(FortuneAdminQA $qa)
    {
        $qa->delete();

        return redirect()
            ->route('admin.fortune.admin-qa.index')
            ->with('success', 'ลบสำเร็จ');
    }

    /**
     * อัพเดท RAG settings (จาก index page)
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'admin_qa_rag_enabled' => 'nullable|boolean',
            'admin_qa_capture_enabled' => 'nullable|boolean',
            'admin_qa_rag_threshold' => 'nullable|numeric|min:0|max:1',
            'admin_qa_rag_top_k' => 'nullable|integer|min:1|max:10',
        ]);

        $settings = FortuneTellingSetting::getSettings();
        $settings->admin_qa_rag_enabled = (bool) ($validated['admin_qa_rag_enabled'] ?? false);
        $settings->admin_qa_capture_enabled = (bool) ($validated['admin_qa_capture_enabled'] ?? false);
        $settings->admin_qa_rag_threshold = $validated['admin_qa_rag_threshold'] ?? FortuneAdminQA::DEFAULT_THRESHOLD;
        $settings->admin_qa_rag_top_k = $validated['admin_qa_rag_top_k'] ?? 3;
        $settings->save();

        return back()->with('success', 'บันทึกการตั้งค่าสำเร็จ');
    }
}
