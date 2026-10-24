<?php

namespace App\Jobs;

use App\Services\Fortune\FortunePredictionMenu;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 🔮 (2026-10-24) สลับ Rich Menu LINE ของลูกค้าคนเดียว ตามสถานะการทำนาย
 *
 * แยกเป็น job เพราะครั้งแรกหลัง deploy เมนูหลัก ต้องวาดภาพ + อัปโหลดเมนูช่วงทำนาย (หลายวินาที)
 * — ห้ามหน่วงคำตอบลูกค้า · ล้ม = ลูกค้ายังเห็นเมนูเดิม ข้อความเตือนใน Hard Guard ยังทำงาน
 */
class SyncFortunePredictionMenuJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public string $lineUserId) {}

    public function handle(FortunePredictionMenu $menu): void
    {
        try {
            $menu->sync($this->lineUserId);
        } catch (\Throwable $e) {
            Log::warning('SyncFortunePredictionMenuJob: ล้ม (non-blocking)', [
                'user_id' => $this->lineUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
