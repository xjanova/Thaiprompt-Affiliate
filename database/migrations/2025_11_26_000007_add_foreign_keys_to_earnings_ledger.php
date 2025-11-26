<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม Foreign Key สำหรับ earnings_ledger
     * ต้องทำหลังจาก payout_requests ถูกสร้างแล้ว
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('earnings_ledger', function (Blueprint $table) {
            // เพิ่ม FK สำหรับ payout_request_id
            $table->foreign('payout_request_id')
                ->references('id')
                ->on('payout_requests')
                ->onDelete('set null');

            // เพิ่ม index
            $table->index('payout_request_id');
        });
    }

    /**
     * ลบ Foreign Key
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('earnings_ledger', function (Blueprint $table) {
            $table->dropForeign(['payout_request_id']);
            $table->dropIndex(['payout_request_id']);
        });
    }
};
