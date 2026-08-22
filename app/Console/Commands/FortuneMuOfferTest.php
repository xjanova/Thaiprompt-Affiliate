<?php

namespace App\Console\Commands;

use App\Models\FortuneProductOffer;
use App\Models\FortuneReading;
use App\Services\Fortune\FortuneMuOfferService;
use App\Services\Marketplace\MuOfferCardBuilder;
use App\Services\Marketplace\MuPickContext;
use App\Services\Marketplace\MuProductPicker;
use Illuminate\Console\Command;

/**
 * 🧪 ตรวจว่าระบบเสนอสินค้าของแม่หมอ "ทำงานจริง" ไหม
 *
 * 🚨 ทำไมต้องมีคำสั่งนี้ (บทเรียน 2026-08-23)
 *   `FortuneMuOfferService::offer()` ห่อ try/catch ทั้งก้อนโดยตั้งใจ —
 *   ขายของพังต้องไม่ทำให้ดูดวงพัง แต่ผลข้างเคียงคือ **ฟีเจอร์ตายสนิทได้โดยไม่มีอะไรดังเลย**
 *   ของจริง: ลืมใส่พารามิเตอร์ `$reading` ใน buildContext() ⇒ `Undefined variable $reading`
 *   ทุกครั้งที่เรียก ⇒ ไม่มีลูกค้าคนไหนได้การ์ดเลย แต่หน้าจอทุกอย่างปกติ
 *   เห็นได้จาก laravel.log บรรทัดเดียวเท่านั้น
 *
 *   ⇒ หลัง deploy ทุกครั้ง ให้รันคำสั่งนี้ อย่าดูแค่ว่า "ลูกค้าไม่เห็น error"
 *
 * Usage:
 *   php artisan fortune:mu-offer-test                       # ตรวจทุกจุด แบบไม่ส่งจริง
 *   php artisan fortune:mu-offer-test --reading=11461       # ใช้บริบทจากบิลจริง
 *   php artisan fortune:mu-offer-test --send --platform=facebook --user=PSID
 */
class FortuneMuOfferTest extends Command
{
    protected $signature = 'fortune:mu-offer-test
                            {--send : ส่งจริงเข้าแชท (ต้องระบุ --platform และ --user)}
                            {--platform=facebook : facebook | line}
                            {--user= : PSID/LINE userId ปลายทาง (ใช้กับ --send)}
                            {--trigger= : ทดสอบเฉพาะจุดนี้ (ไม่ระบุ = ทุกจุด)}
                            {--reading= : ใช้บริบทจาก reading id นี้}';

    protected $description = '🧪 ตรวจระบบเสนอสินค้าเสริมดวง — พิสูจน์ว่าเลือกของได้ + ประกอบการ์ดได้จริง';

    public function handle(FortuneMuOfferService $service, MuProductPicker $picker, MuOfferCardBuilder $cards): int
    {
        $platform = (string) $this->option('platform');
        $reading = $this->resolveReading();

        $triggers = $this->option('trigger')
            ? [(string) $this->option('trigger')]
            : [
                FortuneProductOffer::TRIGGER_CELTIC_END,
                FortuneProductOffer::TRIGGER_DEEP_END,
                FortuneProductOffer::TRIGGER_DAILY_FREE,
                FortuneProductOffer::TRIGGER_PITCH_DECLINED,
                FortuneProductOffer::TRIGGER_CHAT_END,
                FortuneProductOffer::TRIGGER_CUSTOMER_ASK,
            ];

        $this->line('');
        $this->info('🧪 ตรวจระบบเสนอสินค้าเสริมดวง'.($reading ? " (บริบทจาก reading #{$reading->id})" : ''));
        $this->line('');

        $failures = 0;

        foreach ($triggers as $trigger) {
            $on = $service->isEnabled($trigger);
            $this->line(sprintf('── %-16s %s', $trigger, $on ? '✅ เปิด' : '⬜ ปิด'));

            // ใช้ id ปลอมต่อ trigger — กันตัวกันส่งซ้ำ/เพดานรายวันของคนจริงมาเบี่ยงผล
            $probeUser = 'MUTEST_'.strtoupper(substr(md5($trigger), 0, 10));

            $failures += $this->probeOne($picker, $cards, $platform, $probeUser, $trigger, $reading);
        }

        $this->line('');

        if ($this->option('send')) {
            $failures += $this->sendForReal($service, $platform, $reading);
        }

        if ($failures > 0) {
            $this->error("❌ มีปัญหา {$failures} จุด — ดูรายละเอียดด้านบน");

            return self::FAILURE;
        }

        $this->info('✅ ทุกจุดเลือกของได้ + ประกอบการ์ดได้จริง');
        $this->line('   (ยังไม่ได้ส่งเข้าแชท — ใช้ --send --user=... เพื่อทดสอบส่งจริง)');

        return self::SUCCESS;
    }

