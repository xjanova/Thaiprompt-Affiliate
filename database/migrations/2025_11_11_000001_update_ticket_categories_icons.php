<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update icons for existing categories
        $updates = [
            'การสนับสนุนทางเทคนิค' => 'fa-solid fa-screwdriver-wrench',
            'การเงินและการชำระเงิน' => 'fa-solid fa-wallet',
            'บัญชีผู้ใช้' => 'fa-solid fa-id-card',
            'ระบบแอฟฟิลิเอท' => 'fa-solid fa-sitemap',
            'ผลิตภัณฑ์และบริการ' => 'fa-solid fa-gift',
            'ข้อเสนอแนะ' => 'fa-solid fa-comment-dots',
            'อื่นๆ' => 'fa-solid fa-circle-question',
        ];

        foreach ($updates as $name => $icon) {
            DB::table('ticket_categories')
                ->where('name', $name)
                ->update([
                    'icon' => $icon,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback to old icons
        $rollback = [
            'การสนับสนุนทางเทคนิค' => 'fa-solid fa-wrench',
            'การเงินและการชำระเงิน' => 'fa-solid fa-dollar-sign',
            'บัญชีผู้ใช้' => 'fa-solid fa-user',
            'ระบบแอฟฟิลิเอท' => 'fa-solid fa-network-wired',
            'ผลิตภัณฑ์และบริการ' => 'fa-solid fa-box',
            'ข้อเสนอแนะ' => 'fa-solid fa-lightbulb',
            'อื่นๆ' => 'fa-solid fa-circle-question',
        ];

        foreach ($rollback as $name => $icon) {
            DB::table('ticket_categories')
                ->where('name', $name)
                ->update([
                    'icon' => $icon,
                    'updated_at' => now(),
                ]);
        }
    }
};
