<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecruitTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin Controller สำหรับจัดการ Recruit Templates
 *
 * แอดมินสามารถ:
 * - สร้าง/แก้ไข/ลบ เทมเพลตหน้า Recruit
 * - กำหนดเทมเพลต default
 * - จัดการข้อมูลพื้นฐาน: คู่มือ, ขั้นตอน, ประโยชน์
 */
class RecruitTemplateController extends Controller
{
    /**
     * แสดงรายการเทมเพลตทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $templates = RecruitTemplate::withCount('customizations')
            ->latest()
            ->paginate(15);

        return view('admin.recruit-templates.index', [
            'templates' => $templates,
            'pageTitle' => 'จัดการเทมเพลตหน้า Recruit',
        ]);
    }

    /**
     * แสดงฟอร์มสร้างเทมเพลตใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.recruit-templates.create', [
            'pageTitle' => 'สร้างเทมเพลตใหม่',
        ]);
    }

    /**
     * บันทึกเทมเพลตใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validate ข้อมูล
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'welcome_message' => 'required|string',
            'pitch_message' => 'nullable|string',
            'benefits' => 'nullable|array',
            'benefits.*' => 'string',
            'instructions' => 'nullable|array',
            'instructions.*' => 'string',
            'required_steps' => 'nullable|array',
            'required_steps.*' => 'string',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'require_line_friend' => 'boolean',
            'show_team_leader_info' => 'boolean',
            'show_statistics' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // ถ้าเป็น default ต้องยกเลิก default อื่นๆ ก่อน
            if ($validated['is_default'] ?? false) {
                RecruitTemplate::where('is_default', true)
                    ->update(['is_default' => false]);
            }

            // สร้างเทมเพลต
            $template = RecruitTemplate::create($validated);

            DB::commit();

            return redirect()
                ->route('admin.recruit-templates.index')
                ->with('success', 'สร้างเทมเพลตสำเร็จ');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create recruit template', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * แสดงรายละเอียดเทมเพลต
     *
     * @param RecruitTemplate $recruitTemplate
     * @return \Illuminate\View\View
     */
    public function show(RecruitTemplate $recruitTemplate)
    {
        $recruitTemplate->loadCount('customizations');

        return view('admin.recruit-templates.show', [
            'template' => $recruitTemplate,
            'pageTitle' => 'รายละเอียดเทมเพลต: ' . $recruitTemplate->title,
        ]);
    }

    /**
     * แสดงฟอร์มแก้ไขเทมเพลต
     *
     * @param RecruitTemplate $recruitTemplate
     * @return \Illuminate\View\View
     */
    public function edit(RecruitTemplate $recruitTemplate)
    {
        return view('admin.recruit-templates.edit', [
            'template' => $recruitTemplate,
            'pageTitle' => 'แก้ไขเทมเพลต: ' . $recruitTemplate->title,
        ]);
    }

    /**
     * อัพเดทเทมเพลต
     *
     * @param Request $request
     * @param RecruitTemplate $recruitTemplate
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, RecruitTemplate $recruitTemplate)
    {
        // Validate ข้อมูล
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'welcome_message' => 'required|string',
            'pitch_message' => 'nullable|string',
            'benefits' => 'nullable|array',
            'benefits.*' => 'string',
            'instructions' => 'nullable|array',
            'instructions.*' => 'string',
            'required_steps' => 'nullable|array',
            'required_steps.*' => 'string',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'require_line_friend' => 'boolean',
            'show_team_leader_info' => 'boolean',
            'show_statistics' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // ถ้าเป็น default ต้องยกเลิก default อื่นๆ ก่อน
            if ($validated['is_default'] ?? false) {
                RecruitTemplate::where('is_default', true)
                    ->where('id', '!=', $recruitTemplate->id)
                    ->update(['is_default' => false]);
            }

            // อัพเดทเทมเพลต
            $recruitTemplate->update($validated);

            DB::commit();

            return redirect()
                ->route('admin.recruit-templates.show', $recruitTemplate)
                ->with('success', 'อัพเดทเทมเพลตสำเร็จ');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update recruit template', [
                'template_id' => $recruitTemplate->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ลบเทมเพลต (Soft Delete)
     *
     * @param RecruitTemplate $recruitTemplate
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(RecruitTemplate $recruitTemplate)
    {
        try {
            // ตรวจสอบว่าเป็น default หรือไม่
            if ($recruitTemplate->is_default) {
                return back()->with('error', 'ไม่สามารถลบเทมเพลต default ได้ กรุณาตั้งค่าเทมเพลตอื่นเป็น default ก่อน');
            }

            // ตรวจสอบว่ามีการใช้งานหรือไม่
            $usageCount = $recruitTemplate->customizations()->count();
            if ($usageCount > 0) {
                return back()->with('error', "ไม่สามารถลบได้ เนื่องจากมีแม่ทีม {$usageCount} คนที่ใช้เทมเพลตนี้อยู่");
            }

            // ลบเทมเพลต (Soft Delete)
            $recruitTemplate->delete();

            return redirect()
                ->route('admin.recruit-templates.index')
                ->with('success', 'ลบเทมเพลตสำเร็จ');

        } catch (\Exception $e) {
            \Log::error('Failed to delete recruit template', [
                'template_id' => $recruitTemplate->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ตั้งค่าเป็นเทมเพลต default
     *
     * @param RecruitTemplate $recruitTemplate
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setDefault(RecruitTemplate $recruitTemplate)
    {
        try {
            $recruitTemplate->setAsDefault();

            return back()->with('success', 'ตั้งค่าเป็นเทมเพลต default สำเร็จ');

        } catch (\Exception $e) {
            \Log::error('Failed to set default recruit template', [
                'template_id' => $recruitTemplate->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Toggle สถานะ active/inactive
     *
     * @param RecruitTemplate $recruitTemplate
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleActive(RecruitTemplate $recruitTemplate)
    {
        try {
            $recruitTemplate->is_active = !$recruitTemplate->is_active;
            $recruitTemplate->save();

            $status = $recruitTemplate->is_active ? 'เปิด' : 'ปิด';

            return back()->with('success', "{$status}ใช้งานเทมเพลตสำเร็จ");

        } catch (\Exception $e) {
            \Log::error('Failed to toggle recruit template', [
                'template_id' => $recruitTemplate->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ดูตัวอย่างเทมเพลต
     *
     * @param RecruitTemplate $recruitTemplate
     * @return \Illuminate\View\View
     */
    public function preview(RecruitTemplate $recruitTemplate)
    {
        // จำลองข้อมูลแม่ทีมสำหรับ preview
        $mockTeamLeader = (object) [
            'name' => 'สมชาย ใจดี',
            'member_code' => 'DEMO001',
            'profile_picture_url' => 'https://ui-avatars.com/api/?name=S&background=667eea&color=fff&size=200',
            'phone' => '081-234-5678',
            'address' => 'กรุงเทพมหานคร',
        ];

        return view('recruit.show', [
            'template' => $recruitTemplate,
            'teamLeader' => $mockTeamLeader,
            'customization' => null,
            'isPreview' => true,
        ]);
    }
}
