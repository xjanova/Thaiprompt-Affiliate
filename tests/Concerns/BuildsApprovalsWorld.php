<?php

namespace Tests\Concerns;

use App\Models\KycVerification;
use App\Models\MlmCommission;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\Rider;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\TicketService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ตัวช่วยเทสต์คิวอนุมัติของแอปแอดมิน (/api/admin/approvals/*) — สร้างแถวแบบเจาะจงคอลัมน์
 */
trait BuildsApprovalsWorld
{
    /** คำขอเปิดร้านที่รออนุมัติ */
    protected function makePendingStore(?User $owner = null, array $attrs = []): VendorStore
    {
        $owner ??= User::factory()->create(['role' => 'user']);

        return VendorStore::create(array_merge([
            'user_id' => $owner->id,
            'store_name' => 'ร้านป้าแดง '.Str::random(4),
            'store_slug' => 'shop-'.$owner->id.'-'.Str::lower(Str::random(6)),
            'business_type' => 'company',
            'company_name' => 'บริษัท ป้าแดง จำกัด',
            'tax_id' => '0105555123456',
            'store_phone' => '0812345678',
            'store_email' => $owner->email,
            'store_address' => '99/9 ถนนเจริญกรุง',
            'store_city' => 'บางรัก',
            'store_state' => 'กรุงเทพมหานคร',
            'store_postal_code' => '10500',
            'status' => 'pending',
            'is_active' => false,
        ], $attrs));
    }

    /**
     * ไรเดอร์ (ค่าเริ่มต้น: ใบสมัครรอตรวจ มอเตอร์ไซค์ เอกสารครบบน private disk)
     *
     * @param  array<string, mixed>  $attrs
     */
    protected function makeRiderApplicant(array $attrs = [], bool $withDocuments = true): Rider
    {
        $user = User::factory()->create(['role' => 'user']);

        $rider = new Rider;
        $rider->forceFill(array_merge([
            'user_id' => $user->id,
            'full_name' => 'สมชาย ขยันส่ง',
            'phone' => '0898765432',
            'id_card_number' => '1234567890121',
            'status' => 'pending',
            'availability' => 'offline',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => '1กข 1234',
            'province' => 'กรุงเทพมหานคร',
        ], $attrs))->save();

        if ($withDocuments) {
            $columns = [];
            foreach (['id_card' => 'id_card_image', 'driver_license' => 'driver_license_image', 'vehicle_registration' => 'vehicle_registration_image', 'profile' => 'profile_image'] as $type => $column) {
                $path = 'riders/'.$rider->id.'/'.$type.'/doc.jpg';
                Storage::disk('local')->put($path, 'fake-image-'.$type);
                $columns[$column] = $path;
            }
            $rider->forceFill($columns)->save();
        }

        return $rider->fresh();
    }

    /** ตั๋วซัพพอร์ตผ่าน TicketService (เส้นทางเดียวกับผู้ใช้เปิดตั๋วจากเว็บ) */
    protected function makeTicket(User $owner, array $data = []): Ticket
    {
        return app(TicketService::class)->createTicket(array_merge([
            'user_id' => $owner->id,
            'subject' => 'ถอนเงินไม่เข้า',
            'description' => 'ถอนเงินเมื่อวานยังไม่เข้าบัญชีค่ะ',
            'priority' => 'medium',
        ], $data));
    }

    /** คอมมิชชัน MLM (ค่าเริ่มต้น: รออนุมัติ 120 บาท) */
    protected function makeCommission(?User $earner = null, string $status = 'pending', float $amount = 120): MlmCommission
    {
        $earner ??= User::factory()->create(['role' => 'user']);

        // สมาชิก MLM ได้คนละ 1 แถว (unique user_id) — ใช้แถวเดิมถ้ามีแล้ว
        $member = MlmMember::where('user_id', $earner->id)->first();
        if (! $member) {
            $plan = MlmPlan::create([
                'name' => 'Test Plan',
                'name_th' => 'แผนทดสอบ',
                'slug' => 'plan-'.uniqid(),
                'type' => 'unilevel',
                'is_active' => true,
                'is_default' => true,
            ]);

            $member = MlmMember::create([
                'user_id' => $earner->id,
                'mlm_plan_id' => $plan->id,
                'member_code' => 'M'.uniqid(),
                'status' => 'active',
                'is_qualified' => true,
                'joined_at' => now()->subMonth(),
            ]);
        }

        return MlmCommission::create([
            'mlm_member_id' => $member->id,
            'mlm_plan_id' => $member->mlm_plan_id,
            'user_id' => $earner->id,
            'type' => 'direct_referral',
            'level' => 1,
            'pv_amount' => 0,
            'sales_amount' => 1000,
            'commission_amount' => $amount,
            'status' => $status,
            'approved_at' => $status === 'approved' ? now() : null,
        ]);
    }

    /** แถว eKYC ที่ AI ส่งให้แอดมินตรวจ (ไม่มีรูป — ใช้กับเทสต์ที่ไม่เปิดรูป) */
    protected function makeEkycReview(?User $user = null, array $attrs = []): KycVerification
    {
        $user ??= User::factory()->create(['role' => 'user']);

        $kyc = new KycVerification;
        $kyc->forceFill(array_merge([
            'user_id' => $user->id,
            'method' => KycVerification::METHOD_EKYC,
            'status' => 'pending',
            'ai_decision' => 'review',
            'ai_reasons' => ['BORDERLINE_MATCH'],
            'ai_face_match' => 0.41,
            'ai_liveness' => 0.92,
            'ai_real' => 0.88,
            'id_last4' => '0121',
            'name_th' => 'นาย ณัฐ ใจงาม',
            'submitted_at' => now()->subMinutes(30),
            'processed_at' => now()->subMinutes(30),
        ], $attrs))->save();

        return $kyc->fresh();
    }
}
