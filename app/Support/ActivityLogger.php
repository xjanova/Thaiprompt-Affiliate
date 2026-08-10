<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * 📝 ตัวบันทึกกิจกรรมของแอดมิน — ตัวแทน spatie/laravel-activitylog ที่ไม่เคยถูกติดตั้ง
 *
 * 🚨 ที่มา (2026-08-10): โค้ดใน app/ เรียก `activity()` อยู่ **49 จุดใน 16 ไฟล์**
 *    (ECommerce, HDWalletManagement, TokenManagement, PosDevice, PosTransaction,
 *     LicenseKeyService, DeveloperApproval, AntiAbuse, KeywordABTest, SentimentAnalysis ฯลฯ)
 *    แต่ `spatie/laravel-activitylog` **ไม่เคยอยู่ใน composer.json เลยสักครั้ง**
 *    (ยืนยันด้วย `git log -S activitylog -- composer.json` = ไม่มีผลลัพธ์)
 *
 *    แพทเทิร์นนี้มาจากเทมเพลต Service ใน CLAUDE.md ที่เขียนตัวอย่างว่า
 *    `activity()->performedOn($feature)->log('...')` — ก็อปตามกันมาโดยไม่มีของจริงรองรับ
 *
 *    ผลคือ **ทุกจุดที่เรียกถึงจะ fatal 500 ทันที** ("Call to undefined function activity()")
 *    ไม่ใช่แค่ log หาย แต่ธุรกรรมทั้งก้อนล้ม เพราะหลายจุดอยู่ใน DB::transaction
 *    ซ่อนอยู่ได้นานเพราะ ci.yml ตั้ง `continue-on-error: true` ที่สเต็ป phpunit
 *
 * ⚖️ ทำไมเลือกเขียนตัวแทน แทนที่จะ `composer require spatie/laravel-activitylog`:
 *    แพคเกจนั้นต้องมี migration สร้างตาราง `activity_log` บน prod ซึ่งเป็นการเปลี่ยน
 *    โครงฐานข้อมูลจริงของลูกค้า — เป็นการตัดสินใจของเจ้าของ ไม่ใช่ผลพลอยได้ของการซ่อมเทสต์
 *    ตัวนี้จึงเก็บ "เจตนาการ audit" ไว้ครบโดยเขียนลง log channel ปกติแทน
 *    ถ้าวันหนึ่งติดตั้งแพคเกจจริง ให้ลบไฟล์นี้ + helper ออก API เข้ากันได้อยู่แล้ว
 *
 * 🚨 กฎเหล็ก: **ห้าม throw** — การบันทึก audit ต้องไม่ทำให้การกระทำทางธุรกิจล้ม
 *    (นี่คือบั๊กเดิมเป๊ะ ๆ ที่กำลังแก้อยู่)
 */
class ActivityLogger
{
    /** ชื่อ log (spatie เรียก log name) — เก็บไว้เพื่อความเข้ากันได้ */
    protected ?string $logName;

    /** โมเดลที่ถูกกระทำ */
    protected ?Model $subject = null;

    /** ผู้กระทำ — ไม่ระบุ = คนที่ล็อกอินอยู่ */
    protected mixed $causer = null;

    /** ข้อมูลประกอบเพิ่มเติม */
    protected array $properties = [];

    public function __construct(?string $logName = null)
    {
        $this->logName = $logName;
    }

    /**
     * ระบุโมเดลที่ถูกกระทำ
     */
    public function performedOn(?Model $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /** alias ของ performedOn (spatie มีทั้งคู่) */
    public function on(?Model $subject): static
    {
        return $this->performedOn($subject);
    }

    /**
     * ระบุผู้กระทำ (User หรือ id)
     */
    public function causedBy(mixed $causer): static
    {
        $this->causer = $causer;

        return $this;
    }

    /** alias ของ causedBy */
    public function by(mixed $causer): static
    {
        return $this->causedBy($causer);
    }

    /**
     * แนบข้อมูลประกอบ
     */
    public function withProperties(array $properties): static
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * แนบข้อมูลประกอบทีละคีย์
     */
    public function withProperty(string $key, mixed $value): static
    {
        $this->properties[$key] = $value;

        return $this;
    }

    /**
     * เปลี่ยนชื่อ log
     */
    public function useLog(?string $logName): static
    {
        $this->logName = $logName;

        return $this;
    }

    /**
     * ระบุชนิดเหตุการณ์ (created/updated/deleted ฯลฯ)
     */
    public function event(string $event): static
    {
        $this->properties['event'] = $event;

        return $this;
    }

    /**
     * บันทึกจริง — ปลายทางคือ log channel ปกติของแอป
     *
     * ⚠️ กลืน exception ทุกชนิดโดยตั้งใจ ห้ามให้การเขียน audit ล้มการทำงานหลัก
     */
    public function log(string $description): void
    {
        try {
            Log::channel(config('logging.default'))->info('📝 activity: '.$description, array_filter([
                'log_name' => $this->logName,
                'subject_type' => $this->subject !== null ? $this->subject::class : null,
                'subject_id' => $this->subject?->getKey(),
                'causer_id' => $this->resolveCauserId(),
                'properties' => $this->properties !== [] ? $this->properties : null,
            ], static fn ($v) => $v !== null));
        } catch (\Throwable $e) {
            // เงียบโดยตั้งใจ — audit ล้มต้องไม่ลากธุรกรรมล้มตาม
        }
    }

    /**
     * หา id ของผู้กระทำ — รับได้ทั้ง Model, id ดิบ หรือไม่ระบุ (ใช้คนที่ล็อกอิน)
     */
    protected function resolveCauserId(): int|string|null
    {
        try {
            if ($this->causer instanceof Authenticatable) {
                return $this->causer->getAuthIdentifier();
            }

            if ($this->causer instanceof Model) {
                return $this->causer->getKey();
            }

            if (is_int($this->causer) || is_string($this->causer)) {
                return $this->causer;
            }

            return Auth::id();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
