<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
use App\Services\FortuneChannelManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fortune Users Controller
 *
 * จัดการผู้ใช้ที่เคยดูดวง + ส่งข้อความไปหาผู้ใช้เก่า
 */
class FortuneUsersController extends Controller
{
    /**
     * แสดงรายการผู้ใช้ที่เคยดูดวง (unique users)
     */
    public function index(Request $request)
    {
        // ดึง unique users จากตาราง fortune_readings
        $query = FortuneReading::query()
            ->select(
                'facebook_user_id',
                'platform',
                DB::raw('MAX(facebook_user_name) as facebook_user_name'),
                DB::raw('COUNT(*) as total_readings'),
                DB::raw('SUM(CASE WHEN reading_type = "deep" THEN 1 ELSE 0 END) as deep_readings'),
                DB::raw('SUM(CASE WHEN is_paid = 1 THEN 1 ELSE 0 END) as paid_readings'),
                DB::raw('SUM(amount_paid) as total_spent'),
                DB::raw('MAX(created_at) as last_reading_at'),
                DB::raw('MIN(created_at) as first_reading_at'),
            )
            ->groupBy('facebook_user_id', 'platform');

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('facebook_user_id', 'like', "%{$search}%")
                    ->orWhere('facebook_user_name', 'like', "%{$search}%");
            });
        }

        // กรองตาม platform
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        // กรองเฉพาะลูกค้าจ่ายเงิน
        if ($request->boolean('paid_only')) {
            $query->having('paid_readings', '>', 0);
        }

        // เรียงลำดับ
        $sortBy = $request->input('sort', 'last_reading_at');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['last_reading_at', 'total_readings', 'total_spent', 'first_reading_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $users = $query->paginate(20)->withQueryString();

        // สถิติรวม
        $stats = [
            'total_users' => FortuneReading::select('facebook_user_id')->distinct()->count(),
            'facebook_users' => FortuneReading::where('platform', 'facebook')->select('facebook_user_id')->distinct()->count(),
            'line_users' => FortuneReading::where('platform', 'line')->select('facebook_user_id')->distinct()->count(),
            'paying_users' => FortuneReading::where('is_paid', true)->select('facebook_user_id')->distinct()->count(),
            'total_revenue' => FortuneReading::where('is_paid', true)->sum('amount_paid'),
        ];

        return view('admin.fortune.users.index', [
            'users' => $users,
            'stats' => $stats,
            'pageTitle' => 'ผู้ใช้ดูดวง',
        ]);
    }

    /**
     * แสดงรายละเอียดผู้ใช้คนเดียว + ประวัติทั้งหมด
     */
    public function show(Request $request, string $platform, string $userId)
    {
        $readings = FortuneReading::where('facebook_user_id', $userId)
            ->where('platform', $platform)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        if ($readings->isEmpty()) {
            abort(404, 'ไม่พบผู้ใช้');
        }

        // ข้อมูลผู้ใช้จาก reading ล่าสุด
        $latestReading = $readings->first();
        $userInfo = [
            'facebook_user_id' => $userId,
            'facebook_user_name' => $latestReading->facebook_user_name ?? '-',
            'platform' => $platform,
            'total_readings' => FortuneReading::where('facebook_user_id', $userId)->where('platform', $platform)->count(),
            'deep_readings' => FortuneReading::where('facebook_user_id', $userId)->where('platform', $platform)->where('reading_type', 'deep')->count(),
            'total_spent' => FortuneReading::where('facebook_user_id', $userId)->where('platform', $platform)->sum('amount_paid'),
            'first_reading' => FortuneReading::where('facebook_user_id', $userId)->where('platform', $platform)->min('created_at'),
            'last_reading' => FortuneReading::where('facebook_user_id', $userId)->where('platform', $platform)->max('created_at'),
        ];

        // ดึง credit ถ้ามี
        $credit = FortuneUserCredit::findByUser($userId);

        // 🔒 (2026-05-04) Pay-later eligibility — ตรวจว่า user คนนี้ใช้สิทธิ์ "ดูก่อนจ่ายทีหลัง" ไปแล้วหรือยัง
        $payLaterReadings = FortuneReading::where(function ($q) use ($userId) {
            $q->where('facebook_user_id', $userId)
                ->orWhere('platform_user_id', $userId);
        })
            ->where('platform', $platform)
            ->where('reading_type', FortuneReading::READING_TYPE_DEEP)
            ->whereJsonContains('conversation_state->is_request_before_pay', true)
            ->orderByDesc('created_at')
            ->get(['id', 'bill_reference', 'is_paid', 'amount_paid', 'conversation_status', 'created_at', 'paid_at']);

        $payLaterStatus = [
            'has_used' => $payLaterReadings->isNotEmpty(),
            'eligible' => $payLaterReadings->isEmpty(),
            'usage_count' => $payLaterReadings->count(),
            'paid_count' => $payLaterReadings->where('is_paid', true)->count(),
            'unpaid_count' => $payLaterReadings->where('is_paid', false)->count(),
            'first_used_at' => $payLaterReadings->last()?->created_at,
            'last_used_at' => $payLaterReadings->first()?->created_at,
            'readings' => $payLaterReadings,
        ];

        return view('admin.fortune.users.show', [
            'readings' => $readings,
            'userInfo' => $userInfo,
            'credit' => $credit,
            'payLaterStatus' => $payLaterStatus,
            'pageTitle' => "ผู้ใช้: {$userInfo['facebook_user_name']}",
        ]);
    }

    /**
     * 🔒 (2026-05-04) Reset pay-later eligibility — ให้ลูกค้าใช้สิทธิ์ "ดูก่อนจ่ายทีหลัง" ได้อีก
     *
     * เคลียร์ flag is_request_before_pay + pay_later_acked จากทุก reading ของ user คนนี้
     * ใช้กรณี: ลูกค้าจ่ายแล้วครบแต่อยากใช้สิทธิ์ใหม่ / admin จัดโปรโมชัน
     */
    public function resetPayLaterEligibility(Request $request, string $platform, string $userId)
    {
        try {
            $count = FortuneReading::where(function ($q) use ($userId) {
                $q->where('facebook_user_id', $userId)
                    ->orWhere('platform_user_id', $userId);
            })
                ->where('platform', $platform)
                ->where('reading_type', FortuneReading::READING_TYPE_DEEP)
                ->whereJsonContains('conversation_state->is_request_before_pay', true)
                ->get()
                ->each(function (FortuneReading $reading) {
                    $state = $reading->conversation_state ?? [];
                    unset($state['is_request_before_pay']);
                    unset($state['pay_later_acked']);
                    unset($state['awaiting_pay_later_ack']);
                    unset($state['pending_pay_later_questions']);
                    $reading->update(['conversation_state' => $state]);
                })
                ->count();

            Log::info('Admin: รีเซ็ตสิทธิ์ pay-later', [
                'admin_id' => auth()->id(),
                'platform' => $platform,
                'user_id' => $userId,
                'readings_cleared' => $count,
            ]);

            return back()->with('success', "✅ รีเซ็ตสิทธิ์ดูก่อนจ่ายทีหลังสำเร็จ — เคลียร์ flag จาก {$count} readings");
        } catch (\Throwable $e) {
            Log::error('Admin: reset pay-later eligibility ล้มเหลว', [
                'platform' => $platform,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'รีเซ็ตล้มเหลว: '.$e->getMessage());
        }
    }

    /**
     * ส่งข้อความไปหาผู้ใช้ (Facebook Messenger / LINE)
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|in:facebook,line',
            'facebook_user_id' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        try {
            $settings = FortuneTellingSetting::getSettings();
            $channelManager = new FortuneChannelManager($settings);
            $platformService = $channelManager->getPlatform($validated['platform']);

            if (! $platformService) {
                return back()->with('error', "ไม่สามารถเชื่อมต่อ {$validated['platform']} ได้ กรุณาตั้งค่าช่องทางก่อน");
            }

            $success = $platformService->sendMessage(
                $validated['facebook_user_id'],
                $validated['message'],
                ['from_admin' => true]
            );

            if ($success) {
                Log::info('Admin sent fortune message', [
                    'admin_id' => auth()->id(),
                    'platform' => $validated['platform'],
                    'user_id' => $validated['facebook_user_id'],
                    'message_length' => mb_strlen($validated['message']),
                ]);

                return back()->with('success', 'ส่งข้อความสำเร็จ!');
            }

            return back()->with('error', 'ส่งข้อความไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');

        } catch (\Exception $e) {
            Log::error('Admin send fortune message failed', [
                'error' => $e->getMessage(),
                'platform' => $validated['platform'],
                'user_id' => $validated['facebook_user_id'],
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * ส่งข้อความถึงผู้ใช้หลายคน (broadcast)
     */
    public function broadcastMessage(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|in:facebook,line,all',
            'message' => 'required|string|max:2000',
            'target' => 'required|in:all,paid,recent',
        ]);

        try {
            $settings = FortuneTellingSetting::getSettings();
            $channelManager = new FortuneChannelManager($settings);

            // ดึงรายชื่อผู้ใช้ที่จะส่ง
            $query = FortuneReading::query()
                ->select('facebook_user_id', 'platform')
                ->whereNotNull('platform')
                ->groupBy('facebook_user_id', 'platform');

            if ($validated['platform'] !== 'all') {
                $query->where('platform', $validated['platform']);
            }

            if ($validated['target'] === 'paid') {
                $query->having(DB::raw('SUM(CASE WHEN is_paid = 1 THEN 1 ELSE 0 END)'), '>', 0);
            } elseif ($validated['target'] === 'recent') {
                $query->having(DB::raw('MAX(created_at)'), '>=', now()->subDays(7));
            }

            $recipients = $query->get();

            $sent = 0;
            $failed = 0;

            foreach ($recipients as $recipient) {
                try {
                    // ข้ามผู้ใช้ที่ไม่มีข้อมูล platform
                    if (empty($recipient->platform)) {
                        $failed++;
                        continue;
                    }
                    $platformService = $channelManager->getPlatform($recipient->platform);
                    if ($platformService) {
                        $success = $platformService->sendMessage(
                            $recipient->facebook_user_id,
                            $validated['message'],
                            ['from_admin' => true]
                        );
                        $success ? $sent++ : $failed++;
                    } else {
                        $failed++;
                    }
                    // หน่วงเวลาเพื่อไม่ให้โดน rate limit
                    usleep(300000); // 0.3 วินาที
                } catch (\Exception $e) {
                    $failed++;
                    Log::warning('Broadcast message failed for user', [
                        'user_id' => $recipient->facebook_user_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Admin broadcast fortune message', [
                'admin_id' => auth()->id(),
                'platform' => $validated['platform'],
                'target' => $validated['target'],
                'sent' => $sent,
                'failed' => $failed,
            ]);

            return back()->with('success', "ส่งข้อความสำเร็จ {$sent} คน (ล้มเหลว {$failed} คน)");

        } catch (\Exception $e) {
            Log::error('Admin broadcast message failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * เพิ่มเครดิตฟรีให้ผู้ใช้ (quick action จากหน้า users)
     */
    public function quickAddCredits(Request $request)
    {
        $validated = $request->validate([
            'facebook_user_id' => 'required|string|max:255',
            'platform' => 'required|string|max:50',
            'facebook_user_name' => 'nullable|string|max:255',
            'amount' => 'required|integer|min:1|max:999',
        ]);

        $credit = FortuneUserCredit::getOrCreate(
            $validated['facebook_user_id'],
            $validated['platform'],
            $validated['facebook_user_name'] ?? null
        );

        $credit->addCredits(
            $validated['amount'],
            auth()->id(),
            "เพิ่มเครดิต {$validated['amount']} ครั้งจากหน้าผู้ใช้ดูดวง"
        );

        return back()->with('success', "เพิ่มเครดิต {$validated['amount']} ครั้ง สำเร็จ");
    }
}
