<?php

namespace App\Services\Seller;

use App\Models\User;
use App\Models\VendorStore;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 🏪 คำขอเปิดร้านค้า (SELLER-07) — ตรรกะเดียวใช้ร่วมกันระหว่างเว็บ (/user/seller-apply) และแอป (/api/v1/seller/application)
 *
 * ขั้นตอน:
 *   1. สมาชิกยื่นคำขอ → สร้าง VendorStore status=pending, is_active=false
 *   2. แอดมินอนุมัติที่ /admin/seller-applications → ร้าน active + ผู้ใช้กลายเป็น role seller
 *   3. ผู้ขายยืนยันตัวตน (KYC) แล้วเริ่มลงสินค้าได้
 *
 * คำขอที่ถูกปฏิเสธ = ร้าน status=closed + suspension_reason เก็บเหตุผล → แก้ไขแล้วยื่นใหม่ได้ (แถวเดิม ไม่สร้างร้านซ้อน)
 */
class SellerApplicationService
{
    /**
     * role ที่สมัครเปิดร้านเองได้ (role อื่นเช่น provider/manager มีพื้นที่ทำงานของตัวเอง
     * การเปลี่ยนเป็น seller จะทำให้เข้าพื้นที่เดิมไม่ได้ → ต้องให้แอดมินจัดการ)
     */
    public const APPLICABLE_ROLES = ['user', 'affiliate'];

    /** สถานะที่ยื่นคำขอได้ */
    public const SUBMITTABLE_STATES = ['can_apply', 'rejected'];

    /**
     * ร้านล่าสุดของผู้ใช้ (ใช้แสดงสถานะคำขอ)
     */
    public function latestStore(User $user): ?VendorStore
    {
        return VendorStore::where('user_id', $user->id)->latest('id')->first();
    }

    /**
     * สถานะของผู้ใช้ต่อการเปิดร้าน
     *
     * @return string can_apply | pending | rejected | approved | seller | role_not_eligible
     */
    public function stateFor(User $user, ?VendorStore $store): string
    {
        if ($user->role === 'seller' || $user->is_super_admin || in_array($user->role, ['admin', 'super_admin'], true)) {
            return 'seller';
        }

        if ($store && $store->status === 'pending') {
            return 'pending';
        }

        if ($store && in_array($store->status, ['active', 'suspended'], true)) {
            return 'approved';
        }

        if (! in_array($user->role, self::APPLICABLE_ROLES, true)) {
            return 'role_not_eligible';
        }

        if ($store && $store->status === 'closed') {
            return 'rejected';
        }

        return 'can_apply';
    }

    /**
     * ข้อความภาษาไทยเมื่อยื่นคำขอไม่ได้ในสถานะนั้น
     */
    public function stateMessage(string $state): string
    {
        return match ($state) {
            'pending' => 'คำขอของคุณกำลังรอทีมงานตรวจสอบ',
            'approved', 'seller' => 'คุณเป็นผู้ขายอยู่แล้ว',
            'role_not_eligible' => 'บัญชีประเภทนี้สมัครเปิดร้านเองไม่ได้ กรุณาติดต่อทีมงาน',
            default => 'ไม่สามารถยื่นคำขอได้ในขณะนี้',
        };
    }

