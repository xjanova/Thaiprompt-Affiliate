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
        // Update icons to more beautiful and appropriate Font Awesome icons
        $updates = [
            'การสนับสนุนทางเทคนิค' => 'fa-solid fa-headset',
            'การเงินและการชำระเงิน' => 'fa-solid fa-wallet',
            'บัญชีผู้ใช้' => 'fa-solid fa-user-circle',
            'ระบบแอฟฟิลิเอท' => 'fa-solid fa-sitemap',
            'ผลิตภัณฑ์และบริการ' => 'fa-solid fa-cubes',
            'ข้อเสนอแนะ' => 'fa-solid fa-comment-dots',
            'อื่นๆ' => 'fa-solid fa-ellipsis',
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
        // Revert to old icons
        $updates = [
            'การสนับสนุนทางเทคนิค' => 'fa-solid fa-wrench',
            'การเงินและการชำระเงิน' => 'fa-solid fa-dollar-sign',
            'บัญชีผู้ใช้' => 'fa-solid fa-user',
            'ระบบแอฟฟิลิเอท' => 'fa-solid fa-network-wired',
            'ผลิตภัณฑ์และบริการ' => 'fa-solid fa-box',
            'ข้อเสนอแนะ' => 'fa-solid fa-lightbulb',
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
};
