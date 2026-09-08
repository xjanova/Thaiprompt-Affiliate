<?php

namespace Tests\Feature;

use App\Models\FortuneHoroscopeContent;
use App\Services\FortuneHoroscopePublishService;
use App\Services\FortuneHoroscopeService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🎨 รูปดวงรายวันต้องหมุน ห้ามซ้ำของเดิม
 *
 * เหตุจริง 2026-09-08 — เจ้าของทักว่า "มันใช้ภาพเดิมซ้ำ ๆ กลัวว่ามันจะมองว่าเป็นสแปม"
 * ตรวจบน prod แล้วซ้ำจริงระดับ **ไบต์ต่อไบต์**:
 *   md5 ของรูปที่โพส 5, 6, 7, 8 ก.ย. = `0f4add1543886faafd2e20ab44f78bc6` ตัวเดียวกันหมด
 *
 * ต้นเหตุ 3 ชั้น:
 *   1. `FortuneHoroscopeService::generateImage()` เรียก provider โดย **ไม่ส่ง `seed`**
 *      — `PollinationsProvider` สุ่ม seed ให้เฉพาะภาพที่ 2 เป็นต้นไปของ batch (`$i > 0`)
 *        แต่เราขอ `num_images = 1` เสมอ ⇒ ไม่เคยมี seed เลย
 *        และ Pollinations เป็น deterministic ต่อ prompt ⇒ prompt เดิม = ภาพเดิมเป๊ะ
 *   2. prompt เดิมทุกวัน — template แทนค่าแค่ {birth_day_name}/{element}/{lucky_color}/
 *      {main_planet} ซึ่งทั้งหมดผูกกับ **วันเกิด ไม่ใช่วันที่**
 *   3. ปลายทางหยิบ `$imageUrls[0]` = การ์ดวันอาทิตย์ทุกวัน ⇒ ต่อให้เจนใหม่ก็โทนเดิม
 *
 * ⚠️ ไม่แตะ DB — สร้าง service ด้วย newInstanceWithoutConstructor()
 *    (constructor ของจริงเรียก FortuneTellingSetting::getSettings() ซึ่งต้องมีฐานข้อมูล)
 */
class HoroscopeImageRotationTest extends TestCase
{
    /**
     * 🎲 seed ต้องเปลี่ยนทุกวัน — ไม่งั้น Pollinations คืนภาพเดิม
     *
     * และต้องคงที่ภายในวันเดียวกัน เพื่อให้สั่ง regenerate ของวันเดิมได้ภาพเดิม
     */
    public function test_seed_ของรูป_ต้องเปลี่ยนทุกวัน_แต่คงที่ในวันเดียวกัน(): void
    {
        $imageSeed = $this->methodOf(FortuneHoroscopeService::class, 'imageSeed');
        $service = $this->serviceWithoutConstructor(FortuneHoroscopeService::class);

        $วันนี้ = Carbon::create(2026, 9, 8);
        $พรุ่งนี้ = Carbon::create(2026, 9, 9);

        $seedวันนี้ = $imageSeed->invoke($service, $วันนี้, 0);

        $this->assertSame(
            $seedวันนี้,
            $imageSeed->invoke($service, Carbon::create(2026, 9, 8, 23, 59), 0),
            'วันเดียวกันต้องได้ seed เดิม — ไม่งั้นสั่งเจนซ้ำแล้วได้คนละภาพ'
        );

        $this->assertNotSame(
            $seedวันนี้,
            $imageSeed->invoke($service, $พรุ่งนี้, 0),
            'ข้ามวันแล้ว seed ต้องเปลี่ยน ไม่งั้นได้ภาพเดิมซ้ำเหมือนเคส 5-8 ก.ย. 2569'
        );

        // แต่ละวันเกิดของวันเดียวกันก็ต้องคนละ seed (ไม่งั้นการ์ด 7 ใบเหมือนกันหมด)
        $this->assertNotSame(
            $seedวันนี้,
            $imageSeed->invoke($service, $วันนี้, 3),
            'คนละวันเกิดต้องคนละ seed'
        );

        // ยิงซ้ำตอนเจอรูปซ้ำ ต้องได้ seed คนละตัวกับครั้งแรก
        $this->assertNotSame(
            $seedวันนี้,
            $imageSeed->invoke($service, $วันนี้, 0, 1),
            'attempt ที่ 2 ต้องเปลี่ยน seed ไม่งั้นยิงซ้ำก็ได้ภาพเดิม'
        );

        // API รับ seed เป็น int32 — เกินแล้วโดนตัด/ปฏิเสธเงียบ
        $this->assertLessThan(
            2147483647,
            $imageSeed->invoke($service, Carbon::create(2030, 12, 31), 7, 1),
            'seed ต้องไม่ล้น int32 แม้ในปีอนาคต'
        );
    }

