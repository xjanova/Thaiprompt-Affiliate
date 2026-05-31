<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneKnowledge;
use App\Services\FortuneKnowledgeService;
use Illuminate\Http\Request;

/**
 * 🧠 จัดการคลังองค์ความรู้แม่หมอ (RAG)
 *
 * แอดมินเห็น/เพิ่ม/แก้/ปิด องค์ความรู้ที่แม่หมอ (AI) ดึงไปทำนายตามหน้าไพ่:
 *   สุขภาพ / ฮวงจุ้ย / เจ้าที่ / องค์เทพ / ไสยศาสตร์
 * เปลี่ยนแล้ว AI ใช้ทันที (ล้าง cache ทุกครั้งที่บันทึก)
 */
class FortuneKnowledgeController extends Controller
{
    /**
     * แสดงรายการความรู้ทั้งหมด (filter หมวด + ค้นหา + สถิติ)
     */
    public function index(Request $request)
    {
        $query = FortuneKnowledge::query();

        // ค้นหา
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('content', 'LIKE', "%{$search}%")
                    ->orWhere('subject', 'LIKE', "%{$search}%")
                    ->orWhere('card_name', 'LIKE', "%{$search}%")
                    ->orWhere('keywords', 'LIKE', "%{$search}%");
            });
        }

        // กรองหมวด
        $category = (string) $request->input('category', '');
        if ($category !== '') {
            $query->where('category', $category);
        }

        // กรอง active
        $active = $request->input('active');
        if ($active === '1') {
            $query->where('is_active', true);
        } elseif ($active === '0') {
            $query->where('is_active', false);
        }

        $rows = $query->orderBy('category')
            ->orderBy('priority')
            ->orderByRaw('card_name IS NULL')
            ->orderBy('card_name')
            ->orderBy('id')
            ->paginate(30)
            ->withQueryString();

        $stats = [
            'total' => FortuneKnowledge::count(),
            'active' => FortuneKnowledge::where('is_active', true)->count(),
            'category_breakdown' => FortuneKnowledge::selectRaw('category, COUNT(*) as total')
                ->groupBy('category')
                ->pluck('total', 'category')
                ->toArray(),
        ];

        return view('admin.fortune.knowledge.index', [
            'rows' => $rows,
            'stats' => $stats,
            'search' => $search,
            'category' => $category,
            'active' => $active,
            'categories' => FortuneKnowledge::CATEGORIES,
            'categoryLabels' => FortuneKnowledge::CATEGORY_LABELS,
            'pageTitle' => 'คลังความรู้แม่หมอ (RAG)',
        ]);
    }

    /**
     * ฟอร์มเพิ่มความรู้ใหม่
     */
    public function create()
    {
        return view('admin.fortune.knowledge.create', [
            'categories' => FortuneKnowledge::CATEGORIES,
            'categoryLabels' => FortuneKnowledge::CATEGORY_LABELS,
            'pageTitle' => 'เพิ่มองค์ความรู้',
        ]);
    }

    /**
     * บันทึกความรู้ใหม่
     */
    public function store(Request $request)
    {
        $validated = $this->validateData($request);
        $validated['is_active'] = $request->boolean('is_active');

        FortuneKnowledge::create($validated);
        $this->clearCache();

        return redirect()
            ->route('admin.fortune.knowledge.index')
            ->with('success', 'เพิ่มองค์ความรู้สำเร็จ');
    }

    /**
     * ฟอร์มแก้ไข
     */
    public function edit(FortuneKnowledge $knowledge)
    {
        return view('admin.fortune.knowledge.edit', [
            'item' => $knowledge,
            'categories' => FortuneKnowledge::CATEGORIES,
            'categoryLabels' => FortuneKnowledge::CATEGORY_LABELS,
            'pageTitle' => 'แก้ไของค์ความรู้ #'.$knowledge->id,
        ]);
    }

    /**
     * อัปเดตความรู้
     */
    public function update(Request $request, FortuneKnowledge $knowledge)
    {
        $validated = $this->validateData($request);
        $validated['is_active'] = $request->boolean('is_active');

        $knowledge->update($validated);
        $this->clearCache();

        return redirect()
            ->route('admin.fortune.knowledge.index')
            ->with('success', 'อัปเดตองค์ความรู้สำเร็จ');
    }

    /**
     * ลบความรู้
     */
    public function destroy(FortuneKnowledge $knowledge)
    {
        $knowledge->delete();
        $this->clearCache();

        return redirect()
            ->route('admin.fortune.knowledge.index')
            ->with('success', 'ลบองค์ความรู้สำเร็จ');
    }

    /**
     * เปิด/ปิดความรู้ (toggle)
     */
    public function toggle(FortuneKnowledge $knowledge)
    {
        $knowledge->update(['is_active' => ! $knowledge->is_active]);
        $this->clearCache();

        return back()->with('success', ($knowledge->is_active ? 'เปิด' : 'ปิด').'ใช้งานความรู้แล้ว');
    }

    /**
     * ตรวจสอบข้อมูล (ใช้ร่วม store/update)
     */
    protected function validateData(Request $request): array
    {
        return $request->validate([
            'category' => 'required|string|max:32',
            'card_name' => 'nullable|string|max:64',
            'subject' => 'nullable|string|max:120',
            'title' => 'required|string|max:200',
            'keywords' => 'nullable|string|max:2000',
            'content' => 'required|string|max:20000',
            'severity' => 'nullable|in:low,medium,high',
            'priority' => 'nullable|integer|min:0|max:9999',
            'source' => 'nullable|string|max:255',
        ]);
    }

    /**
     * ล้าง cache ของ FortuneKnowledgeService → AI เห็นข้อมูลใหม่ทันที
     */
    protected function clearCache(): void
    {
        app(FortuneKnowledgeService::class)->clearCache();
    }
}
