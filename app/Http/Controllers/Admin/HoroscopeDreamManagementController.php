<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HoroscopeDreamCategory;
use App\Models\HoroscopeDreamDictionary;
use App\Models\HoroscopeDreamReading;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * HoroscopeDreamManagementController — จัดการพจนานุกรมทำนายฝัน
 *
 * จัดการ:
 * - CRUD หมวดหมู่ฝัน (HoroscopeDreamCategory)
 * - CRUD สัญลักษณ์ฝัน / พจนานุกรม (HoroscopeDreamDictionary)
 * - ดูผลทำนายฝัน (HoroscopeDreamReading)
 */
class HoroscopeDreamManagementController extends Controller
{
    // ======================================================
    // หมวดหมู่ฝัน (Categories)
    // ======================================================

    /**
     * แสดงรายการหมวดหมู่ฝัน
     *
     * @return \Illuminate\View\View
     */
    public function categories()
    {
        $categories = HoroscopeDreamCategory::orderBy('sort_order')
            ->withCount(['dreamSymbols', 'dreamSymbols as active_symbols_count' => fn ($q) => $q->where('is_active', true)])
            ->paginate(20);

        return view('admin.fortune.horoscope-public.dream.categories', [
            'pageTitle' => '📂 หมวดหมู่ทำนายฝัน',
            'categories' => $categories,
        ]);
    }

    /**
     * แสดงฟอร์มสร้างหมวดหมู่ใหม่
     *
     * @return \Illuminate\View\View
     */
    public function createCategory()
    {
        return view('admin.fortune.horoscope-public.dream.category-form', [
            'pageTitle' => '➕ เพิ่มหมวดหมู่ฝัน',
            'category' => null,
        ]);
    }

    /**
     * บันทึกหมวดหมู่ใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name_th' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:horoscope_dream_categories,slug',
            'icon' => 'nullable|string|max:50',
            'color_hex' => 'required|string|max:10',
            'description_th' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'required|integer|min:0',
        ]);

        // สร้าง slug อัตโนมัติถ้าไม่ระบุ
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name_th']);
        }

        $validated['is_active'] = $request->boolean('is_active');

        HoroscopeDreamCategory::create($validated);

        return redirect()
            ->route('admin.fortune.horoscope-public.dream.categories')
            ->with('success', '✅ เพิ่มหมวดหมู่สำเร็จ');
    }

    /**
     * แสดงฟอร์มแก้ไขหมวดหมู่
     *
     * @param HoroscopeDreamCategory $category
     * @return \Illuminate\View\View
     */
    public function editCategory(HoroscopeDreamCategory $category)
    {
        return view('admin.fortune.horoscope-public.dream.category-form', [
            'pageTitle' => "✏️ แก้ไขหมวด {$category->name_th}",
            'category' => $category,
        ]);
    }

    /**
     * อัพเดทหมวดหมู่
     *
     * @param Request $request
     * @param HoroscopeDreamCategory $category
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCategory(Request $request, HoroscopeDreamCategory $category)
    {
        $validated = $request->validate([
            'name_th' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:horoscope_dream_categories,slug,' . $category->id,
            'icon' => 'nullable|string|max:50',
            'color_hex' => 'required|string|max:10',
            'description_th' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'required|integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $category->update($validated);

        return redirect()
            ->route('admin.fortune.horoscope-public.dream.categories')
            ->with('success', "✅ อัพเดทหมวด {$category->name_th} สำเร็จ");
    }

    /**
     * ลบหมวดหมู่
     *
     * @param HoroscopeDreamCategory $category
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyCategory(HoroscopeDreamCategory $category)
    {
        // เช็คว่ามีสัญลักษณ์อยู่หรือไม่
        if ($category->dreamSymbols()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', '❌ ไม่สามารถลบหมวดหมู่ที่มีสัญลักษณ์อยู่ได้ กรุณาลบสัญลักษณ์ก่อน');
        }

        $category->delete();

        return redirect()
            ->route('admin.fortune.horoscope-public.dream.categories')
            ->with('success', '✅ ลบหมวดหมู่สำเร็จ');
    }

    // ======================================================
    // พจนานุกรมฝัน (Dictionary / Symbols)
    // ======================================================

    /**
     * แสดงรายการสัญลักษณ์ฝัน
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = HoroscopeDreamDictionary::with('category');

        // ค้นหาตามคำสำคัญ
        if ($request->filled('search')) {
            $query->where('keyword_th', 'like', '%' . $request->input('search') . '%');
        }

        // กรองตามหมวดหมู่
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // กรองตามลางบอกเหตุ
        if ($request->filled('omen')) {
            $query->where('is_good_omen', $request->input('omen') === 'good');
        }

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $symbols = $query->orderBy('keyword_th')->paginate(30);

        $categories = HoroscopeDreamCategory::orderBy('sort_order')->get();

        return view('admin.fortune.horoscope-public.dream.index', [
            'pageTitle' => '📖 พจนานุกรมทำนายฝัน',
            'symbols' => $symbols,
            'categories' => $categories,
        ]);
    }

    /**
     * แสดงฟอร์มสร้างสัญลักษณ์ใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $categories = HoroscopeDreamCategory::active()->ordered()->get();

        return view('admin.fortune.horoscope-public.dream.dictionary-form', [
            'pageTitle' => '➕ เพิ่มสัญลักษณ์ฝัน',
            'symbol' => null,
            'categories' => $categories,
        ]);
    }

    /**
     * บันทึกสัญลักษณ์ใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:horoscope_dream_categories,id',
            'keyword_th' => 'required|string|max:100|unique:horoscope_dream_dictionary,keyword_th',
            'meaning_th' => 'required|string',
            'lucky_numbers' => 'nullable|string',
            'is_good_omen' => 'boolean',
            'intensity' => 'required|integer|min:1|max:5',
            'is_active' => 'boolean',
        ]);

        // แปลง lucky_numbers จาก string เป็น array
        $validated['lucky_numbers'] = $this->parseLuckyNumbers($request->input('lucky_numbers'));
        $validated['is_good_omen'] = $request->boolean('is_good_omen');
        $validated['is_active'] = $request->boolean('is_active');

        HoroscopeDreamDictionary::create($validated);

        return redirect()
            ->route('admin.fortune.horoscope-public.dream.index')
            ->with('success', '✅ เพิ่มสัญลักษณ์สำเร็จ');
    }

    /**
     * แสดงฟอร์มแก้ไขสัญลักษณ์
     *
     * @param HoroscopeDreamDictionary $symbol
     * @return \Illuminate\View\View
     */
    public function edit(HoroscopeDreamDictionary $symbol)
    {
        $categories = HoroscopeDreamCategory::active()->ordered()->get();

        return view('admin.fortune.horoscope-public.dream.dictionary-form', [
            'pageTitle' => "✏️ แก้ไข — ฝันเห็น{$symbol->keyword_th}",
            'symbol' => $symbol,
            'categories' => $categories,
        ]);
    }