    /**
     * 🎠 สไตล์ + มุมภาพต้องหมุน — คู่เดิมห้ามกลับมาเร็วกว่า 84 วัน
     *
     * 12 สไตล์ × 7 มุมภาพ และตัวนับเดินทีละ 1 วัน ⇒ ค.ร.น. = 84
     */
    public function test_สไตล์ภาพต้องไม่ซ้ำคู่เดิมภายใน_84_วัน(): void
    {
        $append = $this->methodOf(FortuneHoroscopeService::class, 'appendDailyVariation');
        $service = $this->serviceWithoutConstructor(FortuneHoroscopeService::class);

        $เห็นแล้ว = [];
        $วันก่อนหน้า = null;

        for ($i = 0; $i < 84; $i++) {
            $วันที่ = Carbon::create(2026, 9, 8)->addDays($i);
            $ผลลัพธ์ = $append->invoke($service, 'base prompt', $วันที่, 0);

            $this->assertNotContains(
                $ผลลัพธ์,
                $เห็นแล้ว,
                "วันที่ {$วันที่->toDateString()} ได้สไตล์ซ้ำกับวันก่อนหน้าในรอบ 84 วัน"
            );

            $this->assertNotSame($วันก่อนหน้า, $ผลลัพธ์, 'สองวันติดกันต้องไม่ได้สไตล์เดียวกัน');

            $เห็นแล้ว[] = $ผลลัพธ์;
            $วันก่อนหน้า = $ผลลัพธ์;
        }

        // ต่อท้าย ไม่ทับ — template ที่แอดมินตั้งไว้ใน DB ต้องอยู่ครบ
        $this->assertStringStartsWith(
            'base prompt,',
            $append->invoke($service, 'base prompt', Carbon::create(2026, 9, 8), 0)
        );
    }

    /**
     * 🔒 ตัวเลือกที่ส่งให้ provider ต้องมี `seed`
     *
     * ตรวจที่ซอร์สโดยตรง เพราะนี่คือบรรทัดเดียวที่เคยหายไปแล้วทำให้ภาพซ้ำ 4 วันติด
     * โดยไม่มี error ที่ไหนเลย — refactor ครั้งหน้าที่ทำหล่นอีก จะโดนจับที่นี่
     */
    public function test_ตัวเลือกที่ส่งให้_provider_ต้องมี_seed(): void
    {
        $ซอร์ส = (string) file_get_contents(app_path('Services/FortuneHoroscopeService.php'));

        $this->assertMatchesRegularExpression(
            "/'num_images' => 1,\s*\n\s*'seed' => \\\$this->imageSeed\(/u",
            $ซอร์ส,
            'generateImage() ต้องส่ง seed ให้ provider ทุกครั้ง (Pollinations deterministic ต่อ prompt)'
        );
    }

