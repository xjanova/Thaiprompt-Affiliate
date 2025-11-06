<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\LearningArticle;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    protected $certificateService;

    public function __construct(CertificateService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    /**
     * Display user's certificates
     */
    public function index()
    {
        $user = Auth::user();
        $certificates = $this->certificateService->getUserCertificates($user);

        return view('admin.certificates.index', compact('certificates'));
    }

    /**
     * Generate certificate for a course
     */
    public function generate(Request $request, $articleId)
    {
        $user = Auth::user();
        $article = LearningArticle::findOrFail($articleId);

        try {
            // Check if user can get certificate
            if (!$this->certificateService->canIssueCertificate($user, $article)) {
                return redirect()->back()
                    ->with('error', 'คุณยังไม่สามารถรับใบประกาศนียบัตรสำหรับคอร์สนี้ได้ กรุณาเรียนจบและผ่าน Quiz ที่กำหนดก่อน');
            }

            $certificate = $this->certificateService->generateCertificate($user, $article);

            return redirect()->route('admin.certificates.show', $certificate->id)
                ->with('success', 'สร้างใบประกาศนียบัตรสำเร็จ!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Show certificate details
     */
    public function show($id)
    {
        $user = Auth::user();
        $certificate = Certificate::where('id', $id)
            ->where('user_id', $user->id)
            ->with('article')
            ->firstOrFail();

        return view('admin.certificates.show', compact('certificate'));
    }

    /**
     * Download certificate PDF
     */
    public function download($id)
    {
        $user = Auth::user();
        $certificate = Certificate::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (!$certificate->isValid()) {
            abort(403, 'This certificate has been revoked');
        }

        if (!$certificate->pdf_path || !Storage::disk('public')->exists($certificate->pdf_path)) {
            // Regenerate if missing
            $this->certificateService->generatePDF($certificate);
        }

        $filename = "certificate_{$certificate->certificate_number}.html";

        return Storage::disk('public')->download($certificate->pdf_path, $filename);
    }

    /**
     * Public certificate verification
     */
    public function verify($verificationCode)
    {
        $certificate = $this->certificateService->verifyCertificate($verificationCode);

        if (!$certificate) {
            return view('certificates.verify', [
                'found' => false,
                'message' => 'ไม่พบใบประกาศนียบัตรนี้ในระบบ',
            ]);
        }

        if (!$certificate->isValid()) {
            return view('certificates.verify', [
                'found' => true,
                'valid' => false,
                'certificate' => $certificate,
                'message' => 'ใบประกาศนียบัตรนี้ถูกเพิกถอนแล้ว',
            ]);
        }

        return view('certificates.verify', [
            'found' => true,
            'valid' => true,
            'certificate' => $certificate,
            'message' => 'ใบประกาศนียบัตรนี้ถูกต้องและเป็นของจริง',
        ]);
    }

    /**
     * Share certificate (public view)
     */
    public function share($id)
    {
        $certificate = Certificate::where('id', $id)
            ->with(['user', 'article'])
            ->firstOrFail();

        if (!$certificate->isValid()) {
            abort(403, 'This certificate has been revoked');
        }

        return view('certificates.share', compact('certificate'));
    }
}