    /**
     * ลองเลือกของ + ประกอบการ์ด สำหรับ 1 จุดยิง
     *
     * @return int จำนวนปัญหาที่เจอ
     */
    private function probeOne(
        MuProductPicker $picker,
        MuOfferCardBuilder $cards,
        string $platform,
        string $probeUser,
        string $trigger,
        ?FortuneReading $reading
    ): int {
        try {
            $ctx = $trigger === FortuneProductOffer::TRIGGER_CUSTOMER_ASK
                ? MuPickContext::customerAsk($platform, $probeUser, 'ปี่เซี้ยะ')
                : MuPickContext::proactive($platform, $probeUser);

            if ($reading) {
                $topic = FortuneMuOfferService::topicTextOf($reading);
                if ($topic !== '') {
                    $ctx = $ctx->withTopic($topic);
                }
                $ctx = $ctx->withBirthYear(FortuneMuOfferService::birthYearOf($reading));
            }

            $picked = $picker->pick($ctx);

            if (empty($picked['items'])) {
                $this->warn("   ⚠️  ไม่มีของที่ผ่านเกณฑ์ — {$picked['reason']}");

                return 1;
            }

            $this->line('   กลุ่ม='.($picked['group'] ?? 'ทุกกลุ่ม').' · เหตุผล='.$picked['reason']);

            foreach ($picked['items'] as $item) {
                $p = $item['product'];
                $this->line(sprintf(
                    '   [%-4s] %6sB %3s%% = %6sB | %s',
                    $item['slot'],
                    round((float) $p->price),
                    round((float) $p->commission_rate),
                    round($picker->expectedEarning($p), 1),
                    mb_substr((string) $p->name, 0, 44)
                ));
            }

            // ประกอบการ์ดจริงทั้งสองแพลตฟอร์ม — จุดที่พังบ่อยคือรูป/ชื่อว่าง
            $fb = $cards->facebookTemplate($picked['items']);
            $line = $cards->lineFlex($picked['items']);
            $plain = $cards->plainTextFallback($picked['items']);

            $fbCount = count($fb['attachment']['payload']['elements'] ?? []);
            $lineCount = ($line['type'] ?? '') === 'carousel' ? count($line['contents'] ?? []) : (empty($line) ? 0 : 1);

            $problems = 0;
            if ($fbCount !== count($picked['items'])) {
                $this->error("   ❌ การ์ด FB ได้ {$fbCount} ใบ จากของ ".count($picked['items']).' ชิ้น (รูปหรือชื่อมีปัญหา)');
                $problems++;
            }
            if ($lineCount !== count($picked['items'])) {
                $this->error("   ❌ การ์ด LINE ได้ {$lineCount} ใบ จากของ ".count($picked['items']).' ชิ้น');
                $problems++;
            }
            if (trim($plain) === '') {
                $this->error('   ❌ ข้อความสำรองว่าง — นอกกรอบ 24 ชม. ลูกค้าจะไม่ได้อะไรเลย');
                $problems++;
            }

            // ชื่อว่าง = Facebook ปฏิเสธทั้งการ์ด (เคยพังมาแล้ว)
            foreach ($fb['attachment']['payload']['elements'] ?? [] as $el) {
                if (trim((string) ($el['title'] ?? '')) === '') {
                    $this->error('   ❌ การ์ด FB มีใบที่ title ว่าง — FB จะปฏิเสธทั้งกล่อง');
                    $problems++;
                }
            }

            if ($problems === 0) {
                $this->line("   การ์ด: FB {$fbCount} ใบ · LINE {$lineCount} ใบ · สำรอง ".mb_strlen($plain).' ตัวอักษร  ✅');
            }

            return $problems;
        } catch (\Throwable $e) {
            // 🚨 จุดสำคัญ: ที่นี่ **ไม่กลืน** ข้อผิดพลาด — ต่างจาก offer() ที่กลืนโดยตั้งใจ
            $this->error('   ❌ ระเบิด: '.$e->getMessage());
            $this->line('      '.$e->getFile().':'.$e->getLine());

            return 1;
        }
    }

    /**
     * ส่งจริงเข้าแชท (ต้องระบุ --user)
     *
     * @return int จำนวนปัญหา
     */
    private function sendForReal(FortuneMuOfferService $service, string $platform, ?FortuneReading $reading): int
    {
        $user = trim((string) $this->option('user'));
        if ($user === '') {
            $this->error('❌ --send ต้องระบุ --user=<PSID หรือ LINE userId> ด้วย');

            return 1;
        }

        $trigger = (string) ($this->option('trigger') ?: FortuneProductOffer::TRIGGER_CHAT_END);

        $this->warn("📤 กำลังส่งจริงถึง {$platform}:{$user} (trigger={$trigger})...");

        $ok = $service->offer($platform, $user, $trigger, $reading);

        if ($ok) {
            $this->info('✅ ส่งสำเร็จ — ไปดูในแชทได้เลย');

            return 0;
        }

        $this->error('❌ ส่งไม่สำเร็จ — เช็ค laravel.log บรรทัด MuOffer');
        $this->line('   สาเหตุที่พบบ่อย: สวิตช์ปิด · จุดยิงนี้ไม่อยู่ในลิสต์ · เกินเพดานรายวัน · ลูกค้าถูกสั่งเงียบ');

        return 1;
    }

    /**
     * หา reading ที่จะใช้เป็นบริบท
     */
    private function resolveReading(): ?FortuneReading
    {
        $id = $this->option('reading');

        return $id ? FortuneReading::find((int) $id) : null;
    }
}
