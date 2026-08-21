<?php

namespace App\Services\Fortune;

use App\Models\FortuneTellingSetting;

/**
 * 🏬 FortunePageIdentity — บอก AI ว่า "ตอนนี้แม่หมอนั่งรับลูกค้าอยู่เพจไหน"
 *
 * ระบบสาขา (fortune_pages) ทำให้ "ข้อมูล" รู้ว่ามาจากเพจไหนแล้ว (fortune_page_id)
 * แต่ตัว AI ยังไม่เคยถูกบอกเลย → ลูกค้าบนเพจสาขาถามว่า "นี่เพจอะไร"
 * บอทจะเดา/ตอบชื่อเพจหลัก หรือแต่งชื่อขึ้นเอง
 *
 * คลาสนี้แปลง context ปัจจุบันเป็นบล็อกข้อความสั้นๆ แล้วแปะท้าย system prompt
 *
 * ⚠️ กฎที่ต้องรักษาไว้:
 *   1. ไม่มี context = คืนค่าว่าง (พฤติกรรมเดิมเป๊ะ) ห้ามเดาเพจให้ AI
 *      — บอกเพจผิดแย่กว่าไม่บอกเลย
 *   2. idempotent — prompt เดียวไหลผ่านหลายชั้น (builder → generateProResponse)
 *      แปะซ้ำ = เปลืองโทเคนและ AI สับสน
 *   3. ห้ามโยน exception — พังตรงนี้ต้องไม่ทำให้คำทำนายทั้งใบล่ม
 */
class FortunePageIdentity
{
    /** ตัวจับว่าแปะไปแล้ว (ใช้เช็ค idempotent — ห้ามแก้ข้อความนี้โดยไม่แก้ promptBlock) */
    // ⚠️ ขึ้นต้นด้วยตัวอักษรอังกฤษโดยตั้งใจ — sanitizeChatResult() ตัดวงเล็บเฉพาะ [A-Za-z...] กับ [👤 ...]
    //    ขึ้นต้นด้วยอีโมจิ = ถ้า AI เผลอกบล็อกนี้ออกมา จะหลุดถึงหน้าลูกค้า
    public const PROMPT_MARKER = '[PAGE_CONTEXT 🏬 เพจที่แม่หมอนั่งรับลูกค้าอยู่ตอนนี้';

    /**
     * ข้อมูลตัวตนของเพจที่กำลังคุยอยู่
     *
     * @return array{page_id:int,label:string,platform:string,channel_word:string,url:?string,is_default:bool}|null
     */
    public static function describe(): ?array
    {
        try {
            $page = FortunePageContext::current();

            if (! $page) {
                return null;
            }

            // brand_name = ชื่อที่ลูกค้าเห็น (ถ้าแอดมินตั้งไว้) · name = ชื่อเพจจริงจาก Graph
            $label = trim((string) ($page->brand_name ?: $page->name));

            if ($label === '') {
                return null;
            }

            $platform = $page->platform ?: 'facebook';
            $externalId = trim((string) ($page->external_page_id ?? ''));

            return [
                'page_id' => (int) $page->id,
                'label' => $label,
                'platform' => $platform,
                'channel_word' => $platform === 'line' ? 'LINE OA' : 'เพจเฟซบุ๊ก',
                // LINE ไม่มี URL สาธารณะที่ derive จาก channel id ได้ → null
                'url' => ($platform === 'facebook' && $externalId !== '')
                    ? "https://www.facebook.com/{$externalId}"
                    : null,
                'is_default' => (bool) $page->is_default,
            ];
        } catch (\Throwable $e) {
            // ตาราง fortune_pages ยังไม่ migrate / DB สะดุด → ถือว่าไม่รู้เพจ
            return null;
        }
    }

    /**
     * บล็อกข้อความที่จะแปะเข้า system prompt ('' = ไม่รู้ว่าเพจไหน)
     */
    public static function promptBlock(): string
    {
        $info = self::describe();

        if ($info === null) {
            return '';
        }

        try {
            $brand = FortuneTellingSetting::getSettings()->getFortuneBrandName();
        } catch (\Throwable $e) {
            $brand = 'แม่หมอจันทรา';
        }

        $line = "- ตอนนี้คุณกำลังคุยกับลูกค้าผ่าน{$info['channel_word']}ชื่อ \"{$info['label']}\"";

        if ($info['url'] !== null) {
            $line .= " (ลิงก์เพจ: {$info['url']})";
        }

        // 🏠 ช่องหลัก vs ช่องสาขา — เจ้าของสั่ง (2026-08-21): เพจหลักต้องตอบว่า
        //    "แม่หมอจันทรา ช่องหลัก" ไม่ใช่ชื่อระบบ/บริษัท
        $withLink = $info['url'] !== null ? ' พร้อมลิงก์ข้างบน' : '';
        $channelLine = $info['is_default']
            ? "- เพจนี้คือ **ช่องหลัก** ของ{$brand} → ถ้าลูกค้าถามว่า \"นี่เพจอะไร / ช่องไหน / ตามเพจได้ที่ไหน / ใช่เพจ...ไหม\" ให้ตอบว่า \"{$brand} ช่องหลัก\"{$withLink}"
            : "- เพจนี้คือ **ช่องสาขา** ของ{$brand} → ถ้าลูกค้าถามว่า \"นี่เพจอะไร / ช่องไหน / ตามเพจได้ที่ไหน / ใช่เพจ...ไหม\" ให้ตอบชื่อ \"{$info['label']}\"{$withLink}";

        $block = self::PROMPT_MARKER." — ข้อเท็จจริง ห้ามเดาเอง]\n"
            .$line."\n"
            .$channelLine."\n"
            ."- ❌ ห้ามแนะนำตัวว่าเป็น \"Thaiprompt\" / \"ระบบดูดวง Thaiprompt\" / ชื่อบริษัทหรือชื่อระบบใดๆ — ลูกค้ารู้จักแค่ \"{$brand}\"\n"
            .'- ห้ามอ้างชื่อเพจอื่น ห้ามแต่งชื่อเพจหรือสาขาขึ้นเอง และห้ามชวนลูกค้าย้ายไปเพจอื่น';

        // ชื่อเพจตรงกับชื่อแม่หมออยู่แล้ว → เตือนเรื่องนี้จะยิ่งทำให้ AI สับสน
        if ($info['label'] !== $brand) {
            $block .= "\n- ⚠️ ชื่อเพจ ≠ ชื่อของคุณ — คุณยังเป็น \"{$brand}\" คนเดิม เพจนี้คือ \"หน้าร้าน\" ที่คุณนั่งรับลูกค้าอยู่";
        }

        return $block;
    }

    /**
     * แปะบล็อกตัวตนเพจท้าย system prompt แบบไม่ซ้ำ
     *
     * ใช้ได้ทั้งกับ prompt ที่โค้ดสร้างเองและ prompt ที่แอดมินเขียนใน DB
     */
    public static function appendTo(string $systemMessage): string
    {
        // แปะไปแล้วจากชั้นก่อนหน้า → ไม่ต้องทำอะไร
        if (str_contains($systemMessage, self::PROMPT_MARKER)) {
            return $systemMessage;
        }

        $block = self::promptBlock();

        if ($block === '') {
            return $systemMessage;
        }

        return $systemMessage."\n\n".$block;
    }
}
