<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AiAssistantController — AI ผู้ช่วยขายของร้านค้า
 *
 * (2026-09-25) SELLER-10 / GAP-15: ตัดสินใจ "ยังไม่เปิดให้บริการ" สำหรับการเปิดตัว
 * เหตุผล:
 *   - view ทั้ง 5 หน้าไม่เคยถูกสร้าง (เดิมเปิดแล้ว 500)
 *   - โค้ดเดิม query ai_bot_profiles ด้วยคอลัมน์ user_id / bot_type / greeting_message
 *     และ ai_conversations ด้วย bot_id ซึ่งไม่มีอยู่จริงในตาราง → SQL error ทุกหน้า
 *   - testBot() ตอบข้อความจำลองตายตัว และสถิติ avg_response_time / conversion_rate เป็น 0 ตายตัว
 *   - ยังไม่มีช่องทางให้ลูกค้าคุยกับบอทของร้านจริง
 * → ทุกหน้า GET แสดงหน้า "ฟีเจอร์นี้ยังไม่เปิดให้บริการ" (V4) · คำสั่งบันทึก/ทดสอบตอบกลับอย่างสุภาพ ไม่แตะข้อมูล
 * เมนูผู้ขายไม่มีลิงก์มาหน้านี้ (config/menus.php)
 */
class AiAssistantController extends Controller
{
    /**
     * หน้าแจ้งว่าฟีเจอร์ยังไม่เปิด
     */
    protected function unavailable(): View
    {
        return view('seller.feature-unavailable', [
            'feature' => 'AI ผู้ช่วยขายของร้าน',
            'icon' => '🤖',
            'description' => 'ผู้ช่วย AI ที่ตอบแชทลูกค้าแทนร้านกำลังพัฒนาให้เชื่อมกับแชทจริงของร้าน ระหว่างนี้ใช้ “แชทกับลูกค้า” และน้อง Eve (ปุ่มผู้ช่วยมุมจอ) ถามยอดขาย/ออเดอร์ของร้านได้เลย',
            'backUrl' => \Illuminate\Support\Facades\Route::has('seller.messages.index') ? route('seller.messages.index') : route('seller.dashboard'),
            'backLabel' => \Illuminate\Support\Facades\Route::has('seller.messages.index') ? 'ไปหน้าแชทกับลูกค้า' : 'กลับแดชบอร์ดร้าน',
        ]);
    }

    public function index(Request $request): View
    {
        return $this->unavailable();
    }

    public function settings(Request $request): View
    {
        return $this->unavailable();
    }

    /**
     * บันทึกการตั้งค่า — ยังไม่เปิดให้บริการ (ไม่บันทึกข้อมูลใด ๆ)
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        return redirect()
            ->route('seller.dashboard')
            ->with('info', 'AI ผู้ช่วยขายยังไม่เปิดให้บริการ');
    }

    public function conversations(Request $request): View
    {
        return $this->unavailable();
    }

    public function showConversation(Request $request, $conversation): View
    {
        return $this->unavailable();
    }

    public function analytics(Request $request): View
    {
        return $this->unavailable();
    }

    /**
     * ทดสอบบอท — ยังไม่เปิดให้บริการ
     */
    public function testBot(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'FEATURE_UNAVAILABLE',
            'message' => 'AI ผู้ช่วยขายยังไม่เปิดให้บริการ',
            'data' => null,
        ], 503);
    }
}