    /**
     * 🖼️ รูปใบแรกของโพส = การ์ดของวันในสัปดาห์นั้น
     *
     * ปลายทางทั้ง FB (`/photos`) และ LINE (image message) หยิบ `$imageUrls[0]` เท่านั้น
     * ⇒ ใบแรกคือรูปเดียวที่คนเห็นจริง
     */
    public function test_รูปใบแรกของโพส_ต้องเป็นการ์ดของวันในสัปดาห์นั้น(): void
    {
        $order = $this->methodOf(FortuneHoroscopePublishService::class, 'orderImagesForToday');
        $service = new FortuneHoroscopePublishService;

        // 8 ก.ย. 2569 = วันอังคาร ⇒ birth_day 2
        $วันอังคาร = Carbon::create(2026, 9, 8);

        $ผลลัพธ์ = $order->invoke($service, $this->การ์ดครบเจ็ดใบ(), $วันอังคาร);

        $this->assertSame('https://x/day-2.jpg', $ผลลัพธ์[0], 'วันอังคารต้องขึ้นการ์ดวันอังคาร');
        $this->assertCount(7, $ผลลัพธ์, 'ใบที่เหลือต้องยังอยู่ครบ ไม่ใช่ถูกตัดทิ้ง');
        $this->assertSame(array_unique($ผลลัพธ์), $ผลลัพธ์, 'ห้ามมีรูปซ้ำในลิสต์');
    }

    /**
     * 🕳️ วันที่เจนรูปไม่สำเร็จ (image_url = null) ต้องไม่ทำให้ลำดับเลื่อน
     *
     * เคสจริง 8 ก.ย. 2569 — birth_day 6 (เสาร์) เจนรูปไม่ติด image_url เป็น null
     * ถ้า index ด้วยลำดับใน collection แทน birth_day จะหยิบการ์ดผิดวันทันที
     */
    public function test_การ์ดที่เจนรูปไม่ติด_ต้องไม่ทำให้หยิบผิดวัน(): void
    {
        $order = $this->methodOf(FortuneHoroscopePublishService::class, 'orderImagesForToday');
        $service = new FortuneHoroscopePublishService;

        $การ์ด = new Collection([
            new FortuneHoroscopeContent(['birth_day' => 0, 'image_url' => 'https://x/day-0.jpg']),
            new FortuneHoroscopeContent(['birth_day' => 1, 'image_url' => null]),
            new FortuneHoroscopeContent(['birth_day' => 2, 'image_url' => 'https://x/day-2.jpg']),
        ]);

        // 7 ก.ย. 2569 = วันจันทร์ (birth_day 1) ซึ่งเป็นใบที่รูปหาย
        $ผลลัพธ์ = $order->invoke($service, $การ์ด, Carbon::create(2026, 9, 7));

        $this->assertCount(2, $ผลลัพธ์, 'ใบที่ไม่มีรูปต้องถูกตัดออก ไม่ใช่ใส่ null ไปให้ FB');
        $this->assertNotContains(null, $ผลลัพธ์);

        // วันของตัวเองไม่มีรูป → ตกไปใช้ใบที่มีจริง (ไม่ใช่พังทั้งโพส)
        $this->assertSame('https://x/day-0.jpg', $ผลลัพธ์[0]);
    }

    /**
     * การ์ดครบ 7 วันเกิด พร้อมรูปทุกใบ
     */
    protected function การ์ดครบเจ็ดใบ(): Collection
    {
        return new Collection(array_map(
            fn (int $birthDay) => new FortuneHoroscopeContent([
                'birth_day' => $birthDay,
                'image_url' => "https://x/day-{$birthDay}.jpg",
            ]),
            range(0, 6)
        ));
    }

    /**
     * ดึงเมธอด protected ออกมาเรียกตรง ๆ
     */
    protected function methodOf(string $class, string $method): ReflectionMethod
    {
        $reflection = new ReflectionMethod($class, $method);
        $reflection->setAccessible(true);

        return $reflection;
    }

    /**
     * สร้าง service โดยข้าม constructor (constructor จริงต้องมี DB)
     */
    protected function serviceWithoutConstructor(string $class): object
    {
        return (new ReflectionClass($class))->newInstanceWithoutConstructor();
    }
}
