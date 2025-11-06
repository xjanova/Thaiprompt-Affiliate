<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\LearningArticle;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CertificateManagementController extends Controller
{
    protected $certificateService;

    public function __construct(CertificateService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    /**
     * Display all certificates (Admin view)
     */
    public function index(Request $request)
    {
        $query = Certificate::with(['user', 'article'])
            ->orderBy('issued_at', 'desc');

        // Search by user name, email, or certificate number
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                  ->orWhere('student_name', 'like', "%{$search}%")
                  ->orWhere('student_email', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by course
        if ($request->has('course_id')) {
            $query->where('article_id', $request->course_id);
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'revoked') {
                $query->where('is_revoked', true);
            } elseif ($request->status === 'active') {
                $query->where('is_revoked', false);
            }
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('issued_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('issued_at', '<=', $request->date_to);
        }

        $certificates = $query->paginate(20);

        // Get courses for filter dropdown
        $courses = LearningArticle::select('id', 'title')
            ->orderBy('title')
            ->get();

        // Statistics
        $stats = [
            'total' => Certificate::count(),
            'active' => Certificate::where('is_revoked', false)->count(),
            'revoked' => Certificate::where('is_revoked', true)->count(),
            'this_month' => Certificate::whereMonth('issued_at', now()->month)
                ->whereYear('issued_at', now()->year)
                ->count(),
        ];

        return view('admin.academy.certificates.index', compact('certificates', 'courses', 'stats'));
    }

    /**
     * Show create certificate form
     */
    public function create()
    {
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $courses = LearningArticle::select('id', 'title')
            ->published()
            ->orderBy('title')
            ->get();

        return view('admin.academy.certificates.create', compact('users', 'courses'));
    }

    /**
     * Store manually created certificate
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'article_id' => ['required', 'exists:learning_articles,id'],
            'student_name' => ['nullable', 'string', 'max:255'],
            'student_email' => ['nullable', 'email', 'max:255'],
            'quiz_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'total_hours' => ['nullable', 'numeric', 'min:0'],
            'custom_text' => ['nullable', 'string'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $article = LearningArticle::findOrFail($validated['article_id']);

        try {
            DB::beginTransaction();

            // Check if certificate already exists
            $existing = Certificate::where('user_id', $user->id)
                ->where('article_id', $article->id)
                ->where('is_revoked', false)
                ->first();

            if ($existing) {
                return redirect()->back()
                    ->with('error', 'ผู้ใช้นี้มีใบประกาศนียบัตรสำหรับคอร์สนี้อยู่แล้ว');
            }

            // Create certificate
            $certificate = Certificate::create([
                'certificate_number' => Certificate::generateCertificateNumber(),
                'verification_code' => Certificate::generateVerificationCode(),
                'user_id' => $user->id,
                'article_id' => $article->id,
                'student_name' => $validated['student_name'] ?? $user->name,
                'student_email' => $validated['student_email'] ?? $user->email,
                'course_title' => $article->title,
                'instructor_name' => $article->instructor_name ?? 'Academy Team',
                'quiz_score' => $validated['quiz_score'] ?? null,
                'total_hours' => $validated['total_hours'] ?? 0,
                'custom_text' => $validated['custom_text'] ?? null,
                'issued_at' => now(),
                'issued_by_admin' => true,
            ]);

            // Generate PDF
            $this->certificateService->generatePDF($certificate);

            DB::commit();

            return redirect()->route('admin.academy.certificates.show', $certificate->id)
                ->with('success', 'สร้างใบประกาศนียบัตรเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show certificate details (Admin view)
     */
    public function show($id)
    {
        $certificate = Certificate::with(['user', 'article'])
            ->findOrFail($id);

        return view('admin.academy.certificates.show', compact('certificate'));
    }

    /**
     * Show edit certificate form
     */
    public function edit($id)
    {
        $certificate = Certificate::with(['user', 'article'])
            ->findOrFail($id);

        return view('admin.academy.certificates.edit', compact('certificate'));
    }

    /**
     * Update certificate
     */
    public function update(Request $request, $id)
    {
        $certificate = Certificate::findOrFail($id);

        $validated = $request->validate([
            'student_name' => ['required', 'string', 'max:255'],
            'student_email' => ['required', 'email', 'max:255'],
            'instructor_name' => ['required', 'string', 'max:255'],
            'quiz_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'total_hours' => ['nullable', 'numeric', 'min:0'],
            'custom_text' => ['nullable', 'string'],
            'issued_at' => ['required', 'date'],
        ]);

        try {
            DB::beginTransaction();

            $certificate->update($validated);

            // Regenerate PDF
            $this->certificateService->generatePDF($certificate);

            DB::commit();

            return redirect()->route('admin.academy.certificates.show', $certificate->id)
                ->with('success', 'แก้ไขใบประกาศนียบัตรเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Revoke certificate
     */
    public function revoke(Request $request, $id)
    {
        $certificate = Certificate::findOrFail($id);

        $validated = $request->validate([
            'revoked_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $certificate->update([
            'is_revoked' => true,
            'revoked_at' => now(),
            'revoked_reason' => $validated['revoked_reason'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'เพิกถอนใบประกาศนียบัตรเรียบร้อยแล้ว',
        ]);
    }

    /**
     * Restore revoked certificate
     */
    public function restore($id)
    {
        $certificate = Certificate::findOrFail($id);

        $certificate->update([
            'is_revoked' => false,
            'revoked_at' => null,
            'revoked_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'กู้คืนใบประกาศนียบัตรเรียบร้อยแล้ว',
        ]);
    }

    /**
     * Delete certificate
     */
    public function destroy($id)
    {
        $certificate = Certificate::findOrFail($id);

        // Delete PDF file
        if ($certificate->pdf_path) {
            \Storage::disk('public')->delete($certificate->pdf_path);
        }

        $certificate->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบใบประกาศนียบัตรเรียบร้อยแล้ว',
        ]);
    }

    /**
     * Bulk generate certificates for a course
     */
    public function bulkGenerate(Request $request)
    {
        $validated = $request->validate([
            'article_id' => ['required', 'exists:learning_articles,id'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $article = LearningArticle::findOrFail($validated['article_id']);
        $successCount = 0;
        $errors = [];

        foreach ($validated['user_ids'] as $userId) {
            try {
                $user = User::findOrFail($userId);

                // Check if certificate already exists
                $existing = Certificate::where('user_id', $user->id)
                    ->where('article_id', $article->id)
                    ->where('is_revoked', false)
                    ->first();

                if ($existing) {
                    $errors[] = "{$user->name} มีใบประกาศนียบัตรอยู่แล้ว";
                    continue;
                }

                // Generate certificate
                $this->certificateService->generateCertificate($user, $article);
                $successCount++;

            } catch (\Exception $e) {
                $errors[] = "{$user->name}: " . $e->getMessage();
            }
        }

        $message = "สร้างใบประกาศนียบัตรเรียบร้อย {$successCount} ฉบับ";
        if (!empty($errors)) {
            $message .= "\n" . implode("\n", $errors);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'success_count' => $successCount,
            'errors' => $errors,
        ]);
    }

    /**
     * Export certificates list to CSV
     */
    public function export(Request $request)
    {
        $query = Certificate::with(['user', 'article'])
            ->orderBy('issued_at', 'desc');

        // Apply same filters as index
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                  ->orWhere('student_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('course_id')) {
            $query->where('article_id', $request->course_id);
        }

        if ($request->has('status')) {
            if ($request->status === 'revoked') {
                $query->where('is_revoked', true);
            } elseif ($request->status === 'active') {
                $query->where('is_revoked', false);
            }
        }

        $certificates = $query->get();

        $filename = 'certificates_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($certificates) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($file, [
                'Certificate Number',
                'Student Name',
                'Student Email',
                'Course',
                'Instructor',
                'Quiz Score',
                'Total Hours',
                'Issued Date',
                'Status',
            ]);

            // Data
            foreach ($certificates as $cert) {
                fputcsv($file, [
                    $cert->certificate_number,
                    $cert->student_name,
                    $cert->student_email,
                    $cert->course_title,
                    $cert->instructor_name,
                    $cert->quiz_score ?? '-',
                    $cert->total_hours,
                    $cert->issued_at->format('Y-m-d H:i:s'),
                    $cert->is_revoked ? 'Revoked' : 'Active',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
