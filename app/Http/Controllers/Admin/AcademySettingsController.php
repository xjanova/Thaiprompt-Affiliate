<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AcademySettingsController extends Controller
{
    /**
     * Display Academy settings
     */
    public function index()
    {
        $settings = AcademySetting::getInstance();
        return view('admin.academy.settings.index', compact('settings'));
    }

    /**
     * Update basic settings
     */
    public function updateBasic(Request $request)
    {
        $validated = $request->validate([
            'academy_name' => ['required', 'string', 'max:255'],
            'academy_description' => ['nullable', 'string'],
            'academy_email' => ['nullable', 'email', 'max:255'],
            'academy_phone' => ['nullable', 'string', 'max:50'],
            'academy_website' => ['nullable', 'url', 'max:500'],
            'primary_color' => ['required', 'string', 'max:10'],
            'secondary_color' => ['required', 'string', 'max:10'],
        ]);

        $settings = AcademySetting::getInstance();
        $settings->update($validated);
        AcademySetting::clearCache();

        return redirect()->route('admin.academy.settings.index')
            ->with('success', 'บันทึกข้อมูลพื้นฐานเรียบร้อยแล้ว');
    }

    /**
     * Upload logo
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048'], // 2MB max
            'type' => ['required', 'in:main,small,favicon'],
        ]);

        $settings = AcademySetting::getInstance();
        $path = $settings->uploadLogo($request->file('logo'), $request->type);

        return response()->json([
            'success' => true,
            'message' => 'อัพโหลดโลโก้เรียบร้อยแล้ว',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * Upload certificate background
     */
    public function uploadCertificateBackground(Request $request)
    {
        $request->validate([
            'background' => ['required', 'image', 'max:5120'], // 5MB max
        ]);

        $settings = AcademySetting::getInstance();
        $path = $settings->uploadCertificateBackground($request->file('background'));

        return response()->json([
            'success' => true,
            'message' => 'อัพโหลดพื้นหลังใบประกาศเรียบร้อยแล้ว',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * Upload certificate template (Word/PDF)
     */
    public function uploadCertificateTemplate(Request $request)
    {
        $request->validate([
            'template' => ['required', 'file', 'mimes:doc,docx,pdf', 'max:10240'], // 10MB max
            'type' => ['required', 'in:word,pdf'],
        ]);

        $settings = AcademySetting::getInstance();
        $path = $settings->uploadCertificateTemplate($request->file('template'), $request->type);

        return response()->json([
            'success' => true,
            'message' => 'อัพโหลด template เรียบร้อยแล้ว',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * Update certificate settings
     */
    public function updateCertificate(Request $request)
    {
        $validated = $request->validate([
            'certificate_template_type' => ['required', 'in:default,custom,uploaded'],
            'certificate_header_html' => ['nullable', 'string'],
            'certificate_footer_html' => ['nullable', 'string'],
            'certificate_border_style' => ['required', 'in:classic,modern,minimal'],
            'certificate_number_prefix' => ['required', 'string', 'max:10'],
            'certificate_number_length' => ['required', 'integer', 'min:4', 'max:20'],
            'certificate_number_format' => ['required', 'string', 'max:100'],
        ]);

        $settings = AcademySetting::getInstance();
        $settings->update($validated);
        AcademySetting::clearCache();

        return redirect()->route('admin.academy.settings.index')
            ->with('success', 'บันทึกการตั้งค่าใบประกาศนียบัตรเรียบร้อยแล้ว');
    }

    /**
     * Add signature
     */
    public function addSignature(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'signature' => ['required', 'image', 'max:1024'], // 1MB max
            'position' => ['required', 'in:left,center,right'],
        ]);

        $settings = AcademySetting::getInstance();

        // Upload signature image
        $signaturePath = $request->file('signature')->store('academy/signatures', 'public');

        // Add signature to settings
        $settings->addSignature(
            $request->name,
            $request->title,
            $signaturePath,
            $request->position
        );

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มลายเซ็นเรียบร้อยแล้ว',
            'signature' => [
                'name' => $request->name,
                'title' => $request->title,
                'signature_path' => $signaturePath,
                'signature_url' => Storage::disk('public')->url($signaturePath),
                'position' => $request->position,
            ],
        ]);
    }

    /**
     * Remove signature
     */
    public function removeSignature(Request $request, $index)
    {
        $settings = AcademySetting::getInstance();
        $settings->removeSignature($index);

        return response()->json([
            'success' => true,
            'message' => 'ลบลายเซ็นเรียบร้อยแล้ว',
        ]);
    }

    /**
     * Update email settings
     */
    public function updateEmail(Request $request)
    {
        $validated = $request->validate([
            'send_certificate_email' => ['boolean'],
            'certificate_email_subject' => ['nullable', 'string', 'max:500'],
            'certificate_email_body' => ['nullable', 'string'],
        ]);

        $settings = AcademySetting::getInstance();
        $settings->update($validated);
        AcademySetting::clearCache();

        return redirect()->route('admin.academy.settings.index')
            ->with('success', 'บันทึกการตั้งค่าอีเมลเรียบร้อยแล้ว');
    }

    /**
     * Update course settings
     */
    public function updateCourse(Request $request)
    {
        $validated = $request->validate([
            'min_completion_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'min_quiz_score' => ['required', 'integer', 'min:0', 'max:100'],
            'require_all_quizzes' => ['boolean'],
            'enable_student_reviews' => ['boolean'],
            'enable_course_discussions' => ['boolean'],
            'enable_course_notes' => ['boolean'],
            'enable_course_downloads' => ['boolean'],
            'require_enrollment_approval' => ['boolean'],
            'enable_course_preview' => ['boolean'],
        ]);

        $settings = AcademySetting::getInstance();
        $settings->update($validated);
        AcademySetting::clearCache();

        return redirect()->route('admin.academy.settings.index')
            ->with('success', 'บันทึกการตั้งค่าคอร์สเรียบร้อยแล้ว');
    }

    /**
     * Update instructor settings
     */
    public function updateInstructor(Request $request)
    {
        $validated = $request->validate([
            'allow_instructors_create_courses' => ['boolean'],
            'require_course_approval' => ['boolean'],
            'instructor_commission_percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $settings = AcademySetting::getInstance();
        $settings->update($validated);
        AcademySetting::clearCache();

        return redirect()->route('admin.academy.settings.index')
            ->with('success', 'บันทึกการตั้งค่าครูผู้สอนเรียบร้อยแล้ว');
    }

    /**
     * Toggle Academy active status
     */
    public function toggleActive(Request $request)
    {
        $settings = AcademySetting::getInstance();
        $settings->update(['is_active' => !$settings->is_active]);
        AcademySetting::clearCache();

        $status = $settings->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return response()->json([
            'success' => true,
            'message' => $status . 'ระบบ Academy เรียบร้อยแล้ว',
            'is_active' => $settings->is_active,
        ]);
    }
}
