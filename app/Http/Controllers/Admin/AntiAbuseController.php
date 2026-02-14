<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceBooking;
use App\Models\ServiceBookingDispute;
use App\Models\ServiceBookingLocationLog;
use App\Models\ServiceBookingPenalty;
use App\Models\User;
use App\Models\UserBlock;
use App\Models\UserTrustScore;
use App\Services\AntiAbuseService;
use Illuminate\Http\Request;

/**
 * AntiAbuseController - Admin Panel
 *
 * จัดการระบบป้องกันการกลั่นแกล้งทั้งหมด:
 * - Disputes (ข้อร้องเรียน)
 * - Trust Scores (คะแนนความน่าเชื่อถือ)
 * - Penalties (ค่าปรับ)
 * - Location History (ประวัติตำแหน่ง)
 * - User Blocks (การ Block)
 */
class AntiAbuseController extends Controller
{
    public function __construct(
        protected AntiAbuseService $antiAbuseService
    ) {}

    // ============================================
    // Dashboard
    // ============================================

    /**
     * หน้า Dashboard หลักของ Anti-Abuse
     */
    public function dashboard()
    {
        // สถิติภาพรวม
        $stats = [
            'pending_disputes' => ServiceBookingDispute::where('status', 'pending')->count(),
            'investigating_disputes' => ServiceBookingDispute::where('status', 'investigating')->count(),
            'high_priority_disputes' => ServiceBookingDispute::whereIn('priority', ['high', 'critical'])->open()->count(),
            'pending_penalties' => ServiceBookingPenalty::where('status', 'pending')->count(),
            'total_penalty_amount' => ServiceBookingPenalty::where('status', 'pending')->sum('penalty_amount'),
            'suspended_users' => UserTrustScore::where('trust_level', 'suspended')->count(),
            'banned_users' => UserTrustScore::where('trust_level', 'banned')->count(),
            'warning_users' => UserTrustScore::where('trust_level', 'warning')->count(),
            'active_blocks' => UserBlock::active()->count(),
        ];

        // Disputes ล่าสุด
        $recentDisputes = ServiceBookingDispute::with(['booking', 'reporterUser', 'accusedUser', 'accusedProvider'])
            ->latest()
            ->limit(10)
            ->get();

        // Penalties ที่รอดำเนินการ
        $pendingPenalties = ServiceBookingPenalty::with(['booking', 'user', 'provider'])
            ->where('status', 'pending')
            ->latest()
            ->limit(10)
            ->get();

        // Users ที่มีปัญหา
        $problemUsers = UserTrustScore::with(['user', 'provider'])
            ->whereIn('trust_level', ['warning', 'restricted', 'suspended'])
            ->orderBy('trust_score', 'asc')
            ->limit(10)
            ->get();

        return view('admin.anti-abuse.dashboard', [
            'stats' => $stats,
            'recentDisputes' => $recentDisputes,
            'pendingPenalties' => $pendingPenalties,
            'problemUsers' => $problemUsers,
            'pageTitle' => 'ศูนย์ป้องกันการกลั่นแกล้ง',
        ]);
    }

    // ============================================
    // Disputes Management
    // ============================================

