<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🛡️ (2026-10-04) K1-4: เลือก "ผู้ใช้ทดสอบ" ที่ลบได้จริงแบบชัดเจน — ใช้ร่วมกันระหว่าง demo:reset และหน้าแอดมิน
 *
 * ที่มา: demo:reset --users เดิมลบทุกแถวที่อีเมลลงท้าย @example.com / @thaiprompt.com ด้วย whereRaw
 *   ⇒ ลบบัญชีร้านทางการ (official-shop@thaiprompt.com — สินค้าทั้งหมดของร้านทางการผูก seller_id นี้)
 *   ⇒ ลบ superadmin@ / admin@ (LineRegistrationSession หา superadmin@ เป็นต้นสายงาน)
 *   ⇒ ลบผู้ใช้ที่มีร้าน/เป็นไรเดอร์/มีออเดอร์ที่จ่ายเงินจริง (ปิด FOREIGN_KEY_CHECKS ⇒ แถวลูกกำพร้า)
 *
 * กติกา: ผู้ใช้ที่อีเมลตรงรูปแบบทดสอบ "และ" ไม่ติดข้อห้ามข้อใดเลย เท่านั้นที่ลบได้
 * ข้อห้าม (reason key → ข้อความไทยใน REASON_LABELS):
 *   official_shop · admin · store_owner · fresh_market_seller · rider · product_owner
 *   paid_orders · fresh_market_paid_orders · wallet_balance · wallet_history · sponsor_of_kept_user
 */
class DemoDataUserGuard
{
    /** รูปแบบอีเมลผู้ใช้ทดสอบ (LIKE) — ไม่รวม @thaiprompt.local ของลูกค้าที่มาจาก LINE/FB */
    public const DEMO_EMAIL_PATTERNS = ['%@example.com', '%@thaiprompt.com'];

    /** role ที่ถือเป็นทีมงาน/แอดมิน (users.role หรือ roles.name) */
    public const ADMIN_ROLES = ['admin', 'super_admin', 'superadmin', 'super-admin', 'manager'];

    public const REASON_LABELS = [
        'official_shop' => 'บัญชีร้านค้าทางการ',
        'admin' => 'แอดมิน / ทีมงาน',
        'store_owner' => 'เจ้าของร้านค้า',
        'fresh_market_seller' => 'ผู้ขายตลาดสด',
        'rider' => 'ไรเดอร์',
        'product_owner' => 'มีสินค้าในระบบ',
        'paid_orders' => 'มีออเดอร์ที่ชำระเงินจริง',
        'fresh_market_paid_orders' => 'มีออเดอร์ตลาดสดที่ชำระเงินจริง',
        'wallet_balance' => 'มีเงินคงเหลือในกระเป๋า',
        'wallet_history' => 'มีประวัติเงินเข้า-ออกกระเป๋า',
        'sponsor_of_kept_user' => 'เป็นผู้แนะนำของผู้ใช้ที่ต้องเก็บไว้',
    ];

    /**
     * แผนการลบ (ยังไม่ลบอะไร) — ใช้แสดงในหน้าแอดมิน / --dry-run
     *
     * @return array{deletable: array<int, array{id:int,email:string,name:string}>, protected: array<int, array{id:int,email:string,name:string,reasons:array<int,string>}>}
     */
    public function plan(): array
    {
        $candidates = $this->candidates();
        $reasons = $this->protectionReasons($candidates);

        $deletable = [];
        $protected = [];

        foreach ($candidates as $user) {
            $row = [
                'id' => (int) $user->id,
                'email' => (string) $user->email,
                'name' => (string) ($user->name ?? ''),
            ];

            if (! empty($reasons[(int) $user->id])) {
                $row['reasons'] = array_values(array_unique($reasons[(int) $user->id]));
                $protected[] = $row;
            } else {
                $deletable[] = $row;
            }
        }

        return ['deletable' => $deletable, 'protected' => $protected];
    }

    /**
     * id ผู้ใช้ทดสอบที่ลบได้จริง
     *
     * @return array<int, int>
     */
    public function deletableUserIds(): array
    {
        return array_map(fn (array $row) => $row['id'], $this->plan()['deletable']);
    }

    /**
     * ข้อความไทยของเหตุผล
     */
    public static function reasonLabel(string $key): string
    {
        return self::REASON_LABELS[$key] ?? $key;
    }