    /**
     * อัพเดทสัญลักษณ์
     *
     * @param Request $request
     * @param HoroscopeDreamDictionary $symbol
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, HoroscopeDreamDictionary $symbol)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:horoscope_dream_categories,id',
            'keyword_th' => 'required|string|max:100|unique:horoscope_dream_dictionary,keyword_th,' . $symbol->id,
            'meaning_th' => 'required|string',
            'lucky_numbers' => 'nullable|string',
            'is_good_omen' => 'boolean',
            'intensity' => 'required|integer|min:1|max:5',
            'is_active' => 'boolean',
        ]);

        $validated['lucky_numbers'] = $this->parseLuckyNumbers($request->input('lucky_numbers'));
        $validated['is_good_omen'] = $request->boolean('is_good_omen');
        $validated['is_active'] = $request->boolean('is_active');

        $symbol->update($validated);

        return redirect()
            ->route('admin.fortune.horoscope-public.dream.index')
            ->with('success', "✅ อัพเดทสัญลักษณ์ «{$symbol->keyword_th}» สำเร็จ");
    }

    /**
     * ลบสัญลักษณ์
     *
     * @param HoroscopeDreamDictionary $symbol
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(HoroscopeDreamDictionary $symbol)
    {
        $symbol->delete();

        return redirect()
            ->route('admin.fortune.horoscope-public.dream.index')
            ->with('success', '✅ ลบสัญลักษณ์สำเร็จ');
    }

    // ======================================================
    // ผลทำนายฝัน (Dream Readings)
    // ======================================================

    /**
     * แสดงรายการผลทำนายฝัน
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function readings(Request $request)
    {
        $query = HoroscopeDreamReading::with('user');

        // ค้นหาตามคำอธิบายฝัน
        if ($request->filled('search')) {
            $query->where('dream_description', 'like', '%' . $request->input('search') . '%');
        }

        $readings = $query->orderByDesc('created_at')->paginate(20);

        // สถิติภาพรวม
        $stats = [
            'total' => HoroscopeDreamReading::count(),
            'today' => HoroscopeDreamReading::whereDate('created_at', today())->count(),
            'free' => HoroscopeDreamReading::where('is_free', true)->count(),
            'views' => HoroscopeDreamReading::sum('view_count'),
        ];

        return view('admin.fortune.horoscope-public.dream.readings', [
            'pageTitle' => '📋 ผลทำนายฝัน',
            'readings' => $readings,
            'stats' => $stats,
        ]);
    }

    /**
     * แสดงรายละเอียดผลทำนายฝัน
     *
     * @param HoroscopeDreamReading $reading
     * @return \Illuminate\View\View
     */
    public function showReading(HoroscopeDreamReading $reading)
    {
        $reading->load('user');

        // ดึงสัญลักษณ์ที่ตรงกัน
        $matchedSymbols = collect();
        if (! empty($reading->matched_keywords)) {
            $matchedSymbols = HoroscopeDreamDictionary::with('category')
                ->whereIn('keyword_th', $reading->matched_keywords)
                ->get();
        }

        return view('admin.fortune.horoscope-public.dream.reading-show', [
            'pageTitle' => '🔍 รายละเอียดผลทำนายฝัน #' . $reading->id,
            'reading' => $reading,
            'matchedSymbols' => $matchedSymbols,
        ]);
    }

    /**
     * ลบผลทำนายฝัน
     *
     * @param HoroscopeDreamReading $reading
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyReading(HoroscopeDreamReading $reading)
    {
        $reading->delete();

        return redirect()
            ->route('admin.fortune.horoscope-public.dream.readings')
            ->with('success', '✅ ลบผลทำนายสำเร็จ');
    }

    // ======================================================
    // Private Helpers
    // ======================================================

    /**
     * แปลง string เลขเด็ดเป็น array
     * รูปแบบ: "12, 34, 56" → [12, 34, 56]
     *
     * @param string|null $input
     * @return array|null
     */
    private function parseLuckyNumbers(?string $input): ?array
    {
        if (empty($input)) {
            return null;
        }

        // แยกตาม comma, space หรือทั้งสองอย่าง
        $numbers = preg_split('/[\s,]+/', trim($input));

        return array_values(array_filter(array_map(function ($num) {
            $num = trim($num);

            return is_numeric($num) ? (int) $num : null;
        }, $numbers)));
    }
}
