<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use App\Models\FortuneUserBan;
use App\Services\FortuneBanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Admin Fortune Ban Controller
 *
 * จัดการระบบ "คุก" (Ban/Jail) — ห้ามบอทคุยกับ user ที่ไม่เหมาะสม
 *
 * Features:
 * - แสดงรายการ user ที่ถูกแบนทั้งหมด (active + expired)
 * - แบน user ใหม่ (เลือกแพลตฟอร์ม + ระยะเวลา/ถาวร + เหตุผล)
 * - ปลดแบน
 * - ดูประวัติพฤติกรรม (จำนวนครั้งที่พยายามทักหลังถูกแบน)
 */
class FortuneBanController extends Controller
{
    protected FortuneBanService $banService;

    public function __construct(FortuneBanService $banService)
    {
        $this->banService = $banService;
    }

    /**
     * แสดงรายการ user ที่ถูกแบน
     */
    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'active'); // active|expired|all
        $platform = $request->query('platform'); // facebook|line|null
        $search = trim($request->query('search', ''));

        $query = FortuneUserBan::with('bannedBy')->latest();

        // Filter by status
        if ($filter === 'active') {
            $query->active();
        } elseif ($filter === 'expired') {
            $query->expired();
        }

        // Filter by platform
        if ($platform && in_array($platform, ['facebook', 'line'], true)) {
            $query->forPlatform($platform);
        }

        // Search
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('platform_user_id', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $bans = $query->paginate(25)->withQueryString();

        // Stats
        $stats = [
            'total_active' => FortuneUserBan::active()->count(),
            'permanent' => FortuneUserBan::active()->whereNull('banned_until')->count(),
            'temporary' => FortuneUserBan::active()->whereNotNull('banned_until')->count(),
            'total_expired' => FortuneUserBan::expired()->count(),
        ];

        return view('admin.fortune.bans.index', [
            'bans' => $bans,
            'stats' => $stats,
            'filter' => $filter,
            'platform' => $platform,
            'search' => $search,
            'pageTitle' => 'ระบบคุก (จัดการ user ที่ถูกแบน)',
        ]);
    }

    /**
     * แบน user ใหม่
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'platform' => 'required|in:facebook,line',
            'platform_user_id' => 'required|string|max:100',
            'duration_type' => 'required|in:permanent,minutes,hours,days',
            'duration_value' => 'nullable|integer|min:1|max:100000',
            'reason' => 'nullable|string|max:1000',
            'display_name' => 'nullable|string|max:200',
        ]);

        // Convert duration → minutes
        $minutes = null;
        if ($validated['duration_type'] !== 'permanent') {
            $value = (int) ($validated['duration_value'] ?? 0);
            if ($value <= 0) {
                return back()
                    ->withInput()
                    ->withErrors(['duration_value' => 'กรุณาระบุระยะเวลามากกว่า 0']);
            }

            $minutes = match ($validated['duration_type']) {
                'minutes' => $value,
                'hours' => $value * 60,
                'days' => $value * 60 * 24,
                default => $value,
            };
        }

        // ดึงชื่อจาก FortuneReading ถ้าไม่ได้กรอก
        $displayName = $validated['display_name'] ?? null;
        if (! $displayName) {
            $platformColumn = $validated['platform'] === 'facebook'
                ? 'facebook_user_id'
                : 'platform_user_id';

            $reading = FortuneReading::where($platformColumn, $validated['platform_user_id'])
                ->whereNotNull('facebook_user_name')
                ->latest()
                ->first();

            if ($reading && $reading->facebook_user_name) {
                $displayName = $reading->facebook_user_name;
            }
        }

        $ban = $this->banService->ban(
            platform: $validated['platform'],
            platformUserId: trim($validated['platform_user_id']),
            minutes: $minutes,
            reason: $validated['reason'] ?? null,
            adminId: Auth::id(),
            displayName: $displayName,
        );

        Log::info('Admin: แบน user ใหม่', [
            'ban_id' => $ban->id,
            'admin_id' => Auth::id(),
            'platform' => $ban->platform,
            'permanent' => $ban->isPermanent(),
        ]);

        return redirect()
            ->route('admin.fortune.bans.index')
            ->with('success', sprintf(
                '🚫 แบน %s:%s เรียบร้อย (%s)',
                $ban->platform,
                $ban->platform_user_id,
                $ban->isPermanent() ? 'ถาวร' : $ban->remainingHumanReadable()
            ));
    }

    /**
     * ปลดแบน
     */
    public function destroy(FortuneUserBan $ban): RedirectResponse
    {
        $platform = $ban->platform;
        $userId = $ban->platform_user_id;

        $this->banService->unban($platform, $userId, Auth::id());

        return redirect()
            ->route('admin.fortune.bans.index')
            ->with('success', "✨ ปลดแบน {$platform}:{$userId} เรียบร้อย");
    }
}
