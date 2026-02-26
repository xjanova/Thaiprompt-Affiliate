<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม mlm_prospect_id เพื่อเชื่อม FortuneReferral กับ MlmProspect
     * ทำให้ fortune referral แสดงในหน้า "ผู้มุ่งหวัง" ของ user ได้
     */
    public function up(): void
    {
        Schema::table('fortune_referrals', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_referrals', 'mlm_prospect_id')) {
                $table->unsignedBigInteger('mlm_prospect_id')->nullable()
                    ->after('referrer_mlm_member_id')
                    ->comment('เชื่อมกับ MlmProspect สำหรับแสดงในหน้าผู้มุ่งหวัง');

                $table->foreign('mlm_prospect_id', 'fk_fr_mlm_prospect')
                    ->references('id')
                    ->on('mlm_prospects')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * ลบ column mlm_prospect_id
     */
    public function down(): void
    {
        Schema::table('fortune_referrals', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_referrals', 'mlm_prospect_id')) {
                $table->dropForeign('fk_fr_mlm_prospect');
                $table->dropColumn('mlm_prospect_id');
            }
        });
    }
};
