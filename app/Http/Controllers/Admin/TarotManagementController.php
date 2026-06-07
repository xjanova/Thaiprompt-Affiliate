<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TarotCard;
use App\Models\TarotCardBackImage;
use App\Models\TarotCardInterpretation;
use App\Models\TarotReading;
use App\Models\TarotReadingCategory;
use App\Models\TarotSetting;
use App\Models\TarotSpreadType;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TarotManagementController extends Controller
{
    protected ImageUploadService $imageService;

    public function __construct(ImageUploadService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Show tarot dashboard
     */
    public function index()
    {
        $stats = [
            'total_readings' => TarotReading::count(),
            'today_readings' => TarotReading::today()->count(),
            'free_readings' => TarotReading::free()->count(),
            'paid_readings' => TarotReading::paid()->count(),
            'total_revenue' => TarotReading::paid()->sum('amount_paid'),
            'total_cards' => TarotCard::count(),
            'active_cards' => TarotCard::active()->count(),
            'categories_count' => TarotReadingCategory::count(),
        ];

        $recent_readings = TarotReading::with(['user', 'category', 'spreadType'])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.tarot.index', compact('stats', 'recent_readings'));
    }

    /**
     * Cards Management
     */
    public function cardsIndex(Request $request)
    {
        // 🐛 (2026-06-08) แก้บัค: เดิม controller ไม่อ่าน query params เลย
        //   → ตัวกรอง (ประเภท/ชุด/ค้นหา) บนหน้ากดแล้ว "ไม่มีผล" แสดงไพ่ทั้งหมดเสมอ
        //   ฟอร์ม Alpine ส่ง ?type=&suit=&search= มาแต่ถูกโยนทิ้ง

        // 🧭 (2026-06-08) จำ filter ล่าสุดไว้ใน session → หลังบันทึก/อัพโหลดรูป/ลบไพ่
        //   จะ redirect กลับมาที่หน้านี้พร้อม filter เดิม (ไม่เด้งไปรายการทั้งหมด)
        session(['tarot_cards_last_filter' => $request->only(['type', 'suit', 'search', 'page'])]);

        $cards = TarotCard::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('suit'), fn ($q) => $q->where('suit', $request->input('suit')))
            ->when($request->filled('search'), function ($q) use ($request) {
                // ค้นหาจากชื่อไทยและอังกฤษ
                $term = trim((string) $request->input('search'));
                $q->where(function ($sub) use ($term) {
                    $sub->where('name_th', 'like', "%{$term}%")
                        ->orWhere('name_en', 'like', "%{$term}%");
                });
            })
            ->orderBy('type')->orderBy('suit')->orderBy('number')
            ->paginate(30)
            ->withQueryString(); // คงค่าตัวกรองไว้เวลาเปลี่ยนหน้า pagination

        return view('admin.tarot.cards.index', compact('cards'));
    }

    public function cardsCreate()
    {
        return view('admin.tarot.cards.create');
    }

    public function cardsStore(Request $request)
    {
        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_th' => 'required|string|max:255',
            'type' => 'required|in:major_arcana,minor_arcana',
            'suit' => 'nullable|in:wands,cups,swords,pentacles',
            'number' => 'nullable|integer',
            'description_en' => 'nullable|string',
            'description_th' => 'nullable|string',
            'upright_meaning_en' => 'nullable|string',
            'upright_meaning_th' => 'nullable|string',
            'reversed_meaning_en' => 'nullable|string',
            'reversed_meaning_th' => 'nullable|string',
            'keywords_en' => 'nullable|string',
            'keywords_th' => 'nullable|string',
            'image' => 'nullable|image|max:20480', // 20 MB (2026-05-04)
            'is_active' => 'boolean',
        ]);

        $data = $request->except(['image', 'keywords_en', 'keywords_th']);

        // Handle keywords
        if ($request->keywords_en) {
            $data['keywords_en'] = array_map('trim', explode(',', $request->keywords_en));
        }
        if ($request->keywords_th) {
            $data['keywords_th'] = array_map('trim', explode(',', $request->keywords_th));
        }

        // Handle image upload - แปลงเป็น WebP และ resize ให้พอดีกับขนาดการ์ด
        if ($request->hasFile('image')) {
            $path = $this->imageService->uploadTarotCard($request->file('image'));
            $data['image_url'] = '/storage/'.$path;
        }

        TarotCard::create($data);

        // 🧭 กลับไปที่ filter ล่าสุด (session) ไม่เด้งไปรายการทั้งหมด
        return redirect()->route('admin.tarot.cards.index', session('tarot_cards_last_filter', []))
            ->with('success', 'สร้างไพ่สำเร็จ');
    }

    public function cardsEdit($id)
    {
        $card = TarotCard::findOrFail($id);

        return view('admin.tarot.cards.edit', compact('card'));
    }

    public function cardsUpdate(Request $request, $id)
    {
        $card = TarotCard::findOrFail($id);

        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_th' => 'required|string|max:255',
            'type' => 'required|in:major_arcana,minor_arcana',
            'suit' => 'nullable|in:wands,cups,swords,pentacles',
            'number' => 'nullable|integer',
            'description_en' => 'nullable|string',
            'description_th' => 'nullable|string',
            'upright_meaning_en' => 'nullable|string',
            'upright_meaning_th' => 'nullable|string',
            'reversed_meaning_en' => 'nullable|string',
            'reversed_meaning_th' => 'nullable|string',
            'keywords_en' => 'nullable|string',
            'keywords_th' => 'nullable|string',
            'image' => 'nullable|image|max:20480', // 20 MB (2026-05-04)
            'is_active' => 'boolean',
        ]);

        $data = $request->except(['image', 'keywords_en', 'keywords_th']);

        // Handle keywords
        if ($request->keywords_en) {
            $data['keywords_en'] = array_map('trim', explode(',', $request->keywords_en));
        }
        if ($request->keywords_th) {
            $data['keywords_th'] = array_map('trim', explode(',', $request->keywords_th));
        }

        // Handle image upload - แปลงเป็น WebP และ resize ให้พอดีกับขนาดการ์ด
        if ($request->hasFile('image')) {
            // ลบรูปเก่า (ใช้ raw value จาก DB ไม่ใช่ accessor)
            $oldImagePath = $card->getRawImageUrl();
            if ($oldImagePath) {
                $this->imageService->deleteTarotImage($oldImagePath);
            }

            // อัพโหลดรูปใหม่
            $path = $this->imageService->uploadTarotCard($request->file('image'));
            $data['image_url'] = '/storage/'.$path;

            // Log for debugging
            \Log::info('Tarot card image uploaded', [
                'card_id' => $card->id,
                'old_path' => $oldImagePath,
                'new_path' => $data['image_url'],
                'storage_path' => $path,
            ]);
        }

        $card->update($data);

        // Log final state
        \Log::info('Tarot card updated', [
            'card_id' => $card->id,
            'image_url' => $card->fresh()->getRawImageUrl(),
        ]);

        // 🧭 กลับไปที่ filter ล่าสุด (session) ไม่เด้งไปรายการทั้งหมด
        return redirect()->route('admin.tarot.cards.index', session('tarot_cards_last_filter', []))
            ->with('success', 'อัพเดทไพ่สำเร็จ');
    }

    public function cardsDestroy($id)
    {
        $card = TarotCard::findOrFail($id);

        // ลบรูปไพ่ (ใช้ raw value จาก DB)
        $imagePath = $card->getRawImageUrl();
        if ($imagePath) {
            $this->imageService->deleteTarotImage($imagePath);
        }

        $card->delete();

        // 🧭 กลับไปที่ filter ล่าสุด (session) ไม่เด้งไปรายการทั้งหมด
        return redirect()->route('admin.tarot.cards.index', session('tarot_cards_last_filter', []))
            ->with('success', 'ลบไพ่สำเร็จ');
    }

    /**
     * AJAX Upload Image สำหรับไพ่ทาโร่ต์
     * ใช้สำหรับอัพโหลดรูปแบบ AJAX พร้อม progress และ error handling
     */
    public function cardsUploadImage(Request $request, $id)
    {
        try {
            $card = TarotCard::findOrFail($id);

            $request->validate([
                'image' => 'required|image|max:20480', // 20 MB (2026-05-04)
            ]);

            if (! $request->hasFile('image')) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบไฟล์รูปภาพ',
                ], 400);
            }

            $file = $request->file('image');

            // ลบรูปเก่า
            $oldImagePath = $card->getRawImageUrl();
            if ($oldImagePath) {
                $this->imageService->deleteTarotImage($oldImagePath);
            }

            // ลองอัพโหลดแบบ WebP ก่อน
            try {
                $path = $this->imageService->uploadTarotCard($file);
                $imageUrl = '/storage/'.$path;
            } catch (\Exception $e) {
                // Fallback: อัพโหลดแบบปกติถ้า WebP ไม่ทำงาน
                \Log::warning('WebP conversion failed, using fallback', ['error' => $e->getMessage()]);

                $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('tarot/cards', $filename, 'public');
                $imageUrl = '/storage/'.$path;
            }

            // อัพเดท database
            $card->update(['image_url' => $imageUrl]);

            \Log::info('Tarot card image uploaded via AJAX', [
                'card_id' => $card->id,
                'image_url' => $imageUrl,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'อัพโหลดรูปสำเร็จ',
                'image_url' => asset($imageUrl),
                'raw_path' => $imageUrl,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'รูปภาพไม่ถูกต้อง: '.implode(', ', $e->validator->errors()->all()),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Tarot card image upload failed', [
                'card_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Categories Management
     */
    public function categoriesIndex()
    {
        $categories = TarotReadingCategory::ordered()->get();

        return view('admin.tarot.categories.index', compact('categories'));
    }

    public function categoriesCreate()
    {
        return view('admin.tarot.categories.create');
    }

    public function categoriesStore(Request $request)
    {
        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_th' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:tarot_reading_categories,slug',
            'description_en' => 'nullable|string',
            'description_th' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'price' => 'required|numeric|min:0',
            'is_free_first' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data = $request->all();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($request->name_en);
        }

        TarotReadingCategory::create($data);

        return redirect()->route('admin.tarot.categories.index')
            ->with('success', 'Category created successfully');
    }

    public function categoriesEdit($id)
    {
        $category = TarotReadingCategory::findOrFail($id);

        return view('admin.tarot.categories.edit', compact('category'));
    }

    public function categoriesUpdate(Request $request, $id)
    {
        $category = TarotReadingCategory::findOrFail($id);

        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_th' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:tarot_reading_categories,slug,'.$id,
            'description_en' => 'nullable|string',
            'description_th' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'price' => 'required|numeric|min:0',
            'is_free_first' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data = $request->all();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($request->name_en);
        }

        $category->update($data);

        return redirect()->route('admin.tarot.categories.index')
            ->with('success', 'Category updated successfully');
    }

    public function categoriesDestroy($id)
    {
        $category = TarotReadingCategory::findOrFail($id);
        $category->delete();

        return redirect()->route('admin.tarot.categories.index')
            ->with('success', 'Category deleted successfully');
    }

    /**
     * Card Back Images Management
     */
    public function cardBacksIndex()
    {
        $cardBacks = TarotCardBackImage::orderBy('sort_order')->get();

        return view('admin.tarot.card-backs.index', compact('cardBacks'));
    }

    public function cardBacksStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'required|image|max:20480', // 20 MB (2026-05-04)
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // อัพโหลดรูปหลังไพ่ - แปลงเป็น WebP และ resize ให้พอดี
        $path = $this->imageService->uploadTarotCardBack($request->file('image'));

        TarotCardBackImage::create([
            'name' => $request->name,
            'image_url' => '/storage/'.$path,
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => TarotCardBackImage::max('sort_order') + 1,
        ]);

        return redirect()->route('admin.tarot.card-backs.index')
            ->with('success', 'อัพโหลดรูปหลังไพ่สำเร็จ');
    }

    public function cardBacksSetDefault($id)
    {
        $cardBack = TarotCardBackImage::findOrFail($id);

        // Remove default from all others
        TarotCardBackImage::where('id', '!=', $id)->update(['is_default' => false]);

        $cardBack->is_default = true;
        $cardBack->save();

        return redirect()->route('admin.tarot.card-backs.index')
            ->with('success', 'Default card back set successfully');
    }

    public function cardBacksDestroy($id)
    {
        $cardBack = TarotCardBackImage::findOrFail($id);

        if ($cardBack->is_default) {
            return redirect()->route('admin.tarot.card-backs.index')
                ->with('error', 'ไม่สามารถลบรูปหลังไพ่ที่เป็นค่าเริ่มต้นได้');
        }

        // ลบรูปหลังไพ่ (ใช้ raw value จาก DB)
        $imagePath = $cardBack->getRawImageUrl();
        if ($imagePath) {
            $this->imageService->deleteTarotImage($imagePath);
        }

        $cardBack->delete();

        return redirect()->route('admin.tarot.card-backs.index')
            ->with('success', 'ลบรูปหลังไพ่สำเร็จ');
    }

    /**
     * Spread Types Management
     */
    public function spreadTypesIndex()
    {
        $spreadTypes = TarotSpreadType::ordered()->get();

        return view('admin.tarot.spread-types.index', compact('spreadTypes'));
    }

    /**
     * Settings Management
     */
    public function settings()
    {
        $settings = TarotSetting::all()->pluck('value', 'key');

        return view('admin.tarot.settings', compact('settings'));
    }

    public function settingsUpdate(Request $request)
    {
        $request->validate([
            'enable_tarot_system' => 'boolean',
            'allow_guest_readings' => 'boolean',
            'show_reversed_cards' => 'boolean',
            'enable_ai_interpretation' => 'boolean',
            'save_readings_days' => 'nullable|integer|min:1',
            'animation_speed' => 'nullable|in:slow,medium,fast',
        ]);

        foreach ($request->all() as $key => $value) {
            if ($key === '_token') {
                continue;
            }

            $type = 'string';
            if (in_array($key, ['enable_tarot_system', 'allow_guest_readings', 'show_reversed_cards', 'enable_ai_interpretation'])) {
                $type = 'boolean';
                $value = $request->boolean($key);
            } elseif (in_array($key, ['save_readings_days'])) {
                $type = 'integer';
            }

            TarotSetting::set($key, $value, $type);
        }

        return redirect()->route('admin.tarot.settings')
            ->with('success', 'Settings updated successfully');
    }

    /**
     * Readings Management
     */
    public function readingsIndex()
    {
        $readings = TarotReading::with(['user', 'category', 'spreadType'])
            ->latest()
            ->paginate(50);

        return view('admin.tarot.readings.index', compact('readings'));
    }

    public function readingsShow($id)
    {
        $reading = TarotReading::with(['user', 'category', 'spreadType', 'cards.card'])->findOrFail($id);

        return view('admin.tarot.readings.show', compact('reading'));
    }

    public function readingsDestroy($id)
    {
        $reading = TarotReading::findOrFail($id);
        $reading->delete();

        return redirect()->route('admin.tarot.readings.index')
            ->with('success', 'Reading deleted successfully');
    }

    /**
     * Analytics
     */
    public function analytics()
    {
        // Get reading statistics
        $stats = [
            'total_readings' => TarotReading::count(),
            'free_readings' => TarotReading::free()->count(),
            'paid_readings' => TarotReading::paid()->count(),
            'total_revenue' => TarotReading::paid()->sum('amount_paid'),
            'avg_reading_price' => TarotReading::paid()->avg('amount_paid'),
        ];

        // Readings by category
        $readingsByCategory = TarotReading::selectRaw('category_id, count(*) as count, sum(amount_paid) as revenue')
            ->groupBy('category_id')
            ->with('category')
            ->get();

        // Readings by spread type
        $readingsBySpread = TarotReading::selectRaw('spread_type_id, count(*) as count')
            ->groupBy('spread_type_id')
            ->with('spreadType')
            ->get();

        // Most popular cards
        $popularCards = \DB::table('tarot_reading_cards')
            ->select('card_id', \DB::raw('count(*) as count'))
            ->groupBy('card_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return view('admin.tarot.analytics', compact(
            'stats',
            'readingsByCategory',
            'readingsBySpread',
            'popularCards'
        ));
    }

    /**
     * จัดการคำทำนายตามหมวดหมู่
     *
     * แสดงรายการไพ่ทั้งหมดพร้อมสถานะคำทำนายในแต่ละหมวด
     */
    public function interpretationsIndex()
    {
        $cards = TarotCard::with('interpretations')
            ->orderBy('type')
            ->orderBy('suit')
            ->orderBy('number')
            ->paginate(30);

        $categories = TarotReadingCategory::active()->ordered()->get();

        return view('admin.tarot.interpretations.index', compact('cards', 'categories'));
    }

    /**
     * แก้ไขคำทำนายของไพ่แต่ละใบสำหรับทุกหมวด
     *
     * @param  int  $id  ID ของไพ่
     */
    public function interpretationsEdit($id)
    {
        $card = TarotCard::with('interpretations')->findOrFail($id);
        $categories = TarotReadingCategory::active()->ordered()->get();

        // เตรียม interpretations สำหรับทุกหมวด (สร้างถ้าไม่มี)
        $interpretations = [];
        foreach ($categories as $category) {
            $interpretation = $card->interpretations
                ->firstWhere('category_id', $category->id);

            if (! $interpretation) {
                $interpretation = new TarotCardInterpretation([
                    'card_id' => $card->id,
                    'category_id' => $category->id,
                ]);
            }

            $interpretations[$category->id] = $interpretation;
        }

        return view('admin.tarot.interpretations.edit', compact('card', 'categories', 'interpretations'));
    }

    /**
     * บันทึกคำทำนายของไพ่
     *
     * @param  int  $id  ID ของไพ่
     */
    public function interpretationsUpdate(Request $request, $id)
    {
        $card = TarotCard::findOrFail($id);

        $request->validate([
            'interpretations' => 'required|array',
            'interpretations.*.category_id' => 'required|exists:tarot_reading_categories,id',
            'interpretations.*.upright_interpretation_th' => 'nullable|string',
            'interpretations.*.upright_interpretation_en' => 'nullable|string',
            'interpretations.*.reversed_interpretation_th' => 'nullable|string',
            'interpretations.*.reversed_interpretation_en' => 'nullable|string',
            'interpretations.*.advice_th' => 'nullable|string',
            'interpretations.*.advice_en' => 'nullable|string',
        ]);

        foreach ($request->interpretations as $data) {
            TarotCardInterpretation::updateOrCreate(
                [
                    'card_id' => $card->id,
                    'category_id' => $data['category_id'],
                ],
                [
                    'upright_interpretation_th' => $data['upright_interpretation_th'] ?? null,
                    'upright_interpretation_en' => $data['upright_interpretation_en'] ?? null,
                    'reversed_interpretation_th' => $data['reversed_interpretation_th'] ?? null,
                    'reversed_interpretation_en' => $data['reversed_interpretation_en'] ?? null,
                    'advice_th' => $data['advice_th'] ?? null,
                    'advice_en' => $data['advice_en'] ?? null,
                ]
            );
        }

        return redirect()
            ->route('admin.tarot.interpretations.index')
            ->with('success', 'บันทึกคำทำนายสำเร็จ');
    }

    /**
     * คัดลอกคำทำนายเริ่มต้นจาก TarotCard ไปยัง TarotCardInterpretation
     *
     * ใช้สำหรับ setup เริ่มต้นเพื่อคัดลอกความหมายพื้นฐานไปทุกหมวด
     *
     * @param  int  $id  ID ของไพ่
     */
    public function interpretationsCopyDefaults($id)
    {
        $card = TarotCard::findOrFail($id);
        $categories = TarotReadingCategory::active()->get();

        foreach ($categories as $category) {
            TarotCardInterpretation::updateOrCreate(
                [
                    'card_id' => $card->id,
                    'category_id' => $category->id,
                ],
                [
                    'upright_interpretation_th' => $card->upright_meaning_th,
                    'upright_interpretation_en' => $card->upright_meaning_en,
                    'reversed_interpretation_th' => $card->reversed_meaning_th,
                    'reversed_interpretation_en' => $card->reversed_meaning_en,
                ]
            );
        }

        return redirect()
            ->route('admin.tarot.interpretations.edit', $card->id)
            ->with('success', 'คัดลอกคำทำนายเริ่มต้นไปทุกหมวดสำเร็จ');
    }

    /**
     * คัดลอกคำทำนายเริ่มต้นให้ไพ่ทั้งหมด
     *
     * Bulk operation สำหรับ setup เริ่มต้น
     */
    public function interpretationsCopyAllDefaults()
    {
        $cards = TarotCard::all();
        $categories = TarotReadingCategory::active()->get();
        $count = 0;

        foreach ($cards as $card) {
            foreach ($categories as $category) {
                TarotCardInterpretation::updateOrCreate(
                    [
                        'card_id' => $card->id,
                        'category_id' => $category->id,
                    ],
                    [
                        'upright_interpretation_th' => $card->upright_meaning_th,
                        'upright_interpretation_en' => $card->upright_meaning_en,
                        'reversed_interpretation_th' => $card->reversed_meaning_th,
                        'reversed_interpretation_en' => $card->reversed_meaning_en,
                    ]
                );
                $count++;
            }
        }

        return redirect()
            ->route('admin.tarot.interpretations.index')
            ->with('success', "คัดลอกคำทำนายเริ่มต้นสำเร็จ ({$count} รายการ)");
    }
}
