<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** ป้ายปุ่มเดิม → ป้ายใหม่ (เจ้าของสั่ง 2026-11-14 "เปลี่ยนจากคำว่าเข้าห้องแม่หมอ เป็น เข้าห้องดูดวง") */
    private const OLD = 'เข้าห้องแม่หมอ';

    private const NEW = 'เข้าห้องดูดวง';

    /**
     * แก้ข้อความชวน (mode=web) ที่อ้างชื่อปุ่มเดิม ให้ตรงกับปุ่มใหม่ในแชท
     *
     * ทำไมต้องแก้ใน DB: seeder จับคู่แถวด้วยตัวข้อความ — แก้แค่ seeder จะได้แถวใหม่ซ้ำ
     * แล้วแถวเก่าที่บอกให้ "แตะ 🌙 เข้าห้องแม่หมอ" (ซึ่งไม่มีปุ่มนั้นแล้ว) ยังถูกสุ่มส่งต่อ
     * deploy รัน migrate ก่อน db:seed ⇒ แถวถูกแก้ก่อน seeder มาเทียบ จึงไม่ซ้ำ
     * รวมแถวที่ถูกลบแบบ soft delete ด้วย (seeder เทียบกับแถวนั้นด้วย — แอดมินลบแล้วห้ามฟื้น)
     */
    public function up(): void
    {
        $this->swap(self::OLD, self::NEW);
    }

    /**
     * คืนป้ายเดิม
     */
    public function down(): void
    {
        $this->swap(self::NEW, self::OLD);
    }

    private function swap(string $from, string $to): void
    {
        if (! Schema::hasTable('fortune_invite_messages') || ! Schema::hasColumn('fortune_invite_messages', 'mode')) {
            return;
        }

        DB::table('fortune_invite_messages')
            ->where('mode', 'web')
            ->where('message', 'like', '%'.$from.'%')
            ->update(['message' => DB::raw('REPLACE(message, '.DB::getPdo()->quote($from).', '.DB::getPdo()->quote($to).')')]);
    }
};
