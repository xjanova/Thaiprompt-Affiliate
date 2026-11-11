<?php

namespace App\Jobs;

use App\Models\PersonalPostDraft;
use App\Services\PersonalPost\PersonalPostPrepService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * งานสร้างโพสต์ส่วนตัวที่สั่งจากหน้าหลังบ้าน
 *
 * ใช้ dispatchAfterResponse() — ตอบหน้าเว็บทันทีแล้วค่อยสร้างต่อใน process เดิม
 * (สร้างภาพ 4 ช่องใช้เวลาหลายนาที ไม่ต้องพึ่ง queue worker)
 *
 * โหมด:
 *  - day   : เตรียมทั้งวัน ($force = เลือกหัวข้อใหม่ทุกช่อง)
 *  - text  : เขียนบทความใหม่ของช่องเดียว (ภาพเดิม)
 *  - image : สร้างภาพใหม่ของช่องเดียว
 *  - both  : สร้างใหม่ทั้งบทความและภาพของช่องเดียว
 */
class PreparePersonalPostsJob
{
    use Dispatchable;

    public function __construct(
        public string $mode,
        public ?string $date = null,
        public ?int $draftId = null,
        public bool $force = false,
    ) {}

    /**
     * คีย์ล็อกกันกดซ้ำ (double-tap) ระหว่างกำลังสร้าง
     */
    public static function lockKey(string $date): string
    {
        return "personal-posts:prepare:{$date}";
    }

    public function handle(PersonalPostPrepService $service): void
    {
        @set_time_limit(1200);
        @ignore_user_abort(true);

        try {
            if ($this->mode === 'day') {
                $date = Carbon::parse($this->date ?? now()->toDateString());
                try {
                    $service->prepareDay($date, $this->force);
                } finally {
                    Cache::forget(self::lockKey($date->toDateString()));
                }

                return;
            }

            $draft = PersonalPostDraft::find($this->draftId);
            if (! $draft) {
                return;
            }

            $service->generate(
                $draft,
                text: in_array($this->mode, ['text', 'both'], true),
                image: in_array($this->mode, ['image', 'both'], true),
            );
        } catch (Throwable $e) {
            Log::warning('PersonalPost: job ล้ม', [
                'mode' => $this->mode,
                'date' => $this->date,
                'draft_id' => $this->draftId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
