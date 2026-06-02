<?php

/**
 * 🔥💧 คลังความรู้ "ธาตุเสริม-ขัด" (Elemental Dignities — Golden Dawn)
 * ────────────────────────────────────────────────────────────────────
 * User directive (2026-06-02): "ขอแม่นๆ ละเอียดๆ" — เพิ่มเลเยอร์ "ธาตุของไพ่"
 *
 * ตำรา Golden Dawn: ไพ่ที่อยู่ "ติดกัน/คู่กัน" บนกระดาน "เสริม-หักล้าง" กันด้วยธาตุ
 *   ไฟ-ลม = เสริม (Active) · น้ำ-ดิน = เสริม (Passive)
 *   ไฟ-น้ำ = ขัดอย่างแรง (ดับ) · ลม-ดิน = ขัดอย่างแรง (พัดทำลาย)
 *   ไฟ-ดิน = เป็นกลาง (อ่อนต่อกัน) · น้ำ-ลม = เป็นกลาง (อ่อนต่อกัน)
 *   ธาตุเดียวกัน = ทวีคูณพลัง
 *
 * Major Arcana มีธาตุของตัวเอง (ตาม Hebrew letter/Zodiac assignments — Liber 777)
 *   ใช้ในการคำนวณ dignity เช่นกัน (Major มีพลังเต็มอยู่แล้ว — เสริมก็ยิ่งแรง)
 *
 * โครงสร้าง: ['matrix' => [a][b] => 'tone', 'interpretations' => [tone][heavy?] => 'text']
 *
 * Retrieval: elementalDignityLines() เช็ค "คู่ตำแหน่งสำคัญ" บน Celtic + นับสรุปสำรับ
 *   คู่ตำแหน่ง: 1↔2 (ครอส) · 3↔6 (อดีต→อนาคต) · 4↔5 (ใต้-บน) ·
 *               7↔8 (ตัวเอง-สิ่งแวดล้อม) · 9↔10 (หวังกลัว→ผลลัพธ์)
 *
 * ⚠️ "ขัด" ≠ ลางร้าย — สื่อ "ความตึง/ต้องประนีประนอม/พลังถูกบีบ" เป็นข้อมูลให้เห็นภาพ
 */

return [
    'label' => '🔥💧 ธาตุเสริม-ขัด (Golden Dawn)',

    // ════════════ Major Arcana → ธาตุ (Golden Dawn / Liber 777) ════════════
    // ตามจักรราศี/ดาว/ตัวอักษรฮีบรู ที่กำกับแต่ละ Major
    'major_elements' => [
        'The Fool' => 'air',                // Aleph - Air
        'The Magician' => 'air',            // Mercury - Air
        'The High Priestess' => 'water',    // Moon - Water
        'The Empress' => 'earth',           // Venus
        'The Emperor' => 'fire',            // Aries - Fire
        'The Hierophant' => 'earth',        // Taurus - Earth
        'The Lovers' => 'air',              // Gemini - Air
        'The Chariot' => 'water',           // Cancer - Water
        'Strength' => 'fire',               // Leo - Fire
        'The Hermit' => 'earth',            // Virgo - Earth
        'Wheel of Fortune' => 'fire',       // Jupiter
        'Justice' => 'air',                 // Libra - Air
        'The Hanged Man' => 'water',        // Mem - Water
        'Death' => 'water',                 // Scorpio - Water
        'Temperance' => 'fire',             // Sagittarius - Fire
        'The Devil' => 'earth',             // Capricorn - Earth
        'The Tower' => 'fire',              // Mars - Fire
        'The Star' => 'air',                // Aquarius - Air
        'The Moon' => 'water',              // Pisces - Water
        'The Sun' => 'fire',                // Sun - Fire
        'Judgement' => 'fire',              // Shin - Fire (Spirit/Fire)
        'The World' => 'earth',             // Saturn/Tav - Earth
    ],

    // ════════════ ตารางธาตุปฏิสัมพันธ์ ════════════
    // tone: same / friendly / contrary / neutral
    'matrix' => [
        'fire' => ['fire' => 'same', 'air' => 'friendly', 'water' => 'contrary', 'earth' => 'neutral'],
        'air' => ['fire' => 'friendly', 'air' => 'same', 'water' => 'neutral', 'earth' => 'contrary'],
        'water' => ['fire' => 'contrary', 'air' => 'neutral', 'water' => 'same', 'earth' => 'friendly'],
        'earth' => ['fire' => 'neutral', 'air' => 'contrary', 'water' => 'friendly', 'earth' => 'same'],
    ],

    // ════════════ ป้ายธาตุ (ไทย) ════════════
    'element_label' => [
        'fire' => 'ไฟ',
        'water' => 'น้ำ',
        'air' => 'ลม',
        'earth' => 'ดิน',
    ],

    // ════════════ ตีความ tone (per-pair) ════════════
    'pair_interpretation' => [
        'same' => 'ธาตุเดียวกัน → ทวีคูณพลัง — เรื่องนี้ "พุ่งแรงไปทางเดียว" ไม่มีตัวถ่วงดุล',
        'friendly' => 'ธาตุเสริมกัน → ส่งเสริม-ไหลลื่น — พลัง 2 ใบหนุนกันแบบ "ลงตัว"',
        'contrary' => 'ธาตุขัดกัน → หักล้าง-ตึง — พลัง 2 ใบ "ปะทะ" ต้องประนีประนอม/เลือกข้าง',
        'neutral' => 'ธาตุเป็นกลาง → ไม่ส่งเสริมไม่หักล้าง — ไพ่ทั้งสองต่างคนต่างทำงาน',
    ],

    // ════════════ ตีความระดับสำรับ ════════════
    'spread_summary' => [
        'highly_friendly' => 'สำรับนี้ธาตุ "เข้ากันได้สูง" — พลังต่างๆ ในชีวิตเรื่องนี้กำลังหนุนกัน ลื่นไหล (เดินตามจังหวะได้)',
        'highly_contrary' => 'สำรับนี้ธาตุ "ขัดกันมาก" — พลังต่างๆ ปะทะกันเอง สถานการณ์ตึงเครียด ต้องเลือก-ประนีประนอม-อย่าฝืนทุกฝ่าย',
        'balanced' => 'สำรับนี้ธาตุ "สมดุล" — มีทั้งเสริมและขัด เป็นสถานการณ์ปกติของชีวิต ปรับไปตามเหตุการณ์ได้',
    ],

    // ════════════ คู่ตำแหน่งที่ใช้คำนวณ Dignity (Celtic) ════════════
    // [a, b, ชื่อคู่]
    'celtic_pairs' => [
        [1, 2, '🎯 ปัจจุบัน × ขัดขวาง'],
        [3, 6, '⏰ อดีต → อนาคต (ทิศทางเวลา)'],
        [4, 5, '🧠 จิตใต้สำนึก × จิตสำนึก (ใต้-บน)'],
        [7, 8, '👤 ตัวเอง × สิ่งแวดล้อม (เรา-รอบข้าง)'],
        [9, 10, '🌟 หวัง/กลัว → ผลลัพธ์'],
    ],
];
