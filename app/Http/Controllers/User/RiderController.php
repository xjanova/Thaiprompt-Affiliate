<?php

namespace App\Http\Controllers\User;

use App\Exceptions\RiderJobException;
use App\Http\Controllers\Concerns\RiderJobActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rider\RiderRegistrationRequest;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Services\RiderAccountService;
use App\Services\RiderDispatchService;
use App\Services\RiderEarningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * หน้าเว็บไรเดอร์ /user/rider/* (session auth — ผู้ใช้ที่ล็อกอินเว็บ)
 *
 * (2026-10-13) owner ตัดสิน: "ไรเดอร์รับงานผ่านแอปเท่านั้น ในเว็บรับไม่ได้ แต่สมัครและใช้โปรไฟล์ได้"
 * - ทำได้บนเว็บ: สมัคร (กติกาเดียวกับแอป: RiderRegistrationRequest — ไม่ต้อง eKYC), ดูสถานะ, อัปโหลด/เปิดเอกสาร
 *   (private disk เท่านั้น), แดชบอร์ด, รายการงาน + รายละเอียดงาน (อ่านอย่างเดียว), รายได้, ตั้งค่า + ความยินยอม, ปิดรับงาน
 * - ทำไม่ได้บนเว็บ: เปิดรับงาน, รับ/ไม่สนใจ/คืนงาน, อัปเดตสถานะ, ส่งสำเร็จ, ส่งไม่สำเร็จ, แจ้ง GPS, ส่งตำแหน่งจากเบราว์เซอร์
 *   → ตอบ APP_ONLY (RiderJobException::appOnly) ให้ไปใช้แอป Thai Prompt APP (/app/download)
 *   route เดิมยังอยู่ (หน้าเว็บที่เปิดค้าง/บุ๊กมาร์กได้คำตอบที่อ่านรู้เรื่อง ไม่ใช่ 404)
 *
 * ทุก action ที่เปลี่ยนข้อมูลตอบได้ 2 แบบ:
 *   - AJAX (Accept: application/json) → {success, code?, message, data} (APP_ONLY = 403)
 *   - ฟอร์มปกติ → redirect กลับพร้อม flash success / error
 */
class RiderController extends Controller
{
    use RiderJobActions;

    public function __construct(
        private readonly RiderAccountService $accounts,
        private readonly RiderDispatchService $dispatch,
        private readonly RiderEarningService $earnings,
    ) {}

    /**
     * ประเภทยานพาหนะ / ประเภทงาน — ใช้ค่าชุดเดียวกับแอป (RiderAccountService)
     */
    private const VEHICLE_TYPES = RiderAccountService::VEHICLE_TYPES;

    private const JOB_TYPE_OPTIONS = RiderAccountService::JOB_TYPE_OPTIONS;

    // =====================================================
    // แดชบอร์ด / สมัคร / สถานะ
    // =====================================================

    /**
     * แดชบอร์ดไรเดอร์ (user.rider.dashboard)
     */
    public function index()
    {
        $rider = $this->currentRider();

        if (! $rider) {
            return redirect()->route('user.rider.register');
        }

        if (in_array($rider->status, ['pending', 'rejected', 'inactive'], true)) {
            return redirect()->route('user.rider.status');
        }

        $activeJob = $rider->activeJob();
        [$availableJobs, $availableReason] = $this->availableJobsPayload($rider, $activeJob);
        $blockReason = $rider->acceptBlockReason();

        return view('user.rider.dashboard', [
            'rider' => $rider,
            'riderData' => $this->riderPayload($rider),
            'stats' => [
                'total_jobs' => (int) $rider->total_jobs,
                'completed_jobs' => (int) $rider->completed_jobs,
                'cancelled_jobs' => (int) $rider->cancelled_jobs,
                'completion_rate' => (float) $rider->completion_rate,
                'total_earnings' => round((float) $rider->total_earnings, 2),
                'rating' => round((float) $rider->rating, 2),
                'rating_count' => (int) $rider->rating_count,
            ],
            'activeJob' => $activeJob,
            'activeJobData' => $activeJob?->toApiDetail($rider),
            'todayEarnings' => $this->earnings->summary($rider, 'today'),
            'weekEarnings' => $this->earnings->summary($rider, 'week'),
            // งานรอรับใกล้ตัว (เงื่อนไขเดียวกับหน้า "งาน" และแอป) — แสดงบนแดชบอร์ดพร้อมปุ่มรับงาน
            'availableJobs' => $availableJobs,
            'availableReason' => $availableReason,
            'canAcceptJobs' => $blockReason === null,
            'blockReason' => $blockReason,
            'recentJobs' => RiderJob::where('rider_id', $rider->id)->latest('id')->limit(10)->get(),
            'pageTitle' => 'แดชบอร์ดไรเดอร์',
        ]);
    }

