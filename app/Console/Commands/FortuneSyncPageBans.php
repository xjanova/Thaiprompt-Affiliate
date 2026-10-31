<?php

namespace App\Console\Commands;

use App\Models\FortuneUserBan;
use App\Services\FortuneBanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class FortuneSyncPageBans extends Command
{
    protected $signature = 'fortune:sync-page-bans
                            {--execute : บล็อก/ปลดบล็อกบนเพจจริง ไม่ใส่จะตรวจรายการอย่างเดียว}
                            {--limit=50 : จำนวนสูงสุดต่อรอบ}';

    protected $description = 'ตามการแบนบอทให้ครบถึงเพจ Facebook และปลดบล็อกแบนที่หมดอายุ';

    public function handle(FortuneBanService $service): int
    {
        if (! Schema::hasColumn('fortune_user_bans', 'page_block_status')) {
            $this->error('กรุณารัน migration สถานะการบล็อกเพจก่อน');

            return self::FAILURE;
        }

        $limit = max(1, min(500, (int) $this->option('limit')));
        // API ล้มเหลวต้องเว้นอย่างน้อย 15 นาที ป้องกันยิงซ้ำถี่เมื่อ token หมดอายุ
        // Facebook จำกัดเพจ (code 368) ต้องพักนานกว่านั้น ยิงซ้ำจะยิ่งโดนนาน
        $due = fn ($query) => $query->whereNull('page_block_attempted_at')
            ->orWhere('page_block_attempted_at', '<=', now()->subHours(FortuneUserBan::PAGE_LIMITED_RETRY_HOURS))
            ->orWhere(fn ($q) => $q->where('page_block_attempted_at', '<=', now()->subMinutes(15))
                ->where(fn ($q) => $q->whereNull('page_block_error')
                    ->orWhere('page_block_error', 'not like', '%'.FortuneUserBan::PAGE_LIMITED_ERROR.'%')));

        $release = FortuneUserBan::query()
            ->where(fn ($q) => $q->where('page_block_status', 'unblock_failed')
                ->orWhere(fn ($q) => $q->expired()))
            ->where(fn ($q) => $q->whereNull('page_block_status')
                ->orWhere('page_block_status', '!=', 'unblock_failed')->orWhere($due))
            ->orderBy('page_block_attempted_at')->limit($limit)->get();

        $block = FortuneUserBan::forPlatform('facebook')->active()
            ->where(fn ($q) => $q->whereNull('page_block_status')
                ->orWhereIn('page_block_status', ['pending', 'failed']))
            ->where($due)->orderBy('page_block_attempted_at')->limit($limit)->get();

        $this->info('รอบนี้: รอบล็อกจากเพจ '.$block->count().' ราย / รอปลดแบน '.$release->count().' ราย');
        if (! $this->option('execute')) {
            $this->warn('ตรวจอย่างเดียว — ใช้ --execute เพื่อดำเนินการจริง');

            return self::SUCCESS;
        }

        $failed = 0;
        foreach ($release as $ban) {
            if (! $service->unban($ban->platform, $ban->platform_user_id)) {
                $failed++;
            }
        }
        // แถวแรกที่เจอ code 368 พักทั้งเพจแล้ว แถวที่เหลือของเพจนั้นห้ามยิงต่อในรอบนี้
        $limitedPages = [];
        foreach ($block as $ban) {
            if (isset($limitedPages[(int) $ban->fortune_page_id])) {
                continue;
            }
            if (! $service->syncPageBlock($ban)) {
                $failed++;
                if ($ban->isPageBlockLimited()) {
                    $limitedPages[(int) $ban->fortune_page_id] = true;
                }
            }
        }

        $this->info('ดำเนินการแล้ว · ไม่สำเร็จ '.$failed.' ราย (เก็บไว้ลองใหม่รอบถัดไป)');

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
