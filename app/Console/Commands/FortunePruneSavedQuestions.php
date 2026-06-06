<?php

namespace App\Console\Commands;

use App\Models\FortuneSavedQuestion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ล้างคำถามรอแอดมิน (fortune_saved_questions) เพื่อประหยัด DB
 *
 * นโยบาย — ลบเฉพาะที่ "จบงาน" หรือ "เป็น noise"
 *   1) noise: reason ∈ (ai_failed, fallback) + ยังไม่ตอบ + เก่ากว่า --failed-hours ชม.
 *      → เกิดจาก AI error / ไม่ match คำใดเลย ลูกค้าไม่ได้กดขอแอดมิน = ไม่มีค่า
 *   2) done:  ตอบแล้ว + ส่งถึงผู้ใช้แล้ว + เก่ากว่า --replied-days วัน
 *      → ตอนแอดมินกดตอบ ระบบ capture Q&A เข้า fortune_admin_qa (RAG) ไว้แล้ว
 *        ความรู้ไม่หายแม้ลบ row นี้ทิ้ง
 *
 * ⛔ ไม่แตะ (เพื่อ "คัดกรองเฉพาะที่ลูกค้าอยากคุยแอดมินจริงๆ"):
 *   - pending ที่ลูกค้าฝากเอง (ai_cannot_answer / user_initiated) ที่ยังไม่ตอบ
 *   - replied ที่ส่งไม่สำเร็จ (is_sent_to_user = false) — ยังกด "ส่งซ้ำ" ได้
 *
 * รันโดย scheduler รายวัน (routes/console.php) — ไม่ใช่ Kernel.php (L11 = dead code)
 */
class FortunePruneSavedQuestions extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fortune:prune-saved-questions
        {--failed-hours=48 : ลบ noise (ai_failed/fallback ที่ยังไม่ตอบ) เก่ากว่า N ชม.}
        {--replied-days=7 : ลบที่ตอบแล้ว+ส่งถึงผู้ใช้แล้ว เก่ากว่า N วัน}
        {--dry-run : แสดงจำนวนที่จะลบ โดยไม่ลบจริง}';

    /**
     * @var string
     */
    protected $description = 'ล้างคำถามรอแอดมินที่จบงาน/เป็น noise (ประหยัด DB — RAG เก็บความรู้ไว้แล้ว)';

    /**
     * รันคำสั่ง prune
     */
    public function handle(): int
    {
        $failedHours = max(1, (int) $this->option('failed-hours'));
        $repliedDays = max(1, (int) $this->option('replied-days'));
        $dryRun = (bool) $this->option('dry-run');

        $noiseCutoff = now()->subHours($failedHours);
        $repliedCutoff = now()->subDays($repliedDays);

        // 1) noise — AI error/fallback ที่ลูกค้าไม่ได้ขอแอดมิน + ไม่เคยตอบ
        $noiseQuery = FortuneSavedQuestion::query()
            ->whereIn('reason', ['ai_failed', 'fallback'])
            ->where('is_replied', false)
            ->where('created_at', '<', $noiseCutoff);

        // 2) done — ตอบแล้ว + ส่งถึงผู้ใช้แล้ว (RAG เก็บ Q&A ไว้แล้ว)
        //    ใช้ COALESCE(replied_at, created_at) กันเคส replied_at เป็น null
        $doneQuery = FortuneSavedQuestion::query()
            ->where('is_replied', true)
            ->where('is_sent_to_user', true)
            ->whereRaw('COALESCE(replied_at, created_at) < ?', [$repliedCutoff]);

        $noiseCount = (clone $noiseQuery)->count();
        $doneCount = (clone $doneQuery)->count();

        $this->info('🧹 Prune saved questions');
        $this->line("  • noise (ai_failed/fallback, ไม่ตอบ, > {$failedHours}h): {$noiseCount}");
        $this->line("  • done  (replied+sent, > {$repliedDays}d): {$doneCount}");

        if ($dryRun) {
            $this->warn('  (dry-run — ไม่ลบจริง)');

            return self::SUCCESS;
        }

        $deletedNoise = $this->deleteInChunks($noiseQuery);
        $deletedDone = $this->deleteInChunks($doneQuery);
        $total = $deletedNoise + $deletedDone;

        $this->info("✅ ลบแล้ว {$total} แถว (noise={$deletedNoise}, done={$deletedDone})");

        Log::info('FortunePruneSavedQuestions: เสร็จสิ้น', [
            'noise_deleted' => $deletedNoise,
            'done_deleted' => $deletedDone,
            'failed_hours' => $failedHours,
            'replied_days' => $repliedDays,
        ]);

        return self::SUCCESS;
    }

    /**
     * ลบทีละ chunk — กัน lock ตารางนาน เมื่อมีข้อมูลค้างเยอะ
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function deleteInChunks($query, int $chunk = 500): int
    {
        $deleted = 0;

        do {
            $ids = (clone $query)->limit($chunk)->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += FortuneSavedQuestion::whereIn('id', $ids)->delete();
        } while ($ids->count() === $chunk);

        return $deleted;
    }
}
