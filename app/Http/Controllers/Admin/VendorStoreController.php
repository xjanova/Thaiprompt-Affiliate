<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorPackage;
use App\Models\VendorStore;
use Illuminate\Http\Request;

/**
 * VendorStoreController - จัดการร้านค้าทั้งหมดในระบบ (Admin)
 *
 * Controller นี้ให้ Admin สามารถดูและจัดการร้านค้าของ Vendor ทั้งหมด
 * รวมถึงการอนุมัติ, ปิด/เปิดใช้งาน, และตั้งค่าร้านค้าแนะนำ
 */
class VendorStoreController extends Controller
{
    /**
     * แสดงรายการร้านค้าทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ⚡ เดิม with('products') โหลดสินค้าทุกชิ้นของทุกร้านมาแค่นับ → ใช้ withCount อย่างเดียว
        $query = VendorStore::with(['user', 'package'])
            ->withCount(['products', 'orders']);

        // ค้นหาด้วยชื่อร้าน
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('store_name', 'like', "%{$search}%")
                    ->orWhere('store_slug', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // กรองตามสถานะ
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'featured':
                    $query->where('is_featured_home', true);
                    break;
                case 'verified':
                    $query->where('is_verified', true);
                    break;
                case 'pending':
                    $query->where('status', 'pending');
                    break;
                case 'suspended':
                    // ถูกระงับ (เปิดคืนได้) แยกจาก "ปิดถาวร" (ใบสมัครถูกปฏิเสธ / เจ้าของลบบัญชีตาม PDPA — เปิดคืนจากหน้านี้ไม่ได้)
                    $query->where('status', 'suspended');
                    break;
                case 'closed':
                    $query->where('status', 'closed');
                    break;
                case 'rider':
                    $query->where('rider_delivery_enabled', true);
                    break;
            }
        }

        // เรียงลำดับ (whitelist — ค่าแปลกเคยทำให้หน้า 500)
        $allowedSort = ['created_at', 'store_name', 'total_revenue', 'rating_average', 'products_count', 'orders_count'];
        $sortBy = in_array($request->get('sort_by'), $allowedSort, true) ? $request->get('sort_by') : 'created_at';
        $sortDir = strtolower((string) $request->get('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $stores = $query->paginate(20)->withQueryString();

        // สถิติ
        $stats = [
            'total' => VendorStore::count(),
            'active' => VendorStore::where('is_active', true)->count(),
            'featured' => VendorStore::where('is_featured_home', true)->count(),
            'verified' => VendorStore::where('is_verified', true)->count(),
            'pending' => VendorStore::where('status', 'pending')->count(),
            'suspended' => VendorStore::where('status', 'suspended')->count(),
            'closed' => VendorStore::where('status', 'closed')->count(),
        ];

        return view('admin.storefront.vendor-stores.index', compact('stores', 'stats'));
    }

    /**
     * แสดงรายละเอียดร้านค้า
     *
     * @return \Illuminate\View\View
     */
    public function show(VendorStore $store)
    {
        $store->load(['user', 'package', 'products' => function ($query) {
            $query->with('images')->latest()->limit(10);
        }]);

        // สถิติร้านค้า
        $stats = [
            'products_count' => $store->products()->count(),
            'orders_count' => $store->orders()->count(),
            'total_revenue' => $store->orders()
                ->where('payment_status', 'paid')
                ->sum('total_amount'),
            'avg_rating' => $store->reviews()->avg('rating') ?? 0,
            'reviews_count' => $store->reviews()->count(),
        ];

        // ออเดอร์ล่าสุดของร้าน
        $recentOrders = $store->orders()->with('user:id,name,email')->latest()->limit(10)->get();

        // 💰 รายได้ของเจ้าของร้านจากการขาย (EarningsLedger) — GP ที่แพลตฟอร์มหัก / รอโอน / โอนแล้ว
        $earnings = ['gross' => 0.0, 'gp' => 0.0, 'vat' => 0.0, 'pool' => 0.0, 'pending' => 0.0, 'paid' => 0.0];
        try {
            $rows = \App\Models\EarningsLedger::where('user_id', $store->user_id)
                ->where('earning_type', \App\Models\EarningsLedger::TYPE_SELLER_SALE)
                ->get(['gross_amount', 'platform_fee', 'vat_amount', 'mlm_commission', 'net_amount', 'status']);
            $earnings = [
                'gross' => round((float) $rows->sum('gross_amount'), 2),
                'gp' => round((float) $rows->sum('platform_fee'), 2),
                'vat' => round((float) $rows->sum('vat_amount'), 2),
                'pool' => round((float) $rows->sum('mlm_commission'), 2),
                'pending' => round((float) $rows->whereIn('status', ['pending', 'available', 'processing', 'held'])->sum('net_amount'), 2),
                'paid' => round((float) $rows->where('status', 'paid')->sum('net_amount'), 2),
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Admin vendor store earnings failed', ['store_id' => $store->id, 'error' => $e->getMessage()]);
        }

        return view('admin.storefront.vendor-stores.show', compact('store', 'stats', 'recentOrders', 'earnings'));
    }

    /**
     * แสดงฟอร์มแก้ไขร้านค้า
     *
     * @return \Illuminate\View\View
     */
    public function edit(VendorStore $store)
    {
        $store->loadMissing(['user', 'package']);
        $packages = VendorPackage::orderBy('sort_order')->orderBy('price')->get();

        return view('admin.storefront.vendor-stores.edit', compact('store', 'packages'));
    }

    /**
     * อัพเดทข้อมูลร้านค้า
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, VendorStore $store)
    {
        // สีร้าน: vendor_stores.primary_color/secondary_color เป็น varchar(7) NOT NULL
        // → ส่งมาแล้วต้องไม่ว่าง และเป็น #rgb หรือ #rrggbb เท่านั้น (ไม่ส่งช่องมาเลย = ไม่เปลี่ยน)
        $colorRule = ['sometimes', 'required', 'string', 'max:7', 'regex:/^#?([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/'];

        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'store_description' => 'nullable|string|max:1000',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'is_featured_home' => 'boolean',
            'primary_color' => $colorRule,
            'secondary_color' => $colorRule,
            // 🏷️ แพ็กเกจกำหนดอัตรา GP ของร้าน · ร้านจด VAT → ระบบหัก VAT 7/107 ตอนแบ่งเงิน
            'package_id' => 'nullable|integer|exists:vendor_packages,id',
            'vat_registered' => 'boolean',
            'tax_id' => 'nullable|string|max:50',
            'rider_delivery_enabled' => 'boolean',
        ], [
            'store_name.required' => 'กรุณากรอกชื่อร้าน',
            'commission_rate.max' => 'อัตรา GP ต้องไม่เกิน 100%',
            'commission_rate.numeric' => 'อัตรา GP ต้องเป็นตัวเลข',
            'package_id.exists' => 'ไม่พบแพ็กเกจที่เลือก',
            'primary_color.required' => 'กรุณาระบุสีหลัก (เช่น #f97316)',
            'secondary_color.required' => 'กรุณาระบุสีรอง (เช่น #ec4899)',
            'primary_color.max' => 'สีหลักต้องเป็นรหัสสี 6 หลัก เช่น #f97316',
            'secondary_color.max' => 'สีรองต้องเป็นรหัสสี 6 หลัก เช่น #ec4899',
            'primary_color.regex' => 'รูปแบบสีหลักไม่ถูกต้อง (เช่น #f97316)',
            'secondary_color.regex' => 'รูปแบบสีรองไม่ถูกต้อง (เช่น #ec4899)',
            'tax_id.max' => 'เลขประจำตัวผู้เสียภาษียาวเกินไป',
        ]);

        // สี: เติม # และขยาย #rgb → #rrggbb ให้เป็นสี CSS ที่ใช้ได้เสมอ
        foreach (['primary_color', 'secondary_color'] as $colorField) {
            if (isset($validated[$colorField])) {
                $validated[$colorField] = self::normalizeHexColor($validated[$colorField]);
            }
        }

        // commission_rate เป็น NOT NULL — เว้นว่าง = ไม่เปลี่ยน (เดิมส่ง null แล้ว 500)
        if (array_key_exists('commission_rate', $validated) && $validated['commission_rate'] === null) {
            unset($validated['commission_rate']);
        }

        // เปิดขายจากฟอร์มนี้ไม่ได้ถ้าร้านถูกระงับ/ปิดถาวร/รออนุมัติ — ต้องผ่านปุ่มเปิดร้านคืนหรือขั้นอนุมัติ
        if (! empty($validated['is_active']) && ! $store->is_active && ($blockedMessage = $this->activationBlockedMessage($store))) {
            return back()->withInput()->withErrors(['is_active' => $blockedMessage]);
        }

        // เปิดไรเดอร์ได้เฉพาะร้านที่ตั้งพิกัดจุดรับของแล้ว (ร้านตั้งเองที่หน้าตั้งค่าร้าน)
        if (! empty($validated['rider_delivery_enabled']) && ! $store->hasPickupLocation()) {
            return back()->withInput()->withErrors([
                'rider_delivery_enabled' => 'ร้านนี้ยังไม่ได้ตั้งพิกัดจุดรับของ ให้ร้านตั้งค่าที่หน้าตั้งค่าร้านก่อนเปิดส่งด้วยไรเดอร์',
            ]);
        }

        // 🧾 VAT: เปลี่ยนการแบ่งเงินจริง (PricingEngine หัก VAT 7/107 จากเงินร้าน) → ต้องมีเลขผู้เสียภาษี 13 หลัก
        //    แบบเดียวกับฝั่งผู้ขาย (Seller\StoreController) · ตรวจเมื่อเปิด VAT หรือแก้เลขผู้เสียภาษีของร้านที่จด VAT
        $oldVat = (bool) $store->vat_registered;
        $newVat = array_key_exists('vat_registered', $validated) ? (bool) $validated['vat_registered'] : $oldVat;
        if (array_key_exists('tax_id', $validated)) {
            $validated['tax_id'] = $validated['tax_id'] !== null ? trim((string) $validated['tax_id']) : null;
            $validated['tax_id'] = $validated['tax_id'] === '' ? null : $validated['tax_id'];
        }
        $taxIdChanged = array_key_exists('tax_id', $validated) && (string) $validated['tax_id'] !== (string) $store->tax_id;
        $effectiveTaxId = array_key_exists('tax_id', $validated) ? $validated['tax_id'] : $store->tax_id;
        if ($newVat && (! $oldVat || $taxIdChanged) && strlen((string) preg_replace('/\D/', '', (string) $effectiveTaxId)) !== 13) {
            return back()->withInput()->withErrors([
                'tax_id' => 'ร้านที่จดทะเบียน VAT ต้องมีเลขประจำตัวผู้เสียภาษี 13 หลัก',
            ]);
        }

        // ค่าก่อนแก้ — ใช้บันทึกประวัติการเปลี่ยนที่กระทบเงิน (VAT / แพ็กเกจ / อัตรา GP)
        $before = [
            'vat_registered' => $oldVat,
            'tax_id' => $store->tax_id,
            'package_id' => $store->package_id !== null ? (int) $store->package_id : null,
            'commission_rate' => round((float) $store->commission_rate, 2),
        ];

        // ร้านแนะนำ: เพิ่มใหม่ → ต่อท้ายลำดับ · เอาออก → ล้างลำดับ (เหมือนปุ่มสลับในหน้ารายการ)
        if (array_key_exists('is_featured_home', $validated)) {
            $featured = (bool) $validated['is_featured_home'];
            if ($featured && ! $store->is_featured_home) {
                $validated['featured_home_order'] = (VendorStore::where('is_featured_home', true)->max('featured_home_order') ?? 0) + 1;
            } elseif (! $featured) {
                $validated['featured_home_order'] = null;
            }
        }

        // ยืนยันร้านครั้งแรก → บันทึกเวลายืนยัน
        if (! empty($validated['is_verified']) && ! $store->is_verified) {
            $validated['verified_at'] = now();
        }

        $store->update($validated);

        $this->auditMoneySettings($request, $store, $before);

        return redirect()
            ->route('admin.storefront.vendor-stores.show', $store)
            ->with('success', 'อัพเดทร้านค้า "'.$store->store_name.'" สำเร็จ');
    }

    /**
     * บันทึกประวัติเมื่อแอดมินเปลี่ยนค่าที่กระทบเงินของร้าน (accounting_activity_logs)
     *
     * - VAT / เลขผู้เสียภาษี → action เดียวกับฝั่งผู้ขาย 'store.vat_registered_changed'
     * - แพ็กเกจ / อัตรา GP → 'store.gp_settings_changed'
     * บันทึกไม่ได้ต้องไม่ทำให้การแก้ร้านล้ม (log warning แทน)
     *
     * @param  array{vat_registered: bool, tax_id: ?string, package_id: ?int, commission_rate: float}  $before
     */
    private function auditMoneySettings(Request $request, VendorStore $store, array $before): void
    {
        $store->refresh();
        $after = [
            'vat_registered' => (bool) $store->vat_registered,
            'tax_id' => $store->tax_id,
            'package_id' => $store->package_id !== null ? (int) $store->package_id : null,
            'commission_rate' => round((float) $store->commission_rate, 2),
        ];

        $entries = [];
        if ($before['vat_registered'] !== $after['vat_registered'] || (string) $before['tax_id'] !== (string) $after['tax_id']) {
            $entries[] = [
                'action' => 'store.vat_registered_changed',
                'description' => 'แอดมินเปลี่ยนสถานะจดทะเบียน VAT ของร้าน: '
                    .($before['vat_registered'] ? 'จด' : 'ไม่จด').' → '.($after['vat_registered'] ? 'จด' : 'ไม่จด')
                    .((string) $before['tax_id'] !== (string) $after['tax_id'] ? ' (แก้เลขผู้เสียภาษี)' : ''),
                'old_values' => ['vat_registered' => $before['vat_registered'], 'tax_id' => $before['tax_id']],
                'new_values' => ['vat_registered' => $after['vat_registered'], 'tax_id' => $after['tax_id']],
            ];
        }
        if ($before['package_id'] !== $after['package_id'] || abs($before['commission_rate'] - $after['commission_rate']) > 0.001) {
            $entries[] = [
                'action' => 'store.gp_settings_changed',
                'description' => 'แอดมินเปลี่ยนแพ็กเกจ/อัตรา GP ของร้าน: แพ็กเกจ #'.($before['package_id'] ?? '-').' → #'.($after['package_id'] ?? '-')
                    .' · GP '.$before['commission_rate'].'% → '.$after['commission_rate'].'%',
                'old_values' => ['package_id' => $before['package_id'], 'commission_rate' => $before['commission_rate']],
                'new_values' => ['package_id' => $after['package_id'], 'commission_rate' => $after['commission_rate']],
            ];
        }

        foreach ($entries as $entry) {
            try {
                \App\Models\AccountingActivityLog::create($entry + [
                    'user_id' => auth()->id(),
                    'loggable_type' => VendorStore::class,
                    'loggable_id' => $store->id,
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Admin vendor store audit log failed', [
                    'store_id' => $store->id,
                    'action' => $entry['action'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * เหตุผลที่เปิดขาย (is_active) จากสวิตช์ไม่ได้ — null = เปิดได้
     *
     * suspended ต้องใช้ปุ่ม "ยกเลิกการระงับ" (ล้างเหตุผล + แจ้งเจ้าของ) · closed = ใบสมัครถูกปฏิเสธหรือบัญชีถูกลบ
     * pending = ต้องผ่านขั้นอนุมัติ (ได้ role ผู้ขาย + แพ็กเกจ)
     */
    private function activationBlockedMessage(VendorStore $store): ?string
    {
        return match ($store->status) {
            'suspended' => 'ร้านนี้ถูกระงับอยู่ ใช้ปุ่ม "ยกเลิกการระงับ / เปิดร้าน" ในหน้ารายละเอียดร้าน',
            'closed' => $this->closedReasonLabel($store) === 'deleted'
                ? 'ร้านนี้ปิดถาวรเพราะเจ้าของลบบัญชีแล้ว เปิดขายไม่ได้'
                : 'ร้านนี้ปิดเพราะใบสมัครถูกปฏิเสธ ให้ผู้สมัครยื่นใหม่แล้วอนุมัติที่หน้าคำขอเปิดร้าน',
            'pending' => 'ร้านนี้ยังรออนุมัติ ใช้ปุ่มอนุมัติในหน้าคำขอเปิดร้าน',
            default => null,
        };
    }

    /**
     * ร้าน status = closed มาจากไหน: 'deleted' = เจ้าของลบบัญชี (PDPA) · 'rejected' = ใบสมัครถูกปฏิเสธ
     */
    private function closedReasonLabel(VendorStore $store): string
    {
        $ownerActive = \App\Models\User::whereKey($store->user_id)->exists();

        return $ownerActive ? 'rejected' : 'deleted';
    }

    /**
     * ทำรหัสสีให้เป็น #rrggbb ตัวพิมพ์เล็ก (รับ #rgb / rgb / #rrggbb / rrggbb ที่ผ่าน validation แล้ว)
     */
    private static function normalizeHexColor(string $color): string
    {
        $hex = strtolower(ltrim(trim($color), '#'));
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return '#'.$hex;
    }

    /**
     * ระงับร้าน (ต้องมีเหตุผล — เจ้าของร้านเห็นข้อความนี้)
     *
     * ร้านที่ status = suspended เข้าหลังร้าน/ขายของไม่ได้ (EnsureHasVendorStore + VendorStore::isBlockedFromSelling)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function suspend(Request $request, VendorStore $store)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ], [
            'reason.required' => 'กรุณาระบุเหตุผลที่ระงับร้าน (เจ้าของร้านจะเห็นข้อความนี้)',
            'reason.min' => 'เหตุผลต้องมีอย่างน้อย 5 ตัวอักษร',
            'reason.max' => 'เหตุผลต้องไม่เกิน 500 ตัวอักษร',
        ]);

        if ($store->isPlatformStore()) {
            return back()->with('error', 'ระงับร้านทางการของแพลตฟอร์มไม่ได้');
        }

        if ($store->status === 'suspended') {
            return back()->with('info', 'ร้านนี้ถูกระงับอยู่แล้ว');
        }

        $store->update([
            'status' => 'suspended',
            'is_active' => false,
            'suspension_reason' => trim($validated['reason']),
        ]);

        $this->notifyOwner(
            $store,
            'ร้านค้าของคุณถูกระงับชั่วคราว',
            'ร้าน "'.$store->store_name.'" ถูกระงับ เหตุผล: '.trim($validated['reason']).' — ติดต่อทีมงานเพื่อขอเปิดร้านอีกครั้ง',
            ['store_id' => $store->id, 'status' => 'suspended']
        );

        \Illuminate\Support\Facades\Log::warning('Vendor store suspended by admin', [
            'store_id' => $store->id,
            'admin_id' => auth()->id(),
            'reason' => $validated['reason'],
        ]);

        return back()->with('success', 'ระงับร้าน "'.$store->store_name.'" แล้ว');
    }

    /**
     * ยกเลิกการระงับร้าน
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unsuspend(VendorStore $store)
    {
        // 🔒 เปิดคืนได้เฉพาะร้านที่ "ถูกระงับ" — ร้าน closed มาจากใบสมัครถูกปฏิเสธ (SellerApplicationController::reject)
        //    หรือเจ้าของลบบัญชีตาม PDPA (AccountDeletionService) ถ้าเปิดตรงนี้ร้านจะขายได้โดยไม่ผ่านขั้นอนุมัติ
        //    (ไม่ได้ role ผู้ขาย/แพ็กเกจ) หรือร้านของบัญชีที่ลบแล้วกลับมาโชว์ต่อสาธารณะ
        if ($store->status === 'closed') {
            return back()->with('error', $this->activationBlockedMessage($store));
        }

        if ($store->status !== 'suspended') {
            return back()->with('info', 'ร้านนี้ไม่ได้ถูกระงับ');
        }

        $store->update([
            'status' => 'active',
            'is_active' => true,
            'suspension_reason' => null,
        ]);

        $this->notifyOwner(
            $store,
            'ร้านค้าของคุณเปิดใช้งานอีกครั้งแล้ว',
            'ร้าน "'.$store->store_name.'" กลับมาขายได้ตามปกติ',
            ['store_id' => $store->id, 'status' => 'active']
        );

        \Illuminate\Support\Facades\Log::info('Vendor store unsuspended by admin', ['store_id' => $store->id, 'admin_id' => auth()->id()]);

        return back()->with('success', 'เปิดร้าน "'.$store->store_name.'" อีกครั้งแล้ว');
    }

    /**
     * แจ้งเจ้าของร้าน (in-app + push ผ่าน NotificationService) — แจ้งไม่ได้ต้องไม่ทำให้การระงับล้ม
     */
    private function notifyOwner(VendorStore $store, string $title, string $message, array $data): void
    {
        try {
            $owner = \App\Models\User::find($store->user_id);
            if (! $owner) {
                return;
            }

            app(\App\Services\NotificationService::class)->create(
                $owner,
                'seller_store_status',
                $title,
                $message,
                $data,
                \Illuminate\Support\Facades\Route::has('seller.dashboard') ? route('seller.dashboard') : null,
                'ดูร้านของฉัน',
                'high',
                true
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Vendor store: notify owner failed', ['store_id' => $store->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * สลับสถานะ Active/Inactive
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(VendorStore $store)
    {
        // สวิตช์เปิดขายกลับค่า is_active อย่างเดียว — ร้านที่ถูกระงับ/ปิดถาวร/รออนุมัติ ยังถูกบล็อกด้วย status
        // (เดิมตอบ "เปิดใช้งานแล้ว" ทั้งที่ร้านยังขายไม่ได้ และเหตุผลการระงับค้างอยู่) → ไม่ให้เปิดจากสวิตช์นี้
        // ปิดการขายชั่วคราวยังทำได้เสมอ (ปลอดภัย)
        if (! $store->is_active && ($blockedMessage = $this->activationBlockedMessage($store))) {
            return response()->json([
                'success' => false,
                'code' => 'STORE_BLOCKED',
                'is_active' => false,
                'message' => $blockedMessage,
                'show_url' => route('admin.storefront.vendor-stores.show', $store),
            ], 409);
        }

        $store->is_active = ! $store->is_active;
        $store->save();

        return response()->json([
            'success' => true,
            'is_active' => $store->is_active,
            'message' => $store->is_active
                ? 'เปิดใช้งานร้านค้า "'.$store->store_name.'" แล้ว'
                : 'ปิดใช้งานร้านค้า "'.$store->store_name.'" แล้ว',
        ]);
    }

    /**
     * สลับสถานะ Featured
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleFeatured(VendorStore $store)
    {
        $store->is_featured_home = ! $store->is_featured_home;

        // ถ้าเป็น featured ใหม่ ให้ตั้ง order
        if ($store->is_featured_home) {
            $maxOrder = VendorStore::where('is_featured_home', true)->max('featured_home_order') ?? 0;
            $store->featured_home_order = $maxOrder + 1;
        } else {
            $store->featured_home_order = null;
        }

        $store->save();

        return response()->json([
            'success' => true,
            'is_featured' => $store->is_featured_home,
            'message' => $store->is_featured_home
                ? 'เพิ่มร้าน "'.$store->store_name.'" เป็นร้านค้าแนะนำแล้ว'
                : 'นำร้าน "'.$store->store_name.'" ออกจากร้านค้าแนะนำแล้ว',
        ]);
    }

    /**
     * ลบร้านค้า (Soft Delete)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(VendorStore $store)
    {
        $storeName = $store->store_name;

        // Soft delete
        $store->delete();

        return redirect()
            ->route('admin.storefront.vendor-stores.index')
            ->with('success', 'ลบร้านค้า "'.$storeName.'" สำเร็จ');
    }
}