    /**
     * ฟอร์มสมัคร / ส่งใบสมัครใหม่ (user.rider.register)
     *
     * ยังไม่สมัคร = ฟอร์มเปล่า · รอตรวจ/ถูกปฏิเสธ/ไม่ใช้งาน = ฟอร์มพร้อมข้อมูลเดิมให้แก้ · อนุมัติ/ระงับ → แดชบอร์ด
     */
    public function register()
    {
        $rider = $this->currentRider();

        if ($rider && in_array($rider->status, ['approved', 'suspended'], true)) {
            return redirect()->route('user.rider.dashboard');
        }

        return view('user.rider.register', [
            'rider' => $rider,
            'isReapply' => $rider !== null && in_array($rider->status, ['rejected', 'inactive'], true),
            'vehicleTypes' => self::VEHICLE_TYPES,
            'requiredDocumentsByVehicle' => collect(array_keys(self::VEHICLE_TYPES))
                ->mapWithKeys(fn ($v) => [$v => $this->accounts->requiredDocumentTypes($v)])
                ->all(),
            'documentTypes' => RiderAccountService::DOCUMENT_TYPES,
            'documentFlags' => $rider ? $this->accounts->documentFlags($rider) : [],
            'minBirthDate' => now()->subYears(100)->toDateString(),
            'maxBirthDate' => now()->subYears(18)->toDateString(),
            'formAction' => route('user.rider.register.submit'),
            'pageTitle' => $rider ? 'แก้ไขใบสมัครไรเดอร์' : 'สมัครเป็นไรเดอร์',
        ]);
    }

