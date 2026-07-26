<?php

namespace App\Services\Eve;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * น้อง Eve — "ตอนนี้กำลังคุยกับใคร"
 *
 * แยกระดับผู้ใช้ (tier) เพื่อกำหนด 3 อย่าง: บุคลิก/คำตอบ, เครื่องมือที่เรียกได้, และโควตา
 *
 * 🔒 กฎเหล็ก: tier ต้องมาจาก Auth เท่านั้น — ห้ามรับจาก request body เด็ดขาด
 *    ไม่งั้นใครก็ส่ง {"tier":"admin"} มาเพื่อเปิดเครื่องมือหลังบ้านได้
 */
class EveActor
{
    public const TIER_GUEST = 'guest';

    public const TIER_CUSTOMER = 'customer';

    public const TIER_SELLER = 'seller';

    public const TIER_ADMIN = 'admin';

    public function __construct(
        public readonly string $tier,
        public readonly ?User $user = null,
    ) {}

    /**
     * อ่านผู้ใช้จาก request (session/auth) → คืน actor
     */
    public static function fromRequest(Request $request): self
    {
        $user = $request->user();

        if (! $user) {
            return new self(self::TIER_GUEST);
        }

        // ── แอดมิน ──
        // ⚠️ เช็คแบบนี้เท่านั้น (ยืนยันกับโค้ดจริงแล้ว):
        //    - ห้ามใช้ User::hasRole() — ถ้ามี role_id มันจะเทียบเฉพาะ roleModel
        //      แล้วไม่ fallback มาดูคอลัมน์ role → คนที่ role='admin' จะหลุดเป็น false
        //    - ห้ามลอก AdminMiddleware — ตัวนั้นไม่ยอมรับ role='super_admin'
        //      ถ้าไม่ได้ตั้ง is_super_admin ด้วย (เป็นบั๊กของมันเอง ไม่ใช่ของเรา)
        if ($user->is_super_admin === true || in_array($user->role, ['admin', 'super_admin'], true)) {
            return new self(self::TIER_ADMIN, $user);
        }

        // ── ผู้ขาย ──
        if ($user->role === 'seller') {
            return new self(self::TIER_SELLER, $user);
        }

        return new self(self::TIER_CUSTOMER, $user);
    }

    public function isAdmin(): bool
    {
        return $this->tier === self::TIER_ADMIN;
    }

    public function isGuest(): bool
    {
        return $this->tier === self::TIER_GUEST;
    }

    /**
     * ชื่อที่ Eve ใช้เรียกผู้ใช้ (null = ยังไม่ล็อกอิน)
     */
    public function displayName(): ?string
    {
        return $this->user?->name;
    }

    /**
     * ความยาวคำตอบสูงสุด — แอดมินต้องการรายงาน/สรุปยาวกว่าลูกค้า
     */
    public function maxTokens(): int
    {
        return $this->isAdmin() ? 1200 : 320;
    }

    /**
     * โควตาต่อวัน (กันเผาเครดิต AI pool ที่ใช้ร่วมกับระบบดูดวงแบบเสียเงิน)
     */
    public function dailyQuota(): int
    {
        return match ($this->tier) {
            self::TIER_ADMIN => 500,
            self::TIER_SELLER => 200,
            self::TIER_CUSTOMER => 120,
            default => 60,
        };
    }

    /**
     * คีย์สำหรับนับโควตา/เก็บบทสนทนา — ผูกกับบัญชีถ้าล็อกอิน ไม่งั้นใช้ IP
     */
    public function quotaKey(Request $request): string
    {
        return $this->user
            ? 'u:'.$this->user->id
            : 'ip:'.sha1((string) $request->ip());
    }

    /**
     * ชื่อเครื่องมือที่ tier นี้เรียกได้ (ใช้กับ tool registry)
     *
     * @return array<int,string>
     */
    public function allowedTools(): array
    {
        // ทุกคนใช้ได้ (ข้อมูลสาธารณะล้วน)
        $tools = ['search_products', 'list_categories', 'explain_page', 'navigate', 'store_info'];

        if ($this->tier === self::TIER_GUEST) {
            return $tools;
        }

        // ล็อกอินแล้ว — ข้อมูล "ของตัวเอง" เท่านั้น (handler ต้อง scope ด้วย Auth::id() เสมอ)
        $tools = array_merge($tools, ['my_orders', 'order_status', 'my_wallet', 'my_commissions', 'my_rank', 'my_tickets']);

        if ($this->isAdmin()) {
            $tools = array_merge($tools, ['signals', 'report', 'tickets_summary', 'urgent_queue']);
        }

        return $tools;
    }

    public function can(string $tool): bool
    {
        return in_array($tool, $this->allowedTools(), true);
    }
}
