<?php

/**
 * 🎯 คลังความรู้ "น้ำหนัก Yes/No" (Weighted Yes/No System)
 * ────────────────────────────────────────────────────────────────────
 * User directive (2026-06-02): "ขอแม่นๆ ละเอียดๆ" — ช่วยฟันธงคำถาม yes/no
 *
 * คนถามแม่หมอเยอะมาก "...ไหม / ...หรือเปล่า / จะ X ได้ไหม / เขาจะกลับมาไหม"
 *   → เลเยอร์นี้คำนวณ "น้ำหนัก Yes / No" รายไพ่ + ผลรวม + ตำแหน่งสำคัญถ่วงน้ำหนัก
 *
 * โครงสร้าง:
 *   'card_weights' => [name_en => +/- score]
 *       +3 = ใช่ชัด · +2 ใช่ · +1 ใช่อ่อน · 0 กลาง · -1 ไม่ใช่อ่อน · -2 ไม่ · -3 ไม่ใช่ชัด
 *   'position_multiplier' => [pos => multiplier]
 *       ตำแหน่ง 6 (อนาคต) และ 10 (ผลลัพธ์) ถ่วงน้ำหนัก ×2
 *
 * Retrieval: yesNoVerdict() คำนวณ + ตัดสิน
 *   - คะแนนรวม >= +5 = ✨ ใช่/น่าจะใช่ (ฟันธงด้านบวก)
 *   - +2..+4 = เอนไปทาง ใช่ แต่ไม่แน่ใจ
 *   - -1..+1 = กำกวม/ก้ำกึ่ง
 *   - -2..-4 = เอนไปทาง ไม่ใช่
 *   - <= -5 = ❌ ไม่ใช่/ไม่น่าจะเป็น
 *
 * ⚠️ เลเยอร์นี้ "เปิดเฉพาะคำถาม yes/no" — detect ใน CelticCrossService::buildYesNoDirective
 *   (ไม่งั้นจะ inject ทุกครั้งโดยไม่จำเป็น)
 */