    /**
     * ผู้ใช้ที่อีเมลตรงรูปแบบทดสอบ (ยังไม่กรองข้อห้าม)
     *
     * @return Collection<int, object>
     */
    private function candidates(): Collection
    {
        if (! Schema::hasTable('users')) {
            return collect();
        }

        return DB::table('users')
            ->select($this->existingColumns('users', ['id', 'email', 'name', 'role', 'role_id', 'is_super_admin', 'sponsor_id']))
            ->where(function ($q) {
                foreach (self::DEMO_EMAIL_PATTERNS as $pattern) {
                    $q->orWhere('email', 'like', $pattern);
                }
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * เหตุผลที่ห้ามลบ ต่อ user id
     *
     * @param  Collection<int, object>  $candidates
     * @return array<int, array<int, string>>
     */
    private function protectionReasons(Collection $candidates): array
    {
        $reasons = [];
        if ($candidates->isEmpty()) {
            return $reasons;
        }

        $ids = $candidates->pluck('id')->map(fn ($id) => (int) $id)->all();
        $add = function (iterable $userIds, string $reason) use (&$reasons) {
            foreach ($userIds as $id) {
                $reasons[(int) $id][] = $reason;
            }
        };

        // 1) บัญชีร้านทางการ (config + ค่าตั้งต้นเผื่อ config ถูกเปลี่ยน)
        $officialEmails = array_values(array_unique(array_filter([
            mb_strtolower((string) config('shop.official_shop.seller_email', 'official-shop@thaiprompt.com')),
            'official-shop@thaiprompt.com',
        ])));
        $add($candidates->filter(fn ($u) => in_array(mb_strtolower((string) $u->email), $officialEmails, true))->pluck('id'), 'official_shop');

        // 2) แอดมิน/ทีมงาน: is_super_admin, users.role, roles.name
        $adminRoleIds = Schema::hasTable('roles') && Schema::hasColumn('roles', 'name')
            ? DB::table('roles')->whereIn(DB::raw('LOWER(name)'), self::ADMIN_ROLES)->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];
        $add($candidates->filter(function ($u) use ($adminRoleIds) {
            return (bool) ($u->is_super_admin ?? false)
                || in_array(mb_strtolower((string) ($u->role ?? '')), self::ADMIN_ROLES, true)
                || (($u->role_id ?? null) !== null && in_array((int) $u->role_id, $adminRoleIds, true));
        })->pluck('id'), 'admin');

        // 3) เป็นเจ้าของ/มีบทบาทในระบบ
        $add($this->idsIn('vendor_stores', 'user_id', $ids), 'store_owner');
        $add($this->idsIn('fresh_market_sellers', 'user_id', $ids), 'fresh_market_seller');
        $add($this->idsIn('riders', 'user_id', $ids), 'rider');
        $add($this->idsIn('products', 'seller_id', $ids), 'product_owner');

        // 4) เงินจริง: ออเดอร์ที่จ่ายแล้ว/คืนเงินแล้ว · กระเป๋ามียอด/มีประวัติ
        $add($this->idsIn('orders', 'user_id', $ids, fn ($q) => $q->whereIn('payment_status', ['paid', 'refunded'])), 'paid_orders');
        $add($this->idsIn('fresh_market_orders', 'buyer_id', $ids, fn ($q) => $q->whereIn('payment_status', ['paid', 'released', 'refunded'])), 'fresh_market_paid_orders');
        $add($this->idsIn('wallets', 'user_id', $ids, fn ($q) => $q->where('balance', '>', 0)), 'wallet_balance');
        $add($this->idsIn('wallet_transactions', 'user_id', $ids, fn ($q) => $q->where('status', 'completed')), 'wallet_history');

        // 5) ผู้แนะนำของคนที่ต้องเก็บไว้ (สายงาน MLM ห้ามขาด) — วนจนนิ่ง เพราะการเก็บคนหนึ่งอาจทำให้ต้องเก็บผู้แนะนำของเขาด้วย
        if (Schema::hasColumn('users', 'sponsor_id')) {
            for ($round = 0; $round < 20; $round++) {
                $deletable = array_values(array_diff($ids, array_keys($reasons)));
                if ($deletable === []) {
                    break;
                }

                $sponsors = [];
                foreach (array_chunk($deletable, 500) as $chunk) {
                    $sponsors = array_merge($sponsors, DB::table('users')
                        ->whereIn('sponsor_id', $chunk)
                        ->whereNotIn('id', $deletable)
                        ->distinct()
                        ->pluck('sponsor_id')
                        ->map(fn ($id) => (int) $id)
                        ->all());
                }

                $sponsors = array_values(array_unique($sponsors));
                if ($sponsors === []) {
                    break;
                }

                $add($sponsors, 'sponsor_of_kept_user');
            }
        }

        return $reasons;
    }

    /**
     * user id (จากชุด $ids) ที่มีแถวในตาราง $table คอลัมน์ $column — ตาราง/คอลัมน์ไม่มี = ว่าง
     *
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    private function idsIn(string $table, string $column, array $ids, ?callable $scope = null): array
    {
        if ($ids === [] || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return [];
        }

        $found = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            $query = DB::table($table)->whereIn($column, $chunk);
            if ($scope !== null) {
                $scope($query);
            }
            $found = array_merge($found, $query->distinct()->pluck($column)->map(fn ($id) => (int) $id)->all());
        }

        return array_values(array_unique($found));
    }

    /**
     * @param  array<int, string>  $wanted
     * @return array<int, string>
     */
    private function existingColumns(string $table, array $wanted): array
    {
        $columns = Schema::getColumnListing($table);

        return array_values(array_intersect($wanted, $columns));
    }
}
