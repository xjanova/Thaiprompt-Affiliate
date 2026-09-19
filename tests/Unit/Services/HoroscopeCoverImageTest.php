<?php

namespace Tests\Unit\Services;

use App\Services\FortuneHoroscopePublishService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 🖼️ รูปหน้าโพสดวงรายวัน = ใบของ "วันในสัปดาห์ที่โพสวันนั้น" ใบเดียว (2026-09-19)
 *
 * เจ้าของทัก: "รูปสร้างใช้ประกอบการโพส แค่รูปเดียวพอสิ สร้างทำไม 8 ใบ"
 * ตรวจแล้วจริง — `publishToFacebook()` และ `publishToLine()` ใช้ `$imageUrls[0]` ทั้งคู่
 * ⇒ FortuneHoroscopeService เจนรูปเฉพาะ birth_day ที่ตรงกับ dayOfWeek ของวันนั้น
 *
 * เทสต์นี้ล็อก **สัญญาระหว่าง 2 service**: ฝั่งเจนสร้างใบเดียว ฝั่งโพสต้องหยิบใบนั้นได้
 * ถ้าใครเผลอเปลี่ยนลำดับใน orderImagesForToday() รูปหน้าฟีดจะกลายเป็นโทนวันผิด
 * (เคยเกิดมาแล้ว — แก้ไว้ 2026-09-08 ตอนหน้าฟีดเป็นโทนวันอาทิตย์ทุกวัน)
 */
class HoroscopeCoverImageTest extends TestCase
{
    /** เรียก protected orderImagesForToday() ตรง ๆ */
    private function order(Collection $contents, Carbon $date): array
    {
        $svc = new FortuneHoroscopePublishService;
        $m = new \ReflectionMethod($svc, 'orderImagesForToday');
        $m->setAccessible(true);

        return $m->invoke($svc, $contents, $date);
    }

    /** @param array<int, ?string> $imagesByBirthDay */
    private function contents(array $imagesByBirthDay): Collection
    {
        return collect($imagesByBirthDay)->map(function ($url, $birthDay) {
            $c = new \stdClass;
            $c->birth_day = $birthDay;
            $c->image_url = $url;

            return $c;
        })->values();
    }

    #[Test]
    public function only_the_weekday_card_is_needed_and_it_becomes_the_cover(): void
    {
        // 2026-09-19 = วันเสาร์ (dayOfWeek 6) — เจนรูปแค่ใบนี้ใบเดียว
        $saturday = Carbon::parse('2026-09-19');
        $this->assertSame(6, $saturday->dayOfWeek, 'สมมติฐานของเทสต์: 19 ก.ย. 2026 เป็นวันเสาร์');

        $urls = $this->order($this->contents([
            0 => null, 1 => null, 2 => null, 3 => null,
            4 => null, 5 => null, 6 => 'https://x/saturday.jpg', 7 => null,
        ]), $saturday);

        $this->assertSame(['https://x/saturday.jpg'], $urls, 'ใบเดียวพอ และต้องเป็นใบของวันนั้น');
    }

    #[Test]
    public function the_weekday_card_stays_first_even_when_older_days_still_have_images(): void
    {
        // ช่วงเปลี่ยนผ่าน: แถวเก่าที่เจนครบ 8 ใบยังอยู่ในระบบ — ใบของวันนั้นต้องยังมาก่อน
        $saturday = Carbon::parse('2026-09-19');

        $urls = $this->order($this->contents([
            0 => 'https://x/sunday.jpg',
            6 => 'https://x/saturday.jpg',
            7 => 'https://x/rahu.jpg',
        ]), $saturday);

        $this->assertSame('https://x/saturday.jpg', $urls[0], 'รูปหน้าโพสต้องเป็นใบของวันในสัปดาห์นั้น');
        $this->assertCount(3, $urls);
    }

    #[Test]
    public function no_image_at_all_yields_an_empty_list_so_the_post_falls_back_to_text(): void
    {
        // ใบเดียวที่เจนล้มเหลว = โพสไม่มีรูป → publishToFacebook ตกไปใช้ /feed แทน /photos
        // (และ TelegramAlertService จะเตือนแอดมินจาก FortuneHoroscopeService::alertOnImageFailures)
        $urls = $this->order($this->contents([0 => null, 6 => null]), Carbon::parse('2026-09-19'));

        $this->assertSame([], $urls);
    }

    #[Test]
    public function a_wednesday_night_card_never_becomes_the_cover(): void
    {
        // birth_day 7 = พุธกลางคืน/ราหู — ไม่มีวันตรงกับ dayOfWeek (0-6)
        $wednesday = Carbon::parse('2026-09-16');
        $this->assertSame(3, $wednesday->dayOfWeek);

        $urls = $this->order($this->contents([
            3 => 'https://x/wednesday.jpg',
            7 => 'https://x/rahu.jpg',
        ]), $wednesday);

        $this->assertSame('https://x/wednesday.jpg', $urls[0]);
    }
}