    /**
     * รายการข้อร้องเรียนทั้งหมด
     */
    public function disputes(Request $request)
    {
        $query = ServiceBookingDispute::with(['booking', 'reporterUser', 'reporterProvider', 'accusedUser', 'accusedProvider', 'handler']);

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // กรองตามประเภท
        if ($request->filled('dispute_type')) {
            $query->where('dispute_type', $request->dispute_type);
        }

        // กรองตาม priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('dispute_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('booking', function ($q2) use ($search) {
                        $q2->where('booking_number', 'like', "%{$search}%");
                    });
            });
        }

        $disputes = $query->latest()->paginate(20);

        return view('admin.anti-abuse.disputes.index', [
            'disputes' => $disputes,
            'disputeTypes' => ServiceBookingDispute::DISPUTE_TYPES,
            'statuses' => ServiceBookingDispute::STATUSES,
            'pageTitle' => 'จัดการข้อร้องเรียน',
        ]);
    }

    /**
     * รายละเอียดข้อร้องเรียน
     */
    public function showDispute(ServiceBookingDispute $dispute)
    {
        $dispute->load([
            'booking.service',
            'booking.user',
            'booking.provider',
            'reporterUser',
            'reporterProvider',
            'accusedUser',
            'accusedProvider',
            'handler',
        ]);

        // ดึงประวัติตำแหน่ง
        $locationHistory = [];
        if ($dispute->booking) {
            $locationHistory = $this->antiAbuseService->getLocationHistory($dispute->booking);
        }

        // ดึง Trust Score ของทั้งสองฝ่าย
        $reporterTrustScore = null;
        $accusedTrustScore = null;

        if ($dispute->reporter_type === 'user' && $dispute->reporterUser) {
            $reporterTrustScore = UserTrustScore::getOrCreateForUser($dispute->reporterUser);
        }

        if ($dispute->accused_type === 'user' && $dispute->accusedUser) {
            $accusedTrustScore = UserTrustScore::getOrCreateForUser($dispute->accusedUser);
        } elseif ($dispute->accused_type === 'provider' && $dispute->accusedProvider) {
            $accusedTrustScore = UserTrustScore::getOrCreateForProvider($dispute->accusedProvider);
        }

        return view('admin.anti-abuse.disputes.show', [
            'dispute' => $dispute,
            'locationHistory' => $locationHistory,
            'reporterTrustScore' => $reporterTrustScore,
            'accusedTrustScore' => $accusedTrustScore,
            'pageTitle' => 'ข้อร้องเรียน #'.$dispute->dispute_number,
        ]);
    }

    /**
     * อัพเดทสถานะข้อร้องเรียน
     */
    public function updateDisputeStatus(Request $request, ServiceBookingDispute $dispute)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,investigating,awaiting_response,resolved_favor_reporter,resolved_favor_accused,resolved_mutual,dismissed,escalated',
            'resolution_notes' => 'nullable|string|max:2000',
            'refund_amount' => 'nullable|numeric|min:0',
            'penalty_amount' => 'nullable|numeric|min:0',
            'resulted_in_ban' => 'nullable|boolean',
        ]);

        $dispute->update([
            'status' => $validated['status'],
            'resolution_notes' => $validated['resolution_notes'] ?? $dispute->resolution_notes,
            'refund_amount' => $validated['refund_amount'] ?? 0,
            'penalty_amount' => $validated['penalty_amount'] ?? 0,
            'resulted_in_ban' => $validated['resulted_in_ban'] ?? false,
            'handled_by' => auth()->id(),
            'resolved_at' => in_array($validated['status'], ['resolved_favor_reporter', 'resolved_favor_accused', 'resolved_mutual', 'dismissed']) ? now() : null,
        ]);

        // ถ้าตัดสินแล้ว ให้อัพเดท Trust Score
        if (in_array($validated['status'], ['resolved_favor_reporter', 'resolved_favor_accused'])) {
            $this->handleDisputeResolution($dispute, $validated);
        }

        return redirect()
            ->route('admin.anti-abuse.disputes.show', $dispute)
            ->with('success', 'อัพเดทสถานะเรียบร้อยแล้ว');
    }

    /**
     * ตัดสินข้อร้องเรียน (Resolve)
     */
    public function resolveDispute(Request $request, ServiceBookingDispute $dispute)
    {
        $validated = $request->validate([
            'resolution' => 'required|in:favor_reporter,favor_accused,mutual,dismissed',
            'resolution_notes' => 'required|string|max:2000',
            'refund_amount' => 'nullable|numeric|min:0',
            'penalty_amount' => 'nullable|numeric|min:0',
            'resulted_in_ban' => 'nullable|boolean',
        ]);

        $statusMap = [
            'favor_reporter' => 'resolved_favor_reporter',
            'favor_accused' => 'resolved_favor_accused',
            'mutual' => 'resolved_mutual',
            'dismissed' => 'dismissed',
        ];

        $dispute->update([
            'status' => $statusMap[$validated['resolution']],
            'resolution_notes' => $validated['resolution_notes'],
            'refund_amount' => $validated['refund_amount'] ?? 0,
            'penalty_amount' => $validated['penalty_amount'] ?? 0,
            'resulted_in_ban' => $validated['resulted_in_ban'] ?? false,
            'handled_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        // จัดการผลการตัดสิน
        if (in_array($validated['resolution'], ['favor_reporter', 'favor_accused'])) {
            $this->handleDisputeResolution($dispute, array_merge($validated, [
                'status' => $statusMap[$validated['resolution']],
            ]));
        }

        return redirect()
            ->route('admin.anti-abuse.disputes.show', $dispute)
            ->with('success', 'ตัดสินข้อร้องเรียนเรียบร้อยแล้ว');
    }

    /**
     * จัดการผลการตัดสินข้อร้องเรียน
     */
    protected function handleDisputeResolution(ServiceBookingDispute $dispute, array $data): void
    {
        // ถ้าตัดสินให้ผู้ร้อง = ผู้ถูกร้องแพ้
        if ($data['status'] === 'resolved_favor_reporter') {
            if ($dispute->accused_type === 'user' && $dispute->accusedUser) {
                $trustScore = UserTrustScore::getOrCreateForUser($dispute->accusedUser);
                $trustScore->penalizeForDisputeLost();

                if ($data['resulted_in_ban'] ?? false) {
                    $trustScore->ban('แพ้ข้อร้องเรียน #'.$dispute->dispute_number);
                }
            } elseif ($dispute->accused_type === 'provider' && $dispute->accusedProvider) {
                $trustScore = UserTrustScore::getOrCreateForProvider($dispute->accusedProvider);
                $trustScore->penalizeForDisputeLost();

                if ($data['resulted_in_ban'] ?? false) {
                    $trustScore->ban('แพ้ข้อร้องเรียน #'.$dispute->dispute_number);
                }
            }

            // สร้าง Penalty ถ้ามี
            if (($data['penalty_amount'] ?? 0) > 0) {
                ServiceBookingPenalty::create([
                    'service_booking_id' => $dispute->service_booking_id,
                    'user_id' => $dispute->accused_type === 'user' ? $dispute->accused_user_id : null,
                    'provider_id' => $dispute->accused_type === 'provider' ? $dispute->accused_provider_id : null,
                    'penalized_type' => $dispute->accused_type,
                    'penalty_type' => 'dispute_lost',
                    'penalty_amount' => $data['penalty_amount'],
                    'trust_score_deduction' => 15,
                    'status' => 'pending',
                    'reason' => 'แพ้ข้อร้องเรียน #'.$dispute->dispute_number,
                ]);
            }
        }
    }

    // ============================================
    // Trust Scores Management
    // ============================================

    /**
     * รายการ Trust Scores ทั้งหมด
     */
    public function trustScores(Request $request)
    {
        $query = UserTrustScore::with(['user', 'provider']);

        // กรองตามประเภท
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        // กรองตามระดับ
        if ($request->filled('trust_level')) {
            $query->where('trust_level', $request->trust_level);
        }

        // กรองคะแนนต่ำ
        if ($request->boolean('low_score')) {
            $query->where('trust_score', '<', 60);
        }

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('provider', function ($q2) use ($search) {
                    $q2->where('display_name', 'like', "%{$search}%");
                });
            });
        }

        $trustScores = $query->orderBy('trust_score', 'asc')->paginate(20);

        return view('admin.anti-abuse.trust-scores.index', [
            'trustScores' => $trustScores,
            'trustLevels' => UserTrustScore::TRUST_LEVELS,
            'pageTitle' => 'จัดการคะแนนความน่าเชื่อถือ',
        ]);
    }

    /**
     * รายละเอียด Trust Score
     */
    public function showTrustScore(UserTrustScore $trustScore)
    {
        $trustScore->load(['user', 'provider']);

        // ดึงประวัติ Penalties
        $penalties = ServiceBookingPenalty::where(function ($q) use ($trustScore) {
            if ($trustScore->entity_type === 'user') {
                $q->where('user_id', $trustScore->user_id);
            } else {
                $q->where('provider_id', $trustScore->provider_id);
            }
        })->latest()->limit(20)->get();

        // ดึงประวัติ Disputes
        $disputes = ServiceBookingDispute::where(function ($q) use ($trustScore) {
            if ($trustScore->entity_type === 'user') {
                $q->where('accused_user_id', $trustScore->user_id)
                    ->orWhere('reporter_user_id', $trustScore->user_id);
            } else {
                $q->where('accused_provider_id', $trustScore->provider_id)
                    ->orWhere('reporter_provider_id', $trustScore->provider_id);
            }
        })->latest()->limit(20)->get();

        // ดึงประวัติ Bookings
        $bookings = ServiceBooking::where(function ($q) use ($trustScore) {
            if ($trustScore->entity_type === 'user') {
                $q->where('user_id', $trustScore->user_id);
            } else {
                $q->where('provider_id', $trustScore->provider_id);
            }
        })->with(['service', 'user', 'provider'])->latest()->limit(20)->get();

        return view('admin.anti-abuse.trust-scores.show', [
            'trustScore' => $trustScore,
            'penalties' => $penalties,
            'disputes' => $disputes,
            'bookings' => $bookings,
            'pageTitle' => 'Trust Score: '.($trustScore->user?->name ?? $trustScore->provider?->display_name),
        ]);
    }

    /**
     * ปรับคะแนน Trust Score
     */
    public function adjustTrustScore(Request $request, UserTrustScore $trustScore)
    {
        $validated = $request->validate([
            'action' => 'required|in:adjust_score,suspend,ban,unban,reset',
            'score_adjustment' => 'nullable|numeric|between:-100,100',
            'suspension_days' => 'nullable|integer|min:1|max:365',
            'reason' => 'required|string|max:500',
        ]);

        switch ($validated['action']) {
            case 'adjust_score':
                $trustScore->trust_score = max(0, min(100, $trustScore->trust_score + ($validated['score_adjustment'] ?? 0)));
                $trustScore->recalculateTrustScore();
                $message = 'ปรับคะแนนเรียบร้อยแล้ว';
                break;

            case 'suspend':
                $trustScore->suspend($validated['suspension_days'] ?? 7, $validated['reason']);
                $message = 'ระงับการใช้งาน '.($validated['suspension_days'] ?? 7).' วัน';
                break;

            case 'ban':
                $trustScore->ban($validated['reason']);
                $message = 'แบนเรียบร้อยแล้ว';
                break;

            case 'unban':
                $trustScore->unban();
                $message = 'ปลดแบนเรียบร้อยแล้ว';
                break;

            case 'reset':
                $trustScore->update([
                    'trust_score' => 80,
                    'payment_score' => 100,
                    'cancellation_score' => 100,
                    'punctuality_score' => 100,
                    'behavior_score' => 100,
                ]);
                $trustScore->recalculateTrustScore();
                $message = 'รีเซ็ตคะแนนเรียบร้อยแล้ว';
                break;
        }

        // บันทึก Log
        activity()
            ->performedOn($trustScore)
            ->causedBy(auth()->user())
            ->withProperties(['action' => $validated['action'], 'reason' => $validated['reason']])
            ->log('admin_trust_score_action');

        return redirect()
            ->route('admin.anti-abuse.trust-scores.show', $trustScore)
            ->with('success', $message);
    }

    /**
     * ระงับการใช้งานชั่วคราว
     */
    public function suspendUser(Request $request, UserTrustScore $trustScore)
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
            'reason' => 'required|string|max:500',
        ]);

        $trustScore->suspend($validated['days'], $validated['reason']);

        // บันทึก Log
        activity()
            ->performedOn($trustScore)
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'suspend', 'days' => $validated['days'], 'reason' => $validated['reason']])
            ->log('admin_trust_score_suspend');

        return redirect()
            ->route('admin.anti-abuse.trust-scores')
            ->with('success', 'ระงับการใช้งาน '.$validated['days'].' วัน เรียบร้อยแล้ว');
    }

    /**
     * แบนผู้ใช้ถาวร
     */
    public function banUser(Request $request, UserTrustScore $trustScore)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $trustScore->ban($validated['reason']);

        // บันทึก Log
        activity()
            ->performedOn($trustScore)
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'ban', 'reason' => $validated['reason']])
            ->log('admin_trust_score_ban');

        return redirect()
            ->route('admin.anti-abuse.trust-scores')
            ->with('success', 'แบนเรียบร้อยแล้ว');
    }

    /**
     * ปลดแบนผู้ใช้
     */
    public function unbanUser(UserTrustScore $trustScore)
    {
        $trustScore->unban();

        // บันทึก Log
        activity()
            ->performedOn($trustScore)
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'unban'])
            ->log('admin_trust_score_unban');

        return redirect()
            ->route('admin.anti-abuse.trust-scores')
            ->with('success', 'ปลดแบนเรียบร้อยแล้ว');
    }

    // ============================================
    // Penalties Management
    // ============================================

    /**
     * รายการ Penalties ทั้งหมด
     */
    public function penalties(Request $request)
    {
        $query = ServiceBookingPenalty::with(['booking', 'user', 'provider']);

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // กรองตามประเภท
        if ($request->filled('penalty_type')) {
            $query->where('penalty_type', $request->penalty_type);
        }

        // กรองตามประเภทผู้ถูกปรับ
        if ($request->filled('penalized_type')) {
            $query->where('penalized_type', $request->penalized_type);
        }

        $penalties = $query->latest()->paginate(20);

        // สรุปยอดรวม
        $summary = [
            'total_pending' => ServiceBookingPenalty::where('status', 'pending')->sum('penalty_amount'),
            'total_charged' => ServiceBookingPenalty::where('status', 'charged')->sum('penalty_amount'),
            'total_waived' => ServiceBookingPenalty::where('status', 'waived')->sum('penalty_amount'),
        ];

        return view('admin.anti-abuse.penalties.index', [
            'penalties' => $penalties,
            'penaltyTypes' => ServiceBookingPenalty::PENALTY_TYPES,
            'summary' => $summary,
            'pageTitle' => 'จัดการค่าปรับ',
        ]);
    }

    /**
     * อัพเดทสถานะ Penalty
     */
    public function updatePenaltyStatus(Request $request, ServiceBookingPenalty $penalty)
    {
        $validated = $request->validate([
            'action' => 'required|in:charge,waive',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validated['action'] === 'charge') {
            $penalty->markAsCharged('admin_manual', 'ADMIN-'.auth()->id().'-'.now()->timestamp);
            $message = 'บันทึกการหักค่าปรับเรียบร้อยแล้ว';
        } else {
            $penalty->waive($validated['notes'] ?? 'Admin ยกเว้น');
            $message = 'ยกเว้นค่าปรับเรียบร้อยแล้ว';
        }

        return redirect()
            ->route('admin.anti-abuse.penalties')
            ->with('success', $message);
    }

    /**
     * หักค่าปรับ
     */
    public function chargePenalty(ServiceBookingPenalty $penalty)
    {
        if ($penalty->status !== 'pending') {
            return redirect()
                ->route('admin.anti-abuse.penalties')
                ->with('error', 'ค่าปรับนี้ไม่สามารถหักได้ (สถานะ: '.$penalty->status.')');
        }

        $penalty->markAsCharged('admin_manual', 'ADMIN-'.auth()->id().'-'.now()->timestamp);

        // บันทึก Log
        activity()
            ->performedOn($penalty)
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'charge', 'amount' => $penalty->penalty_amount])
            ->log('admin_penalty_charge');

        return redirect()
            ->route('admin.anti-abuse.penalties')
            ->with('success', 'หักค่าปรับ ฿'.number_format($penalty->penalty_amount).' เรียบร้อยแล้ว');
    }

    /**
     * ยกเว้นค่าปรับ
     */
    public function waivePenalty(Request $request, ServiceBookingPenalty $penalty)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if ($penalty->status !== 'pending') {
            return redirect()
                ->route('admin.anti-abuse.penalties')
                ->with('error', 'ค่าปรับนี้ไม่สามารถยกเว้นได้ (สถานะ: '.$penalty->status.')');
        }

        $penalty->waive($validated['reason']);

        // บันทึก Log
        activity()
            ->performedOn($penalty)
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'waive', 'reason' => $validated['reason'], 'amount' => $penalty->penalty_amount])
            ->log('admin_penalty_waive');

        return redirect()
            ->route('admin.anti-abuse.penalties')
            ->with('success', 'ยกเว้นค่าปรับเรียบร้อยแล้ว');
    }

    // ============================================
    // Location History
    // ============================================

    /**
     * ดูประวัติตำแหน่ง GPS
     */
    public function locationHistory(Request $request)
    {
        $locationLogs = collect();

        // ค้นหาตาม Booking ID
        if ($request->filled('booking_id')) {
            $locationLogs = ServiceBookingLocationLog::where('service_booking_id', $request->booking_id)
                ->orderBy('recorded_at', 'asc')
                ->get();
        }
        // ค้นหาตาม User ID
        elseif ($request->filled('user_id')) {
            $locationLogs = ServiceBookingLocationLog::where('user_id', $request->user_id)
                ->when($request->filled('date'), function ($q) use ($request) {
                    $q->whereDate('recorded_at', $request->date);
                })
                ->orderBy('recorded_at', 'asc')
                ->limit(500)
                ->get();
        }
        // ค้นหาตาม Provider ID
        elseif ($request->filled('provider_id')) {
            $locationLogs = ServiceBookingLocationLog::where('provider_id', $request->provider_id)
                ->when($request->filled('date'), function ($q) use ($request) {
                    $q->whereDate('recorded_at', $request->date);
                })
                ->orderBy('recorded_at', 'asc')
                ->limit(500)
                ->get();
        }

        // กรองเฉพาะที่สงสัย Spoofing
        if ($request->boolean('show_suspected') && $locationLogs->isNotEmpty()) {
            $locationLogs = $locationLogs->filter(function ($log) {
                return $log->suspected_spoofing || $log->is_mock_location;
            });
        }

        return view('admin.anti-abuse.location-history', [
            'locationLogs' => $locationLogs,
            'pageTitle' => 'ประวัติตำแหน่ง GPS',
        ]);
    }

    /**
     * ดูประวัติตำแหน่งของ Booking เฉพาะ (deprecated - use locationHistory with request params)
     */
    public function locationHistoryForBooking(ServiceBooking $booking)
    {
        $booking->load(['service', 'user', 'provider']);

        $history = $this->antiAbuseService->getLocationHistory($booking);

        // ดึง logs ทั้งหมด
        $logs = ServiceBookingLocationLog::where('service_booking_id', $booking->id)
            ->orderBy('recorded_at', 'asc')
            ->get();

        return view('admin.anti-abuse.location-history', [
            'booking' => $booking,
            'history' => $history,
            'logs' => $logs,
            'pageTitle' => 'ประวัติตำแหน่ง #'.$booking->booking_number,
        ]);
    }

    // ============================================
    // User Blocks Management
    // ============================================

    /**
     * รายการ Blocks ทั้งหมด
     */
    public function blocks(Request $request)
    {
        $query = UserBlock::with(['blockerUser', 'blockerProvider', 'blockedUser', 'blockedProvider']);

        // กรองตามประเภท
        if ($request->filled('block_type')) {
            $query->where('block_type', $request->block_type);
        }

        // กรองเฉพาะที่ยังมีผล
        if ($request->boolean('active_only')) {
            $query->active();
        }

        $blocks = $query->latest()->paginate(20);

        return view('admin.anti-abuse.blocks.index', [
            'blocks' => $blocks,
            'blockTypes' => UserBlock::BLOCK_TYPES,
            'pageTitle' => 'จัดการการ Block',
        ]);
    }

    /**
     * ยกเลิก Block
     */
    public function removeBlock(UserBlock $block)
    {
        $block->delete();

        return redirect()
            ->route('admin.anti-abuse.blocks')
            ->with('success', 'ยกเลิก Block เรียบร้อยแล้ว');
    }

    /**
     * สร้าง Block ใหม่ (Admin)
     */
    public function createBlock(Request $request)
    {
        $validated = $request->validate([
            'blocked_type' => 'required|in:user,provider',
            'blocked_user_id' => 'required_if:blocked_type,user|nullable|exists:users,id',
            'blocked_provider_id' => 'required_if:blocked_type,provider|nullable|exists:service_providers,id',
            'block_type' => 'required|in:safety,system_auto,admin',
            'reason' => 'required|string|max:500',
            'expires_days' => 'nullable|integer|min:1|max:365',
        ]);

        $expiresAt = $validated['expires_days']
            ? now()->addDays($validated['expires_days'])
            : null;

        UserBlock::create([
            'blocker_type' => 'admin',
            'blocker_user_id' => auth()->id(),
            'blocked_type' => $validated['blocked_type'],
            'blocked_user_id' => $validated['blocked_user_id'] ?? null,
            'blocked_provider_id' => $validated['blocked_provider_id'] ?? null,
            'block_type' => $validated['block_type'],
            'reason' => $validated['reason'],
            'expires_at' => $expiresAt,
        ]);

        return redirect()
            ->route('admin.anti-abuse.blocks')
            ->with('success', 'สร้าง Block เรียบร้อยแล้ว');
    }
}
