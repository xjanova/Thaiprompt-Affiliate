<?php

namespace Tests\Feature;

use App\Services\Fortune\PatronDeityDoctrine;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * 🕉️ องค์เทพประจำตัวต้องมาจาก "ตำราวันเกิด" ไม่ใช่จากไพ่
 *
 * เจ้าของสั่ง 2026-09-08: *"คนหนึ่งอาจเหมาะกับการบูชาหลายองค์เทพได้ แต่ต้องตามตำรา
 * ไม่ใช่ตามไพ่ เดิมมันตามไพ่อย่างเดียว ถ้าไม่มีวันเกิดก็ใช้ไพ่แทน"*
 *
 * ของเดิม (ตรวจ prod): คลัง `fortune_knowledge.patron_deity` ผูกองค์กับไพ่ 78 ใบ
 *   ลักษมี 19 · พิฆเนศ 15 · กวนอิม 14 · ครุฑ 13 ⇒ 4 องค์ครอบ 78% ของสำรับ
 *   พญานาคมีแค่ 5 ใบ + พรอมต์สั่ง "เลือกองค์เดียว" ⇒ แทบไม่มีวันถูกเลือก
 *
 * ⚠️ ไม่แตะ DB — ตำราอยู่ใน config ล้วน
 */
class PatronDeityDoctrineTest extends TestCase
{
    /**
     * ⭐ เคสที่เจ้าของยกมา — คนเกิดวันเสาร์ต้องได้พญานาค
     *
     * ที่มาตามตำรา: พระประจำวันเกิดวันเสาร์ = **ปางนาคปรก** (พญานาคแผ่พังพานคุ้มครอง)
     * ไม่ใช่เพราะจั่วได้ไพ่ชุดเหรียญเหมือนของเดิม
     */
    public function test_เกิดวันเสาร์_ต้องได้พญานาคเป็นองค์ประธาน(): void
    {
        // 5 ก.ย. 2569 = วันเสาร์
        $ผล = $this->doctrine()->resolve(Carbon::create(2026, 9, 5), null);

        $this->assertNotNull($ผล, 'มีวันเกิดแล้วต้องได้องค์ตามตำรา ไม่ใช่ null');
        $this->assertSame('พญานาค', $ผล['main']);
        $this->assertSame('ปางนาคปรก', $ผล['buddha_pose']);
        $this->assertSame('เสาร์', $ผล['day_name']);
    }

    /**
     * 🙏 บูชาได้หลายองค์ — ต้องคืนองค์เสริมด้วย และห้ามซ้ำองค์ประธาน
     */
    public function test_ต้องคืนหลายองค์_และองค์เสริมห้ามซ้ำองค์ประธาน(): void
    {
        $ผล = $this->doctrine()->resolve(Carbon::create(2026, 9, 5), null);

        $this->assertGreaterThanOrEqual(2, count($ผล['all']), 'ต้องบูชาได้หลายองค์ ไม่ใช่องค์เดียว');
        $this->assertNotContains($ผล['main'], $ผล['support'], 'องค์เสริมห้ามเป็นองค์เดียวกับองค์ประธาน');
        $this->assertSame(array_unique($ผล['all']), $ผล['all'], 'ห้ามมีองค์ซ้ำในรายการ');
        $this->assertLessThanOrEqual(
            (int) config('fortune_patron_deity_doctrine.max_support'),
            count($ผล['support']),
            'องค์เสริมต้องไม่เกินเพดาน (กันบล็อกบวมจนกินที่หัวข้ออื่น)'
        );
    }

