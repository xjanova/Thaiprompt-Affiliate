<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneResponseTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * จัดการเทมเพลตตอบกลับคำทำนายในระบบ Admin
 */
class FortuneResponseTemplatesController extends Controller
{
    /**
     * แสดงรายการเทมเพลตทั้งหมด
     */
    public function index(Request $request)
    {
        $query = FortuneResponseTemplate::query()
            ->orderBy('type')
            ->orderBy('sort_order');

        // กรองตามประเภท
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // กรองตามสถานะ
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $templates = $query->get();

        // จัดกลุ่มตามประเภท
        $groupedTemplates = $templates->groupBy('type');

        // สถิติ
        $stats = [
            'total' => FortuneResponseTemplate::count(),
            'active' => FortuneResponseTemplate::where('is_active', true)->count(),
            'types' => FortuneResponseTemplate::select('type')
                ->distinct()
                ->pluck('type')
                ->count(),
        ];

        return view('admin.fortune.response-templates.index', [
            'templates' => $templates,
            'groupedTemplates' => $groupedTemplates,
            'stats' => $stats,
            'pageTitle' => 'เทมเพลตตอบกลับคำทำนาย',
        ]);
    }

    /**
     * แสดงฟอร์มสร้างเทมเพลตใหม่
     */
    public function create()
    {
        return view('admin.fortune.response-templates.form', [
            'template' => null,
            'pageTitle' => 'สร้างเทมเพลตใหม่',
        ]);
    }

    /**
     * บันทึกเทมเพลตใหม่
     */
    public function store(Request $request)
    {
        $validated = $this->validateTemplate($request);

        // สร้าง slug อัตโนมัติจากชื่อ (ถ้าไม่ระบุ)
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // แปลง checkbox
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_default'] = $request->boolean('is_default');

        $template = FortuneResponseTemplate::create($validated);

        // ตั้งเป็นเริ่มต้นถ้าเลือก
        if ($template->is_default) {
            $template->setAsDefault();
        }

        return redirect()
            ->route('admin.fortune.response-templates.index')
            ->with('success', "สร้างเทมเพลต '{$template->name}' สำเร็จ");
    }

    /**
     * แสดงฟอร์มแก้ไขเทมเพลต
     */
    public function edit(FortuneResponseTemplate $response_template)
    {
        return view('admin.fortune.response-templates.form', [
            'template' => $response_template,
            'pageTitle' => "แก้ไข: {$response_template->name}",
        ]);
    }

    /**
     * อัพเดทเทมเพลต
     */
    public function update(Request $request, FortuneResponseTemplate $response_template)
    {
        $validated = $this->validateTemplate($request, $response_template->id);

        // แปลง checkbox
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_default'] = $request->boolean('is_default');

        $response_template->update($validated);

        // ตั้งเป็นเริ่มต้นถ้าเลือก
        if ($response_template->is_default) {
            $response_template->setAsDefault();
        }

        return redirect()
            ->route('admin.fortune.response-templates.index')
            ->with('success', "อัพเดทเทมเพลต '{$response_template->name}' สำเร็จ");
    }

    /**
     * ลบเทมเพลต
     */
    public function destroy(FortuneResponseTemplate $response_template)
    {
        $name = $response_template->name;
        $response_template->delete();

        return redirect()
            ->route('admin.fortune.response-templates.index')
            ->with('success', "ลบเทมเพลต '{$name}' สำเร็จ");
    }

    /**
     * ตั้งเป็นเทมเพลตเริ่มต้น
     */
    public function setDefault(FortuneResponseTemplate $response_template)
    {
        $response_template->setAsDefault();

        return redirect()
            ->route('admin.fortune.response-templates.index')
            ->with('success', "ตั้ง '{$response_template->name}' เป็นเทมเพลตเริ่มต้นสำเร็จ");
    }

    /**
     * ดูตัวอย่างเทมเพลต (AJAX)
     */
    public function preview(Request $request)
    {
        $body = $request->input('body', '');
        $headerText = $request->input('header_text', '');
        $footerText = $request->input('footer_text', '');

        // ข้อมูลตัวอย่าง
        $sampleData = [
            'response' => "ดวงความรักของคุณในช่วงนี้มีพลังงานดี มีโอกาสพบเจอคนที่ใช่\nการเงินมีรายรับเข้ามาดี ช่วงกลางเดือนจะมีโชคลาภ\nสุขภาพแข็งแรง แต่ควรระวังเรื่องการพักผ่อน",
            'user_name' => 'สมชาย',
            'date' => now()->format('d/m/Y'),
            'date_thai' => '29 มกราคม 2569',
            'questions' => "1. ความรัก\n2. การเงิน\n3. สุขภาพ",
            'reading_type' => 'เชิงลึก',
            'reading_id' => '12345',
            'rate_url' => url('/fortune/12345/rate'),
            'register_url' => url('/register'),
            'payment_url' => url('/payment'),
            'remaining_free' => '0',
            'max_free' => '3',
            'price' => '29',
        ];

        // สร้างเทมเพลตชั่วคราวเพื่อ render
        $temp = new FortuneResponseTemplate([
            'body' => $body,
            'header_text' => $headerText,
            'footer_text' => $footerText,
        ]);

        $rendered = $temp->render($sampleData);

        return response()->json([
            'success' => true,
            'preview' => $rendered,
        ]);
    }

    /**
     * ตรวจสอบข้อมูลเทมเพลต
     */
    protected function validateTemplate(Request $request, ?int $excludeId = null): array
    {
        $slugUnique = $excludeId
            ? "unique:fortune_response_templates,slug,{$excludeId}"
            : 'unique:fortune_response_templates,slug';

        return $request->validate([
            'name' => 'required|string|max:100',
            'slug' => "nullable|string|max:100|{$slugUnique}",
            'type' => 'required|in:basic,deep,welcome,payment,limit_exceeded,error',
            'body' => 'required|string',
            'header_text' => 'nullable|string|max:500',
            'footer_text' => 'nullable|string|max:500',
            'header_image_url' => 'nullable|url|max:500',
            'footer_image_url' => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