    /**
     * บันทึกใบสมัคร (user.rider.register.submit)
     *
     * หน้าเว็บต้องติ๊กยินยอม PDPA (pdpa_consent) ทุกครั้งที่ส่งใบสมัคร — บันทึกเวลาไว้ใน riders.pdpa_consent_at
     * (API แอปใช้ RiderRegistrationRequest ตัวเดียวกันแต่ไม่ผ่านเมธอดนี้ จึงไม่กระทบแอปรุ่นเก่า)
     */
    public function submitRegistration(RiderRegistrationRequest $request): RedirectResponse
    {
        $request->validate(
            ['pdpa_consent' => ['accepted']],
            ['pdpa_consent.accepted' => 'กรุณาอ่านและยอมรับการเก็บและใช้ข้อมูลส่วนบุคคล (PDPA) ก่อนส่งใบสมัคร']
        );

        try {
            ['rider' => $rider, 'outcome' => $outcome] = $this->accounts->register($request->user(), $request->riderData());
            $this->recordPdpaConsent($rider);
        } catch (RiderJobException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('RiderWeb: register failed', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'สมัครไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        if ($outcome === 'exists') {
            return redirect()->route('user.rider.dashboard')->with('warning', 'คุณเป็นไรเดอร์อยู่แล้ว');
        }

        $message = match ($outcome) {
            'created' => 'ส่งใบสมัครสำเร็จ กรุณาอัปโหลดเอกสารให้ครบเพื่อรอการอนุมัติ',
            'reapplied' => 'ส่งใบสมัครใหม่แล้ว กรุณาตรวจสอบเอกสารให้ครบ',
            default => 'อัปเดตใบสมัครแล้ว',
        };

        return redirect()->route('user.rider.documents')->with('success', $message);
    }

    /**
     * ติดตามสถานะใบสมัคร (user.rider.status)
     */
    public function status()
    {
        $rider = $this->currentRider();

        if (! $rider) {
            return redirect()->route('user.rider.register');
        }

        if ($rider->status === 'approved') {
            return redirect()->route('user.rider.dashboard');
        }

        $missing = $this->accounts->missingDocuments($rider);

        return view('user.rider.status', [
            'rider' => $rider,
            'riderData' => $this->riderPayload($rider),
            'documents' => $this->accounts->documentList($rider, fn (Rider $r, string $t) => $this->documentUrl($t)),
            'missingDocuments' => $missing,
            'missingDocumentLabels' => $this->accounts->documentLabels($missing),
            'canReapply' => in_array($rider->status, ['rejected', 'inactive'], true),
            'pageTitle' => 'ติดตามสถานะการสมัคร',
        ]);
    }

    // =====================================================
    // เอกสาร
    // =====================================================

    /**
     * หน้าอัปโหลดเอกสาร (user.rider.documents)
     */
    public function documents()
    {
        $rider = $this->currentRider();

        if (! $rider) {
            return redirect()->route('user.rider.register');
        }

        $missing = $this->accounts->missingDocuments($rider);

        return view('user.rider.documents', [
            'rider' => $rider,
            'documents' => $this->accounts->documentList($rider, fn (Rider $r, string $t) => $this->documentUrl($t)),
            'documentTypes' => RiderAccountService::DOCUMENT_TYPES,
            'missingDocuments' => $missing,
            'missingDocumentLabels' => $this->accounts->documentLabels($missing),
            'documentsComplete' => $missing === [],
            'documentsPendingReview' => $this->accounts->documentsChangedAt($rider) !== null,
            'uploadUrl' => route('user.rider.documents.upload'),
            'pageTitle' => 'อัปโหลดเอกสารไรเดอร์',
        ]);
    }

    /**
     * อัปโหลดเอกสาร (user.rider.documents.upload) — ฟิลด์ document_type + document
     */
    public function uploadDocument(Request $request): JsonResponse|RedirectResponse
    {
        return $this->handle($request, 'upload_document', function () use ($request) {
            $rider = $this->riderOrFail();

            $data = $this->validateRiderInput($request, [
                'document_type' => ['required', Rule::in(array_keys(RiderAccountService::DOCUMENT_TYPES))],
                'document' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,heic,heif', 'max:10240'],
            ]);

            $rider = $this->accounts->storeDocument($rider, $data['document_type'], $request->file('document'));

            return [
                'อัปโหลดเอกสารสำเร็จ',
                [
                    'type' => $data['document_type'],
                    'uploaded' => true,
                    'url' => $this->documentUrl($data['document_type']),
                    'documents' => $this->accounts->documentFlags($rider),
                    'documents_missing' => $this->accounts->missingDocuments($rider),
                    'documents_complete' => $this->accounts->documentsComplete($rider),
                ],
            ];
        });
    }

    /**
     * เปิดไฟล์เอกสารของตัวเอง (user.rider.documents.file)
     */
    public function documentFile(string $type): StreamedResponse
    {
        $rider = $this->currentRider();

        if (! $rider || ! array_key_exists($type, RiderAccountService::DOCUMENT_TYPES)) {
            abort(404);
        }

        return $this->accounts->documentResponse($rider, $type);
    }

    // =====================================================
    // เปิด-ปิดรับงาน / ความยินยอม / ตำแหน่ง
    // =====================================================

    /**
     * ปิดรับงาน (user.rider.availability) — availability=offline
     *
     * (2026-10-13) เปิดรับงาน (online) ทำได้ในแอปเท่านั้น → APP_ONLY · ปิดรับงานทำได้เสมอ (ไรเดอร์ลืมปิดในแอป)
     */
    public function setAvailability(Request $request): JsonResponse|RedirectResponse
    {
        return $this->handle($request, 'availability', function () use ($request) {
            $rider = $this->riderOrFail();

            $data = $this->validateRiderInput($request, [
                'availability' => ['required', Rule::in(['online', 'offline'])],
            ]);

            if ($data['availability'] === 'online') {
                throw RiderJobException::appOnly();
            }

            $rider = $this->accounts->setAvailability($rider, 'offline');

            return [
                'ปิดรับงานแล้ว',
                [
                    'availability' => (string) $rider->availability,
                    'availability_text' => $rider->availability_text,
                    'can_accept_jobs' => $rider->acceptBlockReason() === null,
                    'block_reason' => $rider->acceptBlockReason(),
                ],
            ];
        });
    }

    /**
     * ยินยอมให้ลูกค้าเห็นตำแหน่งระหว่างส่งงาน (user.rider.consent) — location_consent=1|0
     */
    public function updateConsent(Request $request): JsonResponse|RedirectResponse
    {
        return $this->handle($request, 'consent', function () use ($request) {
            $rider = $this->riderOrFail();

            $data = $this->validateRiderInput($request, [
                'location_consent' => ['required', 'boolean'],
            ]);

            $rider = $this->accounts->updatePermissions($rider, ['location_consent' => $data['location_consent']]);

            return [
                $rider->hasLocationConsent() ? 'บันทึกความยินยอมแล้ว' : 'ยกเลิกความยินยอมแล้ว',
                ['location_consent' => $rider->hasLocationConsent()],
            ];
        });
    }

    /**
     * ส่งตำแหน่งจากเบราว์เซอร์ (user.rider.location)
     *
     * (2026-10-13) ตำแหน่งระหว่างรับงาน/ส่งงานส่งจากแอปเท่านั้น → APP_ONLY
     */
    public function updateLocation(Request $request): JsonResponse|RedirectResponse
    {
        return $this->appOnly($request, 'location');
    }

    // =====================================================
    // งาน
    // =====================================================

    /**
     * รายการงาน (user.rider.jobs) — ?tab=available|current|history&status=completed|cancelled|failed
     */
    public function jobs(Request $request)
    {
        $rider = $this->currentRider();

        if (! $rider) {
            return redirect()->route('user.rider.register');
        }

        if (! in_array($rider->status, ['approved', 'suspended'], true)) {
            return redirect()->route('user.rider.status');
        }

        $tab = in_array($request->query('tab'), ['available', 'current', 'history'], true)
            ? $request->query('tab')
            : 'available';
        $statusFilter = in_array($request->query('status'), RiderJob::TERMINAL_STATUSES, true)
            ? $request->query('status')
            : null;

        $activeJob = $rider->activeJob();

        // งานที่รอรับ (เงื่อนไขเดียวกับแอป)
        [$availableJobs, $availableReason] = $this->availableJobsPayload($rider, $activeJob);

        $history = RiderJob::query()
            ->where('rider_id', $rider->id)
            ->whereIn('status', $statusFilter ? [$statusFilter] : RiderJob::TERMINAL_STATUSES)
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $blockReason = $rider->acceptBlockReason();

        return view('user.rider.jobs', [
            'rider' => $rider,
            'tab' => $tab,
            'statusFilter' => $statusFilter,
            'availableJobs' => $availableJobs,
            'availableReason' => $availableReason,
            'currentJob' => $activeJob,
            'currentJobData' => $activeJob?->toApiDetail($rider),
            'history' => $history,
            'historyItems' => collect($history->items())->map(fn (RiderJob $job) => $job->toApiSummary($rider))->all(),
            'canAcceptJobs' => $blockReason === null,
            'blockReason' => $blockReason,
            'pageTitle' => 'งานไรเดอร์',
        ]);
    }

    /**
     * รายละเอียดงาน (user.rider.jobs.show) — อ่านอย่างเดียว · เห็นได้เฉพาะงานของตัวเอง หรืองานที่ยังเปิดรับและเสนอให้ตัวเองได้
     *
     * (2026-10-13) ไม่มีปุ่มทำงานบนเว็บแล้ว — แบนเนอร์พาไปทำงานในแอป
     */
    public function showJob(RiderJob $job)
    {
        $rider = $this->currentRider();

        if (! $rider || ! $this->canViewJob($job, $rider)) {
            abort(403, 'ไม่มีสิทธิ์เข้าถึงงานนี้');
        }

        $isMine = $job->rider_id !== null && (int) $job->rider_id === (int) $rider->id;

        return view('user.rider.job-detail', [
            'rider' => $rider,
            'job' => $job,
            'jobData' => $job->toApiDetail($rider),
            'isMine' => $isMine,
            'codAmount' => round((float) $job->cod_amount, 2),
            'pageTitle' => 'งาน #'.$job->job_number,
        ]);
    }

    // ===== ปุ่มงาน — ทำได้ในแอปเท่านั้น (2026-10-13) =====
    // route เดิมยังอยู่ ตอบ APP_ONLY (JSON 403 / redirect กลับพร้อม flash error) — ไม่แตะงานเลย

    /**
     * รับงาน (user.rider.jobs.accept) → APP_ONLY
     */
    public function acceptJob(Request $request, RiderJob $job): JsonResponse|RedirectResponse
    {
        return $this->appOnly($request, 'accept');
    }

    /**
     * ไม่สนใจงาน (user.rider.jobs.reject) → APP_ONLY
     */
    public function rejectJob(Request $request, RiderJob $job): JsonResponse|RedirectResponse
    {
        return $this->appOnly($request, 'reject');
    }

    /**
     * คืนงาน (user.rider.jobs.release) → APP_ONLY
     */
    public function releaseJob(Request $request, RiderJob $job): JsonResponse|RedirectResponse
    {
        return $this->appOnly($request, 'release');
    }

    /**
     * อัปเดตสถานะระหว่างทาง (user.rider.jobs.status) → APP_ONLY
     */
    public function updateJobStatus(Request $request, RiderJob $job): JsonResponse|RedirectResponse
    {
        return $this->appOnly($request, 'update_status');
    }

    /**
     * ส่งของสำเร็จ (user.rider.jobs.deliver) → APP_ONLY
     */
    public function deliverJob(Request $request, RiderJob $job): JsonResponse|RedirectResponse
    {
        return $this->appOnly($request, 'deliver');
    }

    /**
     * ส่งไม่สำเร็จ (user.rider.jobs.fail) → APP_ONLY
     */
    public function failJob(Request $request, RiderJob $job): JsonResponse|RedirectResponse
    {
        return $this->appOnly($request, 'fail');
    }

    /**
     * แจ้ง GPS หาย (user.rider.jobs.gps-lost) → APP_ONLY
     */
    public function reportGpsLost(Request $request, RiderJob $job): JsonResponse|RedirectResponse
    {
        return $this->appOnly($request, 'gps_lost');
    }

    /**
     * ยืนยันปิด GPS (user.rider.jobs.gps-off) → APP_ONLY
     */
    public function confirmGpsOff(Request $request, RiderJob $job): JsonResponse|RedirectResponse
    {
        return $this->appOnly($request, 'gps_off');
    }

    // =====================================================
    // รายได้ / ตั้งค่า
    // =====================================================

    /**
     * รายได้ (user.rider.earnings) — ?period=today|week|month|all
     */
    public function earnings(Request $request)
    {
        $rider = $this->currentRider();

        if (! $rider) {
            return redirect()->route('user.rider.register');
        }

        if (! in_array($rider->status, ['approved', 'suspended'], true)) {
            return redirect()->route('user.rider.status');
        }

        $period = in_array($request->query('period'), ['today', 'week', 'month', 'all'], true)
            ? $request->query('period')
            : 'week';

        $summary = $this->earnings->summary($rider, $period);

        return view('user.rider.earnings', [
            'rider' => $rider,
            'period' => $period,
            'periodOptions' => ['today' => 'วันนี้', 'week' => 'สัปดาห์นี้', 'month' => 'เดือนนี้', 'all' => 'ทั้งหมด'],
            'summary' => $summary,
            'dailyEarnings' => $summary['daily'],
            'monthlyEarnings' => $this->earnings->summary($rider, 'month')['gross_earnings'],
            'walletBalance' => $rider->walletBalance(),
            'recentJobs' => RiderJob::where('rider_id', $rider->id)
                ->where('status', 'completed')
                ->orderByDesc('completed_at')
                ->paginate(15)
                ->withQueryString(),
            'kyc' => $this->riderPayload($rider)['kyc'],
            'pageTitle' => 'รายได้ไรเดอร์',
        ]);
    }

    /**
     * ตั้งค่า (user.rider.settings)
     */
    public function settings()
    {
        $rider = $this->currentRider();

        if (! $rider) {
            return redirect()->route('user.rider.register');
        }

        return view('user.rider.settings', [
            'rider' => $rider,
            'riderData' => $this->riderPayload($rider),
            'vehicleTypes' => self::VEHICLE_TYPES,
            'jobTypeOptions' => self::JOB_TYPE_OPTIONS,
            'preferences' => [
                'job_types' => $rider->preferred_job_types ?? [],
                'radius_km' => $rider->preferred_radius_km !== null ? (float) $rider->preferred_radius_km : null,
                'min_fee' => $rider->preferred_min_fee !== null ? (float) $rider->preferred_min_fee : null,
            ],
            'updateUrl' => route('user.rider.settings.update'),
            'consentUrl' => route('user.rider.consent'),
            'pageTitle' => 'ตั้งค่าไรเดอร์',
        ]);
    }

    /**
     * บันทึกตั้งค่า (user.rider.settings.update)
     *
     * เปลี่ยนเป็นยานพาหนะที่ต้องมีเอกสารเพิ่ม (ใบขับขี่/ทะเบียนรถ) หลังอนุมัติ → แจ้งให้อัปโหลด + แอดมินตรวจซ้ำ
     */
    public function updateSettings(Request $request): JsonResponse|RedirectResponse
    {
        return $this->handle($request, 'settings', function () use ($request) {
            $rider = $this->riderOrFail();

            $request->merge([
                'phone' => preg_replace('/[\s\-().]+/', '', (string) $request->input('phone')),
            ]);

            $data = $this->validateRiderInput($request, RiderAccountService::profileRules(), RiderAccountService::profileMessages());

            ['rider' => $rider, 'vehicle_changed' => $vehicleChanged, 'missing' => $missing] = $this->accounts->updateProfile($rider, $data);

            $message = 'บันทึกการตั้งค่าสำเร็จ';
            if ($vehicleChanged && $missing !== []) {
                $message .= ' — กรุณาอัปโหลด'.$this->accounts->documentLabels($missing).'เพิ่มเติม';
            }

            return [$message, ['rider' => $this->riderPayload($rider)]];
        });
    }

    // =====================================================
    // ภายใน
    // =====================================================

    private function currentRider(): ?Rider
    {
        return $this->accounts->findForUser(Auth::user());
    }

    /**
     * งานรอรับใกล้ไรเดอร์ (เงื่อนไขเดียวกับแอป) + เหตุผลเมื่อแสดงไม่ได้
     *
     * @return array{0: array<int, array<string, mixed>>, 1: ?string} [JobSummary[], null|not_approved|busy|offline|no_location]
     */
    private function availableJobsPayload(Rider $rider, ?RiderJob $activeJob): array
    {
        if ($rider->status !== 'approved') {
            return [[], 'not_approved'];
        }

        if ($activeJob) {
            return [[], 'busy'];
        }

        if ($rider->availability !== 'online') {
            return [[], 'offline'];
        }

        if ($rider->last_latitude === null || $rider->last_longitude === null) {
            return [[], 'no_location'];
        }

        $jobs = $this->dispatch->availableJobsFor($rider, null, null, 30)
            ->reject(fn (RiderJob $job) => $this->rejectedByRider($job, $rider))
            ->map(fn (RiderJob $job) => $job->toApiSummary($rider))
            ->values()
            ->all();

        return [$jobs, null];
    }

    /**
     * บันทึกเวลาที่ผู้สมัครยินยอม PDPA (ถ้ายังไม่ได้ migrate คอลัมน์ ข้ามไปเงียบ ๆ — ใบสมัครต้องไม่ล้มเพราะเรื่องนี้)
     */
    private function recordPdpaConsent(Rider $rider): void
    {
        try {
            if (Schema::hasColumn('riders', 'pdpa_consent_at')) {
                Rider::withTrashed()->whereKey($rider->id)->update(['pdpa_consent_at' => now()]);
            }
        } catch (\Throwable $e) {
            Log::warning('RiderWeb: cannot record PDPA consent', ['rider_id' => $rider->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @throws RiderJobException NOT_RIDER
     */
    private function riderOrFail(): Rider
    {
        $rider = $this->currentRider();

        if (! $rider) {
            throw new RiderJobException('NOT_RIDER', 'กรุณาสมัครเป็นไรเดอร์ก่อน', 403);
        }

        return $rider;
    }

    /**
     * ข้อมูลไรเดอร์ชุดเดียวกับแอป (ลิงก์เอกสารเป็น route session ของเว็บ)
     *
     * @return array<string, mixed>
     */
    private function riderPayload(Rider $rider): array
    {
        return $this->accounts->statusPayload($rider, fn (Rider $r, string $t) => $this->documentUrl($t));
    }

    private function documentUrl(string $type): string
    {
        return route('user.rider.documents.file', ['type' => $type]);
    }

    /**
     * ตอบ "ทำได้ในแอปเท่านั้น" (APP_ONLY) — AJAX = JSON 403 / ฟอร์มปกติ = redirect กลับพร้อม flash error
     *
     * ไม่ตรวจว่าเป็นไรเดอร์ก่อน: ผลเหมือนกันทุกคน และไม่แตะข้อมูลงานเลย
     */
    private function appOnly(Request $request, string $action): JsonResponse|RedirectResponse
    {
        return $this->handle($request, $action, function () {
            throw RiderJobException::appOnly();
        });
    }

    /**
     * ครอบ action ที่เปลี่ยนข้อมูล → JSON (AJAX) หรือ redirect พร้อม flash
     *
     * callback คืน [ข้อความ, data, ?redirectUrl]
     *
     * @param  callable(): array{0: string, 1?: array<string, mixed>, 2?: ?string}  $callback
     */
    private function handle(Request $request, string $action, callable $callback): JsonResponse|RedirectResponse
    {
        $wantsJson = $request->expectsJson() || $request->ajax();

        try {
            $result = $callback();
            $message = $result[0];
            $data = $result[1] ?? [];
            $redirect = $result[2] ?? null;

            if ($wantsJson) {
                return response()->json(['success' => true, 'message' => $message, 'data' => $data]);
            }

            return ($redirect ? redirect()->to($redirect) : back())->with('success', $message);
        } catch (RiderJobException $e) {
            if ($wantsJson) {
                return response()->json($e->toArray(), $e->httpStatus);
            }

            return back()->withInput()->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->validator->errors()->first() ?: 'ข้อมูลไม่ถูกต้อง',
                    'errors' => $e->errors(),
                    'data' => null,
                ], 422);
            }

            throw $e;
        } catch (\Throwable $e) {
            Log::error('RiderWeb: '.$action.' failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            $message = 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';

            if ($wantsJson) {
                return response()->json(['success' => false, 'code' => 'SERVER_ERROR', 'message' => $message, 'data' => null], 500);
            }

            return back()->withInput()->with('error', $message);
        }
    }
}
