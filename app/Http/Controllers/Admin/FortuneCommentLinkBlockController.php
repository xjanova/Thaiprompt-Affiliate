<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneCommentLinkBlock;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\Fortune\CommentBlockAdminNotifier;
use App\Services\FortuneBanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * จัดการเหตุการณ์ "คอมเมนต์แปะลิงก์ → บอทบล็อกคนโพสต์"
 *
 * บอทซ่อนคอมเมนต์เองไม่ได้ (Page token ยังไม่มี pages_manage_engagement — ติด App Review)
 * หน้านี้จึงทำหน้าที่:
 * - รวมลิงก์ตำแหน่งคอมเมนต์ให้แอดมินกดไปลบเอง
 * - ปลดบล็อกย้อนหลังได้ (นโยบายคือบล็อกทันทีไม่เว้นใคร รวมคนที่จ่ายเงิน — ต้องมีทางแก้เมื่อพลาด)
 * - ทำเครื่องหมายว่าจัดการแล้ว เพื่อให้ badge บนเมนูนิ่ง
 */
class FortuneCommentLinkBlockController extends Controller
{
    public function __construct(
        protected FortuneBanService $banService,
    ) {}

    /**
     * แสดงรายการเหตุการณ์ทั้งหมด
     */
    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'all'); // all|unread|blocked|unblocked|need_delete
        $search = trim((string) $request->query('search', ''));

        $query = FortuneCommentLinkBlock::with('unblockedBy')->latest();

        // กรองตามสถานะการจัดการ
        if ($filter === 'unread') {
            $query->unread();
        } elseif ($filter === 'blocked') {
            $query->stillBlocked();
        } elseif ($filter === 'unblocked') {
            $query->where('status', 'unblocked');
        } elseif ($filter === 'detect_only') {
            // เจอจากการสแกนย้อนหลัง แต่ยังไม่ได้บล็อกใคร
            $query->where('status', 'detect_only');
        } elseif ($filter === 'link') {
            $query->where('violation_type', 'link');
        } elseif ($filter === 'flood') {
            $query->where('violation_type', 'flood');
        } elseif ($filter === 'need_delete') {
            // ยังไม่ได้ลบคอมเมนต์ และบอทก็ซ่อนไม่สำเร็จ → ต้องให้คนไปลบ
            $query->where('comment_deleted', false)->where('hide_succeeded', false);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'like', "%{$search}%")
                    ->orWhere('platform_user_id', 'like', "%{$search}%")
                    ->orWhere('matched_domain', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $blocks = $query->paginate(25)->withQueryString();

        $stats = [
            'total' => FortuneCommentLinkBlock::count(),
            'unread' => FortuneCommentLinkBlock::unread()->count(),
            'still_blocked' => FortuneCommentLinkBlock::stillBlocked()->count(),
            'detect_only' => FortuneCommentLinkBlock::where('status', 'detect_only')->count(),
            'flood' => FortuneCommentLinkBlock::where('violation_type', 'flood')->count(),
            'block_failed' => FortuneCommentLinkBlock::where('page_blocked', false)->count(),
            'need_delete' => FortuneCommentLinkBlock::where('comment_deleted', false)
                ->where('hide_succeeded', false)
                ->count(),
            'today' => FortuneCommentLinkBlock::whereDate('created_at', today())->count(),
        ];

        $settings = FortuneTellingSetting::getSettings();

        return view('admin.fortune.comment-link-blocks.index', [
            'blocks' => $blocks,
            'stats' => $stats,
            'filter' => $filter,
            'search' => $search,
            'notifyPsid' => $settings->admin_notify_psid ?? null,
            'notifyEnabled' => (bool) ($settings->admin_notify_enabled ?? true),
            'psidCandidates' => $this->findAdminPsidCandidates(),
            'pageTitle' => 'คอมเมนต์แปะลิงก์ (บล็อกอัตโนมัติ)',
        ]);
    }

    /**
     * 🔎 หา PSID ที่น่าจะเป็นแอดมิน จากประวัติดูดวงของบัญชีแอดมินเอง
     *
     * Facebook ไม่เคยบอกว่า "ใคร" ตอบในนามเพจ (echo คืน Page ID เสมอ)
     * แต่ถ้าแอดมินเคยทักเพจในฐานะลูกค้า จะมี PSID ติดอยู่ในประวัติดูดวง
     * → ดึงมาเป็นตัวเลือกให้กดเลือก ไม่ต้องไปหา PSID เอง
     *
     * @return array<int, array{psid: string, name: string, last_seen: string|null}>
     */
    protected function findAdminPsidCandidates(): array
    {
        // ชื่อของแอดมินในระบบ → ใช้เป็นคำค้นในประวัติดูดวงฝั่ง Facebook
        $names = \App\Models\User::whereIn('role', ['admin', 'super_admin'])
            ->pluck('name')
            ->filter()
            ->all();

        if (empty($names)) {
            return [];
        }

        $rows = FortuneReading::query()
            ->where('platform', 'facebook')
            ->whereNotNull('facebook_user_id')
            ->whereNotNull('facebook_user_name')
            ->where(function ($q) use ($names) {
                foreach ($names as $n) {
                    // ใช้คำแรกของชื่อ กันกรณีชื่อในระบบกับบน FB ไม่ตรงกันเป๊ะ
                    $first = trim(explode(' ', trim($n))[0] ?? '');
                    if (mb_strlen($first) >= 3) {
                        $q->orWhere('facebook_user_name', 'like', '%'.$first.'%');
                    }
                }
            })
            ->selectRaw('facebook_user_id, MAX(facebook_user_name) as nm, MAX(created_at) as last_at')
            ->groupBy('facebook_user_id')
            ->orderByDesc('last_at')
            ->limit(5)
            ->get();

        return $rows->map(fn ($r) => [
            'psid' => (string) $r->facebook_user_id,
            'name' => (string) $r->nm,
            'last_seen' => $r->last_at ? (string) $r->last_at : null,
        ])->all();
    }

    /**
     * บันทึก PSID แอดมินที่จะรับแจ้งเตือน
     */
    public function saveNotify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notify_psid' => 'nullable|string|max:100',
            'admin_notify_enabled' => 'nullable|boolean',
        ]);

        $settings = FortuneTellingSetting::getSettings();
        $settings->admin_notify_psid = $validated['admin_notify_psid'] ?: null;
        $settings->admin_notify_enabled = (bool) ($validated['admin_notify_enabled'] ?? false);
        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        return back()->with('success', '💾 บันทึกการแจ้งเตือนเรียบร้อย');
    }

    /**
     * ออกรหัสผูก Messenger (ใช้เมื่อไม่รู้ PSID — แอดมินทักเพจพร้อมรหัสนี้)
     */
    public function bindCode(CommentBlockAdminNotifier $notifier): RedirectResponse
    {
        $code = $notifier->generateBindCode();

        return back()->with('bind_code', $code);
    }

    /**
     * ทดสอบส่งแจ้งเตือนทันที — ใช้เช็คว่ากรอบ 24 ชม. ยังเปิดอยู่ไหม
     */
    public function testNotify(): RedirectResponse
    {
        $settings = FortuneTellingSetting::getSettings();
        $psid = $settings->admin_notify_psid ?? null;

        if (empty($psid)) {
            return back()->with('warning', '⚠️ ยังไม่ได้ตั้ง PSID แอดมิน');
        }

        $ok = app(FacebookWebhookService::class)->sendMessage(
            $psid,
            "🔔 ทดสอบการแจ้งเตือน\n\nถ้าเห็นข้อความนี้ แปลว่าระบบแจ้งเตือนคอมเมนต์แปะลิงก์พร้อมใช้งานแล้ว\n\nพิมพ์ \"สแปม\" เพื่อดูรายการล่าสุดได้ทุกเมื่อ"
        );

        return back()->with(
            $ok ? 'success' : 'warning',
            $ok
                ? '✅ ส่งข้อความทดสอบแล้ว — เช็คใน Messenger'
                : '⚠️ ส่งไม่สำเร็จ — มักเพราะเลยกรอบ 24 ชม. ให้แอดมินทักเพจจากบัญชีส่วนตัวสัก 1 ข้อความก่อน แล้วลองใหม่'
        );
    }

    /**
     * ปลดบล็อก — ทั้งบนเพจ Facebook และระดับบอท
     *
     * ใช้เมื่อบล็อกพลาดคนจริง (นโยบายบล็อกทันทีไม่เว้นใคร ย่อมมีพลาดได้)
     */
    public function unblock(FortuneCommentLinkBlock $block): RedirectResponse
    {
        $fbService = app(FacebookWebhookService::class);

        $unblocked = false;
        $error = null;

        try {
            $unblocked = $fbService->unblockPageUser($block->platform_user_id);
            if (! $unblocked) {
                $error = $fbService->lastFetchError ?? 'unknown';
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        // ปลดแบนระดับบอทด้วย ไม่งั้นบอทยังเงียบใส่เขาอยู่
        try {
            $this->banService->unban($block->platform, $block->platform_user_id, Auth::id());
        } catch (\Throwable $e) {
            Log::warning('unblock: ปลดแบนระดับบอทล้ม: '.$e->getMessage());
        }

        $block->update([
            'status' => 'unblocked',
            'is_read' => true,
            'unblocked_at' => now(),
            'unblocked_by' => Auth::id(),
            'block_error' => $error ? mb_substr($error, 0, 500) : $block->block_error,
        ]);

        Log::info('Admin: ปลดบล็อกคนโพสต์ลิงก์ในคอมเมนต์', [
            'block_id' => $block->id,
            'admin_id' => Auth::id(),
            'psid' => $block->platform_user_id,
            'fb_unblocked' => $unblocked,
        ]);

        return back()->with(
            $unblocked ? 'success' : 'warning',
            $unblocked
                ? '✨ ปลดบล็อก '.($block->display_name ?: $block->platform_user_id).' เรียบร้อย (ทั้งบนเพจและบอท)'
                : '⚠️ ปลดแบนบอทแล้ว แต่ปลดบล็อกบนเพจไม่สำเร็จ: '.$error
        );
    }

    /**
     * ทำเครื่องหมายว่าแอดมินลบคอมเมนต์นั้นแล้ว
     */
    public function markDeleted(FortuneCommentLinkBlock $block): RedirectResponse
    {
        $block->update([
            'comment_deleted' => true,
            'is_read' => true,
        ]);

        return back()->with('success', '🗑️ บันทึกแล้วว่าลบคอมเมนต์นี้เรียบร้อย');
    }

    /**
     * ทำเครื่องหมายว่าอ่าน/รับทราบแล้ว
     */
    public function markRead(FortuneCommentLinkBlock $block): RedirectResponse
    {
        $block->update(['is_read' => true]);

        return back()->with('success', '✅ รับทราบแล้ว');
    }

    /**
     * ทำเครื่องหมายอ่านทั้งหมด (เคลียร์ badge)
     */
    public function markAllRead(): RedirectResponse
    {
        $count = FortuneCommentLinkBlock::unread()->update(['is_read' => true]);

        return back()->with('success', "✅ รับทราบทั้งหมด {$count} รายการ");
    }
}