    /**
     * 🌑 วันเกิดที่ 8 — พุธกลางคืนต้องเป็นราหู ไม่ใช่พุธ
     *
     * และตามวันโหร: เกิด "พฤหัส ตี 3" = พุธกลางคืน · เกิด "พุธ ตี 2" = อังคารกลางคืน
     */
    public function test_พุธกลางคืน_ต้องได้ราหู_และวันโหรต้องเลื่อนถูก(): void
    {
        $d = $this->doctrine();

        // 9 ก.ย. 2569 = วันพุธ · เกิด 20:00 = พุธกลางคืน → ราหู
        $พุธค่ำ = $d->resolve(Carbon::create(2026, 9, 9), 20.0);
        $this->assertSame(7, $พุธค่ำ['day_index']);
        $this->assertSame('พระราหู', $พุธค่ำ['main']);

        // 10 ก.ย. 2569 = วันพฤหัส · เกิดตี 3 = ยังเป็นคืนวันพุธ → ราหูเช่นกัน
        $พฤหัสตีสาม = $d->resolve(Carbon::create(2026, 9, 10), 3.0);
        $this->assertSame(7, $พฤหัสตีสาม['day_index'], 'เกิดก่อนย่ำรุ่ง = คืนของเมื่อวาน');

        // 9 ก.ย. 2569 (พุธ) เกิดตี 2 = คืนวันอังคาร → พระอังคาร ไม่ใช่ราหู
        $พุธตีสอง = $d->resolve(Carbon::create(2026, 9, 9), 2.0);
        $this->assertSame(2, $พุธตีสอง['day_index']);
        $this->assertSame('พระอังคาร', $พุธตีสอง['main']);

        // ไม่ทราบเวลาเกิด → ห้ามเดาว่ากลางคืน ต้องเป็นพุธกลางวันตามปฏิทิน
        $พุธไม่รู้เวลา = $d->resolve(Carbon::create(2026, 9, 9), null);
        $this->assertSame(3, $พุธไม่รู้เวลา['day_index'], 'ไม่รู้เวลาเกิด = ห้ามเดาเป็นราหู');
    }

    /**
     * 🃏 ไม่รู้วันเกิด → คืน null เพื่อให้ caller ตกไปใช้ไพ่แทน (คำสั่งเจ้าของ)
     */
    public function test_ไม่รู้วันเกิด_ต้องคืน_null_ให้ตกไปใช้ไพ่(): void
    {
        $this->assertNull($this->doctrine()->resolve(null, null));
    }

    /**
     * 🛡️ ทุกองค์ที่ตำราอ้างถึง ต้องมีทะเบียนจริง
     *
     * ถ้าเทสต์นี้แดง = มีชื่อองค์ในตารางวันเกิด/ธาตุ ที่ไม่มีของบูชา-วันไหว้รองรับ
     * ⇒ พรอมต์จะสั่งให้แม่หมอ "บอกวิธีบูชา" ขององค์ที่เราไม่มีข้อมูล = เปิดช่องให้มโน
     * ([[rule_bot_promise_needs_code_behind_it]])
     */
    public function test_ทุกองค์ในตำรา_ต้องมีในทะเบียนองค์(): void
    {
        $roster = (array) config('fortune_patron_deity_doctrine.roster');
        $ขาด = [];

        foreach ((array) config('fortune_patron_deity_doctrine.by_birth_day') as $วัน => $entry) {
            foreach (array_merge([$entry['main']], (array) $entry['support']) as $ชื่อ) {
                if (! isset($roster[$ชื่อ])) {
                    $ขาด[] = "วันเกิด {$วัน}: {$ชื่อ}";
                }
            }
        }

        foreach ((array) config('fortune_patron_deity_doctrine.by_element') as $ธาตุ => $องค์) {
            foreach ((array) $องค์ as $ชื่อ) {
                if (! isset($roster[$ชื่อ])) {
                    $ขาด[] = "ธาตุ{$ธาตุ}: {$ชื่อ}";
                }
            }
        }

        foreach ((array) config('fortune_patron_deity_doctrine.by_topic') as $key => $topic) {
            foreach ((array) ($topic['deities'] ?? []) as $ชื่อ) {
                if (! isset($roster[$ชื่อ])) {
                    $ขาด[] = "เรื่อง {$key}: {$ชื่อ}";
                }
            }

            $this->assertNotEmpty($topic['keywords'] ?? [], "เรื่อง {$key} ไม่มีคีย์เวิร์ด = ชั้นนี้ไม่มีวันทำงาน");
            $this->assertNotEmpty($topic['label'] ?? '', "เรื่อง {$key} ไม่มีป้ายชื่อ");

            // คีย์เวิร์ดสั้นเกินไป = กับดักคำไทย (เช่น 'ลูก' 'รัก' 'สอบ' 'ของ')
            foreach ((array) ($topic['keywords'] ?? []) as $kw) {
                $this->assertGreaterThanOrEqual(
                    4,
                    mb_strlen((string) $kw),
                    "คีย์เวิร์ด \"{$kw}\" ของเรื่อง {$key} สั้นเกินไป เสี่ยงไปโดนคำอื่นที่สะกดคร่อมกัน"
                );
            }
        }

        $this->assertSame([], $ขาด, 'องค์ที่ไม่มีในทะเบียน: '.implode(' · ', $ขาด));

        // ทะเบียนแต่ละองค์ต้องมีของบูชา + วันไหว้ครบ (พรอมต์ดึงไปใช้ตรง ๆ)
        foreach ($roster as $ชื่อ => $meta) {
            $this->assertNotEmpty($meta['offering'] ?? '', "องค์ {$ชื่อ} ไม่มีของบูชา");
            $this->assertNotEmpty($meta['day'] ?? '', "องค์ {$ชื่อ} ไม่มีวันไหว้");
            $this->assertNotEmpty($meta['level'] ?? '', "องค์ {$ชื่อ} ไม่มีระดับองค์");
        }
    }

