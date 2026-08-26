<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * FortuneEntryCard — ค่าที่แอดมินแก้ได้ของ "การ์ดทางเข้า" (รูป + คำบนการ์ด)
 *
 * เก็บเฉพาะ "ของที่ถูกทับ" — ไม่มีแถว / ช่องว่าง = ใช้ค่าเดิมที่ฝังมากับโค้ด
 * ⇒ ไม่ต้อง seed · เพิ่มการ์ดใบใหม่ในโค้ดแล้วหน้าแอดมินขึ้นเองทันที
 *
 * 🚨 **รูปที่อัปทับต้องอยู่บน disk `public` (storage/app/public/) เท่านั้น**
 *    deploy.sh:814 รัน `git clean -fdx -e 'storage/app/public/*' …`
 *    ⇒ ถ้าอัปทับลง public/images/ (ซึ่งอยู่ใน git) รูปจะโดนคืนค่าเดิมทุก deploy
 *    เป็นบั๊กเดียวกับที่เคยทำให้รูปสลิปหายทุก deploy (INCIDENT 2026-06-05)
 *
 * @property string $card_key
 * @property string|null $image_path path บน disk public — null = ใช้รูปเดิมในโค้ด
 * @property string|null $title
 * @property string|null $subtitle
 * @property string|null $button_label
 * @property string $text_mode 'invite' = ย่อจากคำ DM ที่หมุนอยู่ · 'custom' = ใช้คำที่พิมพ์เอง
 */
class FortuneEntryCard extends Model
{
    /** โฟลเดอร์เก็บรูปที่อัปทับ (บน disk `public`) */
    public const UPLOAD_DIR = 'fortune/cards';

    /** ใช้คำที่ย่อมาจากข้อความชวนที่หมุนอยู่ในคลัง */
    public const MODE_INVITE = 'invite';

    /** ใช้คำที่แอดมินพิมพ์เอง */
    public const MODE_CUSTOM = 'custom';

    protected $table = 'fortune_entry_cards';

    protected $fillable = [
        'card_key',
        'image_path',
        'title',
        'subtitle',
        'button_label',
        'text_mode',
    ];

    /**
     * ดึงค่าที่ทับไว้ทั้งหมด คีย์ด้วย card_key — เรียกครั้งเดียวแล้วส่งต่อ
     *
     * @return \Illuminate\Support\Collection<string, static>
     */
    public static function overrides(): \Illuminate\Support\Collection
    {
        try {
            return static::all()->keyBy('card_key');
        } catch (\Throwable $e) {
            // ยังไม่ได้ migrate บน prod → ต้องไม่ทำให้การ์ดพังทั้งระบบ
            return collect();
        }
    }

    /**
     * URL รูปที่อัปทับ — null = ยังไม่เคยอัป ให้ caller ใช้รูปเดิมในโค้ด
     *
     * ⏱️ ต่อ `?v={mtime}` ไว้ด้วย — Facebook แคชรูปตาม URL
     *    อัปรูปใหม่ทับ path เดิมโดยไม่เปลี่ยน URL = ลูกค้ายังเห็นรูปเก่าไปอีกนาน
     */
    public function overrideImageUrl(): ?string
    {
        if (empty($this->image_path)) {
            return null;
        }

        try {
            $disk = Storage::disk('public');

            if (! $disk->exists($this->image_path)) {
                return null;
            }

            $url = asset('storage/'.ltrim($this->image_path, '/'));

            if (str_starts_with($url, 'http://')) {
                $url = 'https://'.substr($url, 7);
            }

            if (! str_starts_with($url, 'https://')) {
                return null;
            }

            return $url.'?v='.$disk->lastModified($this->image_path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * คำที่แอดมินพิมพ์ทับ — คืน null ถ้าเว้นว่าง (= ใช้ค่าเดิม)
     */
    public function overrideText(string $field): ?string
    {
        $value = trim((string) ($this->{$field} ?? ''));

        return $value === '' ? null : $value;
    }

    /**
     * ใช้คำที่พิมพ์เองแทนคำ DM ที่หมุนอยู่หรือไม่
     */
    public function usesCustomText(): bool
    {
        return $this->text_mode === self::MODE_CUSTOM;
    }
}