    /**
     * กฎตรวจแบบฟอร์ม (เว็บและแอปใช้ชุดเดียวกัน)
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'min:2', 'max:100'],
            'business_type' => ['required', 'in:individual,company'],
            'store_phone' => ['required', 'string', 'regex:/^0[0-9]{8,9}$/'],
            'store_description' => ['nullable', 'string', 'max:1000'],
            'store_address' => ['required', 'string', 'max:500'],
            'store_city' => ['required', 'string', 'max:100'],
            'store_state' => ['required', 'string', 'max:100'],
            'store_postal_code' => ['required', 'digits:5'],
            'company_name' => ['nullable', 'required_if:business_type,company', 'string', 'max:255'],
            'tax_id' => ['nullable', 'required_if:business_type,company', 'digits:13'],
            'accept_terms' => ['accepted'],
        ];
    }

    /**
     * ข้อความ validation ภาษาไทย
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'store_name.required' => 'กรุณากรอกชื่อร้าน',
            'store_name.min' => 'ชื่อร้านสั้นเกินไป',
            'store_name.max' => 'ชื่อร้านต้องไม่เกิน 100 ตัวอักษร',
            'business_type.required' => 'กรุณาเลือกประเภทผู้ขาย',
            'business_type.in' => 'ประเภทผู้ขายไม่ถูกต้อง',
            'store_phone.required' => 'กรุณากรอกเบอร์โทรร้าน',
            'store_phone.regex' => 'เบอร์โทรต้องเป็นตัวเลข 9-10 หลัก ขึ้นต้นด้วย 0',
            'store_description.max' => 'รายละเอียดร้านต้องไม่เกิน 1,000 ตัวอักษร',
            'store_address.required' => 'กรุณากรอกที่อยู่ร้าน',
            'store_address.max' => 'ที่อยู่ร้านต้องไม่เกิน 500 ตัวอักษร',
            'store_city.required' => 'กรุณากรอกอำเภอ/เขต',
            'store_state.required' => 'กรุณากรอกจังหวัด',
            'store_postal_code.required' => 'กรุณากรอกรหัสไปรษณีย์',
            'store_postal_code.digits' => 'รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก',
            'company_name.required_if' => 'กรุณากรอกชื่อบริษัท',
            'tax_id.required_if' => 'กรุณากรอกเลขประจำตัวผู้เสียภาษี',
            'tax_id.digits' => 'เลขประจำตัวผู้เสียภาษีต้องเป็นตัวเลข 13 หลัก',
            'accept_terms.accepted' => 'กรุณายอมรับเงื่อนไขการเปิดร้านค้า',
        ];
    }

    /**
     * ยื่นคำขอเปิดร้าน (หรือยื่นใหม่หลังถูกปฏิเสธ) — ล็อกแถวร้านเดิมกันกดส่งซ้ำพร้อมกัน
     *
     * @param  array<string, mixed>  $validated  ค่าที่ผ่าน rules() แล้ว
     * @return array{ok: bool, state?: string, store?: VendorStore, resubmitted?: bool}
     *
     * @throws \Throwable เมื่อบันทึกไม่สำเร็จ (ผู้เรียก log + ตอบภาษาไทย)
     */
    public function submit(User $user, array $validated): array
    {
        $result = DB::transaction(function () use ($user, $validated) {
            // ล็อกแถวร้านเดิมของผู้ใช้ กันกดส่งซ้ำ 2 ครั้งพร้อมกันแล้วได้ร้านซ้อน
            $existing = VendorStore::where('user_id', $user->id)->latest('id')->lockForUpdate()->first();
            $state = $this->stateFor($user, $existing);

            if (! in_array($state, self::SUBMITTABLE_STATES, true)) {
                return ['ok' => false, 'state' => $state];
            }

            $isCompany = $validated['business_type'] === 'company';
            $data = [
                'store_name' => trim((string) $validated['store_name']),
                'business_type' => $validated['business_type'],
                'store_phone' => $validated['store_phone'],
                'store_email' => $user->email,
                'store_description' => $validated['store_description'] ?? null,
                'store_address' => $validated['store_address'],
                'store_city' => $validated['store_city'],
                'store_state' => $validated['store_state'],
                'store_postal_code' => $validated['store_postal_code'],
                'company_name' => $isCompany ? ($validated['company_name'] ?? null) : null,
                'tax_id' => $isCompany ? ($validated['tax_id'] ?? null) : null,
                'status' => 'pending',
                'is_active' => false,
                'suspension_reason' => null,
            ];

            if ($existing) {
                $existing->update($data);

                return ['ok' => true, 'store' => $existing->fresh(), 'resubmitted' => true];
            }

            $store = VendorStore::create($data + [
                'user_id' => $user->id,
                'store_slug' => $this->uniqueSlug((string) $validated['store_name'], (int) $user->id),
            ]);

            return ['ok' => true, 'store' => $store, 'resubmitted' => false];
        });

        if ($result['ok']) {
            $this->notifyAdmins($user, $result['store']);
        }

        return $result;
    }

    /**
     * slug ร้านที่ไม่ซ้ำ (ชื่อไทยล้วนแปลงเป็น slug ไม่ได้ → ใช้ shop-{user})
     */
    private function uniqueSlug(string $name, int $userId): string
    {
        $base = Str::slug($name) ?: 'shop';
        $base = Str::limit($base, 60, '');

        do {
            $slug = $base.'-'.$userId.'-'.Str::lower(Str::random(4));
        } while (VendorStore::withTrashed()->where('store_slug', $slug)->exists());

        return $slug;
    }

    /**
     * แจ้งแอดมินว่ามีคำขอใหม่ (in-app + push ผ่าน NotificationService) — ล้มไม่เป็นไร
     */
    private function notifyAdmins(User $applicant, VendorStore $store): void
    {
        try {
            $admins = User::query()
                ->where(function ($q) {
                    $q->where('is_super_admin', true)->orWhereIn('role', ['admin', 'super_admin']);
                })
                ->whereNull('blocked_at')
                ->limit(20)
                ->get();

            $service = app(NotificationService::class);
            foreach ($admins as $admin) {
                $service->create(
                    $admin,
                    'seller_application',
                    'มีคำขอเปิดร้านค้าใหม่',
                    'ร้าน "'.$store->store_name.'" โดย '.$applicant->name.' รอการอนุมัติ',
                    ['store_id' => $store->id, 'user_id' => $applicant->id],
                    route('admin.seller-applications.index'),
                    'ตรวจสอบคำขอ'
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Seller application: notify admins failed', ['error' => $e->getMessage()]);
        }
    }
}
