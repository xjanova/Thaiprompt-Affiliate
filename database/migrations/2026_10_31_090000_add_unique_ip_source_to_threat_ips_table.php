<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    use SafeMigration;

    /**
     * กันแถวซ้ำของ (ip_address, source) เพื่อให้ threat:update เขียนเป็นชุดด้วย upsert ได้
     *
     * ไม่มี index นี้ upsert บน MySQL จะ insert แถวใหม่ทุกรอบแทนการอัปเดต
     * ต้องเพิ่มก่อนฟีด proxies 2.9 ล้านแถวลงตาราง — ตอนนี้ตารางบน production มีแค่หลักพันแถว
     */
    public function up(): void
    {
        // แถวซ้ำเกิดได้จาก updateOrCreate สองตัวแข่งกัน เก็บแถวล่าสุดไว้
        $duplicates = DB::table('threat_ips')
            ->select('ip_address', 'source', DB::raw('MAX(id) as keep_id'))
            ->groupBy('ip_address', 'source')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('threat_ips')
                ->where('ip_address', $duplicate->ip_address)
                ->where('source', $duplicate->source)
                ->where('id', '<', $duplicate->keep_id)
                ->delete();
        }

        $this->safeAddIndex('threat_ips', ['ip_address', 'source'], 'threat_ips_ip_source_unique', 'unique');
    }

    /**
     * ลบ unique index
     */
    public function down(): void
    {
        $this->safeDropIndex('threat_ips', 'threat_ips_ip_source_unique');
    }
};
