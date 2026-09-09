<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ⚡ กวาดดีล Lazada ตามคำสั่งของแอดมิน (ปุ่ม "กวาดเดี๋ยวนี้" ในหลังบ้าน)
 *
 * ทำไมต้องผ่านคิว ไม่ยิงในคำขอเว็บตรง ๆ:
 *   การกวาดยิงหน้ารายการ Lazada 21 คำค้น + ขอลิงก์ค่าคอมทีละชิ้น ⇒ ใช้เวลา 1-5 นาที
 *   นานเกินอายุคำขอ php-fpm ⇒ แอดมินจะเห็นหน้าค้างแล้ว 504 ทั้งที่งานยังวิ่งอยู่
 *
 * 🔑 `$timeout = 900` สำคัญมาก — worker บนพร็อดรันด้วย `--timeout=120`
 *    ซึ่งเป็นแค่ "ค่าปริยายเมื่อ job ไม่ได้กำหนดเอง" (Worker::timeoutForJob)
 *    ถ้าไม่ประกาศไว้ที่นี่ งานจะโดนฆ่ากลางคันที่ 2 นาทีทุกครั้ง แล้ว retry ซ้ำ
 *
 * 🔒 `$tries = 1` — งานนี้ยิง API ภายนอกและเขียนสินค้าจริง ล้มแล้วให้แอดมินกดเอง
 *    ดีกว่าปล่อยให้ retry อัตโนมัติแล้วยิง Lazada ซ้ำ 3 รอบ (เสี่ยงโดนกันบอท)
 */
class ScanLazadaDealsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** กันกดซ้ำ — คีย์ล็อกร่วมกับฝั่ง controller */
    public const LOCK_KEY = 'lazada:scan-deals:running';

    /** อายุล็อก (วินาที) — ยาวกว่าเวลาที่งานใช้จริงเล็กน้อย */
    public const LOCK_TTL = 900;

    public $tries = 1;

    public $timeout = 900;

    public function __construct(public ?int $limit = null) {}

    public function handle(): void
    {
        $options = [];
        if ($this->limit !== null && $this->limit > 0) {
            $options['--limit'] = (string) $this->limit;
        }

        try {
            Artisan::call('lazada:scan-deals', $options);
            Log::info('lazada:scan-deals (จากหลังบ้าน) เสร็จแล้ว', [
                'output' => mb_substr(Artisan::output(), -1500),
            ]);
        } catch (\Throwable $e) {
            Log::error('lazada:scan-deals (จากหลังบ้าน) ล้มเหลว', ['error' => $e->getMessage()]);
            throw $e;
        } finally {
            // ⚠️ ต้องปลดใน finally — ล้มแล้วไม่ปลด แอดมินจะกดใหม่ไม่ได้จนกว่าล็อกจะหมดอายุ 15 นาที
            Cache::forget(self::LOCK_KEY);
        }
    }

    /**
     * งานตายก่อนถึง finally (เช่น worker ถูกฆ่า) — ปลดล็อกให้ด้วย
     */
    public function failed(?\Throwable $e): void
    {
        Cache::forget(self::LOCK_KEY);
    }
}
