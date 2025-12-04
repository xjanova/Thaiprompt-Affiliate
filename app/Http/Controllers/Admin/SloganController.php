<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slogan;
use Illuminate\Http\Request;

/**
 * Admin Slogan Controller
 *
 * จัดการคำขวัญ/คำคมสำหรับแสดงใน Dashboard
 */
class SloganController extends Controller
{
    /**
     * แสดงรายการคำขวัญทั้งหมด
     */
    public function index(Request $request)
    {
        $query = Slogan::query();

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('content', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%");
        }

        // กรองตามหมวดหมู่
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $slogans = $query->orderByDesc('priority')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $stats = Slogan::getStats();
        $categories = Slogan::getCategories();

        return view('admin.slogans.index', compact('slogans', 'stats', 'categories'));
    }

    /**
     * แสดงฟอร์มสร้างคำขวัญใหม่
     */
    public function create()
    {
        $categories = Slogan::getCategories();
        $icons = Slogan::getIcons();

        return view('admin.slogans.create', compact('categories', 'icons'));
    }

    /**
     * บันทึกคำขวัญใหม่
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:500',
            'author' => 'nullable|string|max:100',
            'category' => 'required|string',
            'icon' => 'required|string|max:10',
            'priority' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Slogan::create($validated);

        return redirect()->route('admin.slogans.index')
            ->with('success', 'เพิ่มคำขวัญสำเร็จ!');
    }

    /**
     * แสดงฟอร์มแก้ไขคำขวัญ
     */
    public function edit(Slogan $slogan)
    {
        $categories = Slogan::getCategories();
        $icons = Slogan::getIcons();

        return view('admin.slogans.edit', compact('slogan', 'categories', 'icons'));
    }

    /**
     * อัพเดทคำขวัญ
     */
    public function update(Request $request, Slogan $slogan)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:500',
            'author' => 'nullable|string|max:100',
            'category' => 'required|string',
            'icon' => 'required|string|max:10',
            'priority' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $slogan->update($validated);

        return redirect()->route('admin.slogans.index')
            ->with('success', 'แก้ไขคำขวัญสำเร็จ!');
    }

    /**
     * ลบคำขวัญ
     */
    public function destroy(Slogan $slogan)
    {
        $slogan->delete();

        return redirect()->route('admin.slogans.index')
            ->with('success', 'ลบคำขวัญสำเร็จ!');
    }

    /**
     * Toggle สถานะ active
     */
    public function toggleActive(Slogan $slogan)
    {
        $slogan->toggleActive();

        return response()->json([
            'success' => true,
            'is_active' => $slogan->is_active,
            'message' => $slogan->is_active ? 'เปิดใช้งานแล้ว' : 'ปิดใช้งานแล้ว',
        ]);
    }

    /**
     * API: ดึงคำขวัญแบบสุ่ม
     */
    public function random(Request $request)
    {
        $count = $request->get('count', 1);
        $category = $request->get('category');

        $slogans = Slogan::getRandom(null, $category, $count);

        return response()->json([
            'success' => true,
            'data' => $slogans,
        ]);
    }
}