    /**
     * 📝 บล็อกที่ฉีดเข้าพรอมต์ ต้องมีของจริงให้แม่หมออ่าน
     */
    public function test_บล็อกความรู้_ต้องมีชื่อองค์_ของบูชา_และที่มาตามตำรา(): void
    {
        $d = $this->doctrine();
        $ผล = $d->resolve(Carbon::create(2026, 9, 5), null);
        $block = $d->knowledgeBlock($ผล);

        $this->assertStringContainsString('พญานาค', $block);
        $this->assertStringContainsString('ปางนาคปรก', $block, 'ต้องบอกที่มาตามตำราให้แม่หมออ้างอิงได้');
        $this->assertStringContainsString('ของบูชา:', $block);
        $this->assertStringContainsString('วันไหว้:', $block);
        $this->assertStringContainsString('องค์ประธาน', $block);
        $this->assertStringContainsString('องค์เสริม', $block);
        $this->assertStringContainsString(
            'ไม่ใช่จากหน้าไพ่',
            $block,
            'ต้องบอกโมเดลตรง ๆ ว่าองค์มาจากวันเกิด ไม่ใช่ไพ่ — ไม่งั้นมันเล่าที่มาผิด'
        );

        // ตำราชั้นธาตุต้องได้ที่นั่งด้วย ไม่ใช่ถูกองค์สายวันเกิดกินหมดทุกครั้ง
        $ธาตุ = $this->doctrine()->resolve(Carbon::create(2026, 9, 5), null);
        $องค์ธาตุ = array_intersect($ธาตุ['support'], $ธาตุ['element_deities']);
        $this->assertNotEmpty($องค์ธาตุ, 'องค์เสริมสายธาตุต้องไม่ถูกเบียดตกทุกครั้ง');
    }