return [
    'label' => '🎯 น้ำหนัก Yes/No',

    // ════════════ ตัวคูณตำแหน่ง (Celtic) ════════════
    // ตำแหน่ง 6 (อนาคต) และ 10 (ผลลัพธ์) สำคัญที่สุดสำหรับ yes/no
    'position_multiplier' => [
        1 => 1.0,   // ปัจจุบัน
        2 => 1.0,   // ครอส
        3 => 1.0,   // จิตสำนึก/เป้าหมาย (ตาม CELTIC_POSITIONS — เดิม label ผิดเป็น "อดีต")
        4 => 0.8,   // จิตใต้สำนึก/รากฐาน
        5 => 0.5,   // อดีต (น้ำหนักน้อยสุด — yes/no เน้นอนาคต ไม่ใช่อดีต)
        6 => 2.0,   // 🌟 อนาคต — สำคัญสุด
        7 => 1.0,   // ตัวเอง
        8 => 1.0,   // สิ่งแวดล้อม
        9 => 1.2,   // หวัง/กลัว
        10 => 2.5,  // 🌟🌟 ผลลัพธ์ — สำคัญที่สุด
    ],

    // ════════════ น้ำหนักไพ่รายใบ (Upright) ════════════
    // หากกลับหัว → คะแนนกลับสัญลักษณ์ (เว้นไพ่ที่ระบุ "rev_score" เฉพาะ)
    'card_weights' => [
        // ───── Major Arcana ─────
        'The Fool' => 1,            // ใช่ (เริ่มใหม่ได้)
        'The Magician' => 2,        // ใช่ (มีความสามารถทำได้)
        'The High Priestess' => 0,  // กำกวม (ลึกลับ-รอ)
        'The Empress' => 3,         // ✨ ใช่ชัด (อุดมสมบูรณ์)
        'The Emperor' => 2,         // ใช่ (มั่นคง)
        'The Hierophant' => 2,      // ใช่ (ตามแบบแผน)
        'The Lovers' => 3,          // ✨ ใช่ชัด (ตัดสินใจถูก)
        'The Chariot' => 3,         // ✨ ใช่ชัด (ก้าวหน้า)
        'Strength' => 2,            // ใช่ (เข้มแข็งพอ)
        'The Hermit' => -1,         // ไม่ใช่อ่อน (ต้องรอ/อยู่คนเดียว)
        'Wheel of Fortune' => 2,    // ใช่ (โชคหมุนมา)
        'Justice' => 2,             // ใช่ (เป็นไปตามที่ควร)
        'The Hanged Man' => -2,     // ไม่ (รอ-ค้าง)
        'Death' => -2,              // ไม่ (จบ-เปลี่ยน)
        'Temperance' => 1,          // ใช่อ่อน (ค่อยเป็นค่อยไป)
        'The Devil' => -2,          // ไม่ (ติดกับ)
        'The Tower' => -3,          // ❌ ไม่ชัด (พัง)
        'The Star' => 3,            // ✨ ใช่ชัด (หวังเป็นจริง)
        'The Moon' => -2,           // ไม่ (คลุมเครือ-ลวง)
        'The Sun' => 3,             // ✨ ใช่ชัดที่สุด
        'Judgement' => 2,           // ใช่ (ตัดสิน-เรียก)
        'The World' => 3,           // ✨ ใช่ชัด (สำเร็จ)

        // ───── Wands (ไม้เท้า/ไฟ) ─────
        'Ace of Wands' => 2,
        'Two of Wands' => 1,
        'Three of Wands' => 2,
        'Four of Wands' => 3,       // ✨ ฉลอง-มงคล
        'Five of Wands' => -1,      // ทะเลาะ
        'Six of Wands' => 3,        // ✨ ชัยชนะ
        'Seven of Wands' => 1,      // สู้-ป้องกัน
        'Eight of Wands' => 2,
        'Nine of Wands' => 0,       // เหนื่อย-รอ
        'Ten of Wands' => -1,       // ภาระหนัก
        'Page of Wands' => 1,
        'Knight of Wands' => 2,
        'Queen of Wands' => 2,
        'King of Wands' => 2,

        // ───── Cups (ถ้วย/น้ำ) ─────
        'Ace of Cups' => 3,         // ✨ เปิดใจ-รักใหม่
        'Two of Cups' => 3,         // ✨ คู่-ลงตัว
        'Three of Cups' => 2,
        'Four of Cups' => -1,       // เบื่อ
        'Five of Cups' => -2,       // ผิดหวัง
        'Six of Cups' => 1,         // ดี (แต่ติดอดีต)
        'Seven of Cups' => -1,      // ฝันเฟื่อง-ลังเล
        'Eight of Cups' => -1,      // ทิ้งไป
        'Nine of Cups' => 3,        // ✨ Wish granted
        'Ten of Cups' => 3,         // ✨ สมบูรณ์
        'Page of Cups' => 1,
        'Knight of Cups' => 2,      // ข้อเสนอ
        'Queen of Cups' => 2,
        'King of Cups' => 2,

        // ───── Swords (ดาบ/ลม) ─────
        'Ace of Swords' => 1,       // ตัดสินเด็ดขาด (อาจดี-ร้าย)
        'Two of Swords' => 0,       // ลังเล-ก้ำกึ่ง
        'Three of Swords' => -3,    // ❌ อกหัก
        'Four of Swords' => -1,     // พัก-ยังไม่
        'Five of Swords' => -2,     // ปะทะ
        'Six of Swords' => 1,       // ย้ายผ่าน (ผ่านได้)
        'Seven of Swords' => -2,    // โกง-หลอก
        'Eight of Swords' => -2,    // ติดกับ
        'Nine of Swords' => -2,     // กังวล
        'Ten of Swords' => -3,      // ❌ จบสนิท
        'Page of Swords' => 0,
        'Knight of Swords' => 0,    // รีบ (อาจดี-เสีย)
        'Queen of Swords' => 1,
        'King of Swords' => 1,

        // ───── Pentacles (เหรียญ/ดิน) ─────
        'Ace of Pentacles' => 3,    // ✨ โอกาสมั่นคง
        'Two of Pentacles' => 1,
        'Three of Pentacles' => 2,  // ทีม-สำเร็จ
        'Four of Pentacles' => 0,   // ยึดติด (กลาง)
        'Five of Pentacles' => -3,  // ❌ ขัดสน
        'Six of Pentacles' => 2,    // ให้-รับสมดุล
        'Seven of Pentacles' => 0,  // รอผล
        'Eight of Pentacles' => 2,  // ทักษะ
        'Nine of Pentacles' => 3,   // ✨ มั่งคั่ง-อิสระ
        'Ten of Pentacles' => 3,    // ✨ ครอบครัวมั่นคง
        'Page of Pentacles' => 1,
        'Knight of Pentacles' => 1, // ขยัน-ช้า
        'Queen of Pentacles' => 2,
        'King of Pentacles' => 3,
    ],

    // ════════════ การตัดสินผลลัพธ์ ════════════
    'verdicts' => [
        'strong_yes' => ['threshold' => 5, 'text' => '✨✨ ใช่/น่าจะเป็นไปตามที่ถาม', 'icon' => '✅'],
        'lean_yes' => ['threshold' => 2, 'text' => '✨ เอนไปทาง "ใช่" — มีแนวโน้มเชิงบวก แต่ยังต้องลงมือ', 'icon' => '🟢'],
        'unclear' => ['threshold' => -1, 'text' => '⚖️ ก้ำกึ่ง/ยังไม่ชัด — ขึ้นกับการกระทำของเจ้าชะตา', 'icon' => '🟡'],
        'lean_no' => ['threshold' => -4, 'text' => '⚠️ เอนไปทาง "ไม่ใช่" — มีอุปสรรค/ต้องเปลี่ยนวิธี', 'icon' => '🔶'],
        'strong_no' => ['threshold' => -999, 'text' => '⚠️⚠️ ไม่/ยังไม่ใช่ — ไพ่ไม่หนุนทิศนี้ ลองทบทวนทางใหม่', 'icon' => '🔴'],
    ],
];
