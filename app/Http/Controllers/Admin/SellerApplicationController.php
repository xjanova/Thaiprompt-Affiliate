<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\VendorPackage;
use App\Models\VendorStore;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🏪 คิวอนุมัติคำขอเปิดร้านค้า (audit SELLER-07)
 *
 * คำขอ = VendorStore status=pending (สร้างจาก /user/seller-apply)
 * อนุมัติ → ร้าน active + ผู้ใช้เป็น role seller + ใช้แพ็กเกจฟรี (ถ้ามี) เพื่อให้ผ่าน
 *          EnsureHasVendorStore ได้ทันที ผู้ขายเปลี่ยนแพ็กเกจเองภายหลังได้ที่ onboarding
 * ปฏิเสธ → ร้าน status=closed + เก็บเหตุผลไว้ใน suspension_reason (ผู้ใช้แก้แล้วยื่นใหม่ได้)
 */
class SellerApplicationController extends Controller
{
    /**
     * role ที่อนุมัติเป็น seller ได้ทันที (role อื่นจะเสียพื้นที่ทำงานเดิม ต้องจัดการเองที่หน้าผู้ใช้)
     */
    private const CONVERTIBLE_ROLES = ['user', 'affiliate', 'seller'];

    /**
     * รายการคำขอที่รออนุมัติ
     *
     * GET admin/seller-applications (route: admin.seller-applications.index)
     */
    public function index(Request $request)
    {
        $applications = VendorStore::with(['user' => fn ($q) => $q->withTrashed()])
            ->where('status', 'pending')
            ->orderBy('updated_at')
            ->paginate(20)
            ->withQueryString();

        $recent = VendorStore::with(['user' => fn ($q) => $q->withTrashed()])
            ->whereIn('status', ['active', 'closed'])
            ->where('updated_at', '>=', now()->subDays(30))
            ->latest('updated_at')
            ->limit(10)
            ->get();

        $stats = [
            'pending' => VendorStore::where('status', 'pending')->count(),
            'active' => VendorStore::where('status', 'active')->count(),
        ];

        return view('admin.seller-applications.index', compact('applications', 'recent', 'stats'));
    }

    /**
     * อนุมัติคำขอเปิดร้าน
     *
     * POST admin/seller-applications/{store}/approve (route: admin.seller-applications.approve)
     */
    public function approve(VendorStore $store)
    {
        try {
            $result = DB::transaction(function () use ($store) {
                /** @var VendorStore|null $locked */
                $locked = VendorStore::whereKey($store->id)->lockForUpdate()->first();
                if (! $locked || $locked->status !== 'pending') {
                    return ['ok' => false, 'message' => 'คำขอนี้ถูกดำเนินการไปแล้ว'];
                }

                /** @var User|null $owner */
                $owner = User::whereKey($locked->user_id)->lockForUpdate()->first();
                if (! $owner) {
                    return ['ok' => false, 'message' => 'ไม่พบบัญชีเจ้าของร้าน (อาจถูกลบไปแล้ว)'];
                }
                if ($owner->isSuspended()) {
                    return ['ok' => false, 'message' => 'บัญชีเจ้าของร้านถูกระงับอยู่ กรุณายกเลิกการระงับก่อน'];
                }

                $isAdmin = $owner->is_super_admin || in_array($owner->role, ['admin', 'super_admin'], true);
                if (! $isAdmin && ! in_array($owner->role, self::CONVERTIBLE_ROLES, true)) {
                    return ['ok' => false, 'message' => 'ผู้ใช้มีบทบาท "'.$owner->role.'" อยู่ การเปลี่ยนเป็นผู้ขายต้องทำที่หน้าจัดการผู้ใช้'];
                }

                $freePackage = VendorPackage::where('package_slug', 'free')->where('is_active', true)->first();

                $locked->update([
                    'status' => 'active',
                    'is_active' => true,
                    'suspension_reason' => null,
                    'package_id' => $locked->package_id ?? $freePackage?->id,
                    'commission_rate' => $freePackage?->commission_rate ?? $locked->commission_rate,
                    'subscription_status' => 'active',
                    'subscription_started_at' => $locked->subscription_started_at ?? now(),
                ]);

                // role อยู่ใน $guarded → ตั้งผ่าน forceFill (แอดมินคงบทบาทเดิม)
                if (! $isAdmin && $owner->role !== 'seller') {
                    $sellerRoleId = Role::where('name', 'seller')->value('id');
                    $owner->forceFill([
                        'role' => 'seller',
                        'role_id' => $sellerRoleId,
                    ])->save();
                }

                return ['ok' => true, 'store' => $locked->fresh(), 'owner' => $owner];
            });
        } catch (\Throwable $e) {
            Log::error('Approve seller application failed', ['store_id' => $store->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'อนุมัติไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        if (! $result['ok']) {
            return back()->with('error', $result['message']);
        }

        $this->notifyOwner(
            $result['owner'],
            'อนุมัติเปิดร้านค้าแล้ว 🎉',
            'ร้าน "'.$result['store']->store_name.'" ได้รับการอนุมัติ เข้าหลังร้านเพื่อยืนยันตัวตนและเริ่มลงสินค้าได้เลย',
            ['store_id' => $result['store']->id, 'status' => 'approved'],
            route('seller.onboarding.index'),
            'ไปหลังร้าน'
        );

        Log::info('Seller application approved', ['store_id' => $result['store']->id, 'admin_id' => auth()->id()]);

        return back()->with('success', 'อนุมัติร้าน "'.$result['store']->store_name.'" เรียบร้อย');
    }

    /**
     * ปฏิเสธคำขอเปิดร้าน
     *
     * POST admin/seller-applications/{store}/reject  body: reason (บังคับ)
     */
    public function reject(Request $request, VendorStore $store)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'กรุณาระบุเหตุผลที่ปฏิเสธ (ผู้สมัครจะเห็นข้อความนี้)',
            'reason.max' => 'เหตุผลต้องไม่เกิน 500 ตัวอักษร',
        ]);

        $updated = VendorStore::whereKey($store->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'closed',
                'is_active' => false,
                'suspension_reason' => trim($validated['reason']),
                'updated_at' => now(),
            ]);

        if (! $updated) {
            return back()->with('error', 'คำขอนี้ถูกดำเนินการไปแล้ว');
        }

        $owner = User::find($store->user_id);
        if ($owner) {
            $this->notifyOwner(
                $owner,
                'คำขอเปิดร้านยังไม่ผ่านการอนุมัติ',
                'เหตุผล: '.trim($validated['reason']).' — แก้ไขข้อมูลแล้วยื่นใหม่ได้',
                ['store_id' => $store->id, 'status' => 'rejected'],
                route('user.seller-apply.index'),
                'แก้ไขและยื่นใหม่'
            );
        }

        Log::info('Seller application rejected', ['store_id' => $store->id, 'admin_id' => auth()->id()]);

        return back()->with('success', 'ปฏิเสธคำขอของร้าน "'.$store->store_name.'" แล้ว');
    }

    /**
     * แจ้งผลให้ผู้สมัคร (in-app + push ผ่าน NotificationService) — ล้มไม่เป็นไร
     */
    private function notifyOwner(User $owner, string $title, string $message, array $data, string $actionUrl, string $actionText): void
    {
        try {
            app(NotificationService::class)->create(
                $owner,
                'seller_application',
                $title,
                $message,
                $data,
                $actionUrl,
                $actionText,
                'high',
                true
            );
        } catch (\Throwable $e) {
            Log::warning('Seller application: notify owner failed', ['user_id' => $owner->id, 'error' => $e->getMessage()]);
        }
    }
}