    /**
     * 🎯 ชั้นที่ 3 — องค์ตามเรื่องที่ถาม (สีวลี/หลวงปู่ทวด ได้ที่ยืนตรงนี้)
     */
    public function test_องค์ตามเรื่องที่ถาม_ต้องจับเรื่องได้ถูก(): void
    {
        $d = $this->doctrine();

        $ค้าขาย = $d->topicDeities('ร้านค้าเงียบมาก ยอดขายตกลงเยอะเลยค่ะ');
        $this->assertContains('พระสีวลี', $ค้าขาย['deities']);
        $this->assertContains('แม่นางกวัก', $ค้าขาย['deities']);

        $เดินทาง = $d->topicDeities('ปีนี้จะได้ไปทำงานต่างประเทศไหมคะ');
        $this->assertContains('หลวงปู่ทวด', $เดินทาง['deities']);

        $สุขภาพ = $d->topicDeities('ต้องผ่าตัดเดือนหน้า กังวลมากค่ะ');
        $this->assertContains('หมอชีวกโกมารภัจจ์', $สุขภาพ['deities']);

        // ไม่เข้าเรื่องไหน → ต้องว่าง (ไม่ยัดองค์มั่ว)
        $this->assertSame([], $d->topicDeities('สวัสดีค่ะแม่หมอ')['deities']);
        $this->assertSame([], $d->topicDeities('')['deities']);
    }

    /**
     * 🪤 กับดักคำไทย — คีย์เวิร์ดต้องไม่ไปโดนคำอื่นที่สะกดคร่อมกัน
     *
     * แม่หมอเรียกลูกค้าว่า "ลูก" ทุกประโยค · "รักษา" มี "รัก" · "สอบถาม" มี "สอบ"
     * ([[rule_thai_single_syllable_regex_needs_both_guards]])
     */
    public function test_คีย์เวิร์ดต้องไม่ติดกับดักคำไทย(): void
    {
        $ผล = $this->doctrine()->topicDeities('ลูกอยากสอบถามเรื่องรักษาสุขภาพหน่อยค่ะ');

        $this->assertContains('สุขภาพ-การรักษา', $ผล['topics'], 'ต้องจับเรื่องสุขภาพได้');
        $this->assertNotContains('ความรัก-เนื้อคู่', $ผล['topics'], '"รักษา" ต้องไม่ถูกอ่านเป็น "รัก"');
        $this->assertNotContains('การเรียน-วิชาความรู้', $ผล['topics'], '"สอบถาม" ต้องไม่ถูกอ่านเป็น "สอบ"');
        $this->assertNotContains('ครอบครัว-บุตร', $ผล['topics'], '"ลูก" (สรรพนามเรียกลูกค้า) ต้องไม่ถูกนับ');
    }

    /**
     * 🔁 องค์ตามเรื่อง ต้องไม่ซ้ำกับองค์ประธาน/องค์เสริมที่พูดไปแล้วในบล็อกเดียวกัน
     */
    public function test_องค์ตามเรื่อง_ต้องไม่ซ้ำองค์ที่พูดไปแล้ว(): void
    {
        $d = $this->doctrine();
        $ตำรา = $d->resolve(Carbon::create(2026, 9, 5), null);   // เสาร์ → พญานาค

        $ผล = $d->topicDeities('อยากปลดหนี้สินให้หมดค่ะ', $ตำรา['all']);

        $this->assertNotContains('พญานาค', $ผล['deities'], 'องค์ประธานพูดไปแล้ว ห้ามซ้ำในบล็อกเดียว');
        $this->assertNotEmpty($ผล['deities'], 'ตัดตัวซ้ำแล้วต้องยังเหลือองค์อื่นให้แนะ');

        $block = $d->topicBlock($ผล);
        $this->assertStringContainsString('เสริม ไม่ใช่แทนองค์ประธาน', $block);
        $this->assertStringContainsString('ของบูชา:', $block);
    }

    /**
     * 🔌 ปิดสวิตช์ = กลับไปใช้ไพ่ล้วนแบบเดิม
     */
    public function test_ปิดสวิตช์_ต้องกลับไปใช้ไพ่(): void
    {
        config(['fortune_patron_deity_doctrine.enabled' => false]);

        $this->assertNull($this->doctrine()->resolve(Carbon::create(2026, 9, 5), null));
    }

    protected function doctrine(): PatronDeityDoctrine
    {
        return new PatronDeityDoctrine;
    }
}
