{{--
    คู่มือ "เส้นทางเศรษฐี" ฉบับเริ่มต้น (ธีมโนวาเต็ม) — หน้าเนื้อหาล้วน 10 บท (route closure user.wealth-guide)
    เนื้อหาเก็บเป็นอาร์เรย์ด้านล่าง แล้ววนแสดงด้วยคลาสโนวา (nvm-*) + คลาสเฉพาะหน้า nvm-um-*
    สีประจำยศส่งเข้า CSS ผ่านตัวแปร --c เท่านั้น
--}}
@extends('layouts.user-nova')

@section('title', 'เส้นทางเศรษฐี - คู่มือสู่ความร่ำรวย')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/user-misc.css') }}?v={{ filemtime(public_path('theme-nova/member/user-misc.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);

    // ── สารบัญ (บทเรียน 10 บท) — [anchor, ไอคอน, บท, ชื่อบท] ─────────
    $toc = [
        ['chapter-1', 'send', 'บทที่ 1', 'เริ่มต้นอย่างไร'],
        ['chapter-2', 'info', 'บทที่ 2', 'ระบบทำงานอย่างไร'],
        ['chapter-3', 'cash', 'บทที่ 3', '4 ช่องทางรายได้'],
        ['chapter-4', 'award', 'บทที่ 4', 'ระบบยศและอันดับ'],
        ['chapter-5', 'users', 'บทที่ 5', 'สร้างทีมที่แข็งแกร่ง'],
        ['chapter-6', 'up', 'บทที่ 6', 'กลยุทธ์ขายที่ได้ผล'],
        ['chapter-7', 'wallet', 'บทที่ 7', 'กระเป๋าเงินและถอนเงิน'],
        ['chapter-8', 'user', 'บทที่ 8', 'เป็นแม่ทีมมืออาชีพ'],
        ['chapter-9', 'gift', 'บทที่ 9', 'เคล็ดลับและคำแนะนำ'],
        ['chapter-10', 'search', 'บทที่ 10', 'คำถามที่พบบ่อย'],
    ];

    // ── ขั้นตอนเริ่มต้น 5 ขั้น ─────────────────────────────────
    $startSteps = [
        ['สมัครสมาชิก', 'กรอกข้อมูลให้ครบถ้วน ใช้ลิงก์แนะนำจากผู้สปอนเซอร์ของคุณ (ถ้ามี) เพื่อเชื่อมโยงเข้ากับทีม'],
        ['ยืนยันตัวตน (KYC)', 'อัพโหลดบัตรประชาชนและรูปถ่ายเพื่อยืนยันตัวตน จะช่วยเพิ่มความน่าเชื่อถือและปลดล็อคฟีเจอร์เต็มรูปแบบ'],
        ['ทำความเข้าใจระบบ', 'อ่านคู่มือนี้ให้จบ ดูวิดีโอสอนใช้งาน และทดลองใช้เครื่องมือต่างๆ เช่น ตัวคำนวณรายได้'],
        ['เริ่มแชร์และขาย', 'คัดลอกลิงก์แนะนำของคุณ แชร์ให้เพื่อนและคนรู้จัก เริ่มสร้างยอดขายแรกของคุณ'],
        ['สร้างทีมและเติบโต', 'เชิญคนอื่นมาร่วมทีม สอนพวกเขาให้ประสบความสำเร็จ และรับค่าคอมจากทั้งทีม'],
    ];

    $newbieTips = [
        ['เริ่มจากวงใกล้:', 'ขายให้คนรู้จักก่อน เพราะพวกเขาเชื่อใจคุณ'],
        ['เรียนรู้ผลิตภัณฑ์:', 'ทดลองใช้เองก่อนแนะนำผู้อื่น'],
        ['ตั้งเป้าหมาย:', 'กำหนดเป้ารายได้รายเดือนและทำงานให้ถึงเป้า'],
        ['สม่ำเสมอคือกุญแจ:', 'ขายทุกวันดีกว่าขายเยอะวันเดียว'],
    ];

    // ── 4 ช่องทางรายได้ (บทที่ 3) ───────────────────────────────
    $rankBonus = [
        ['Bronze (สำริด)', '100 บาท (ต้อนรับ) | Commission 5%', '#b07a3c'],
        ['Silver (เงิน)', '500 บาท | Commission 7.5%', '#8a93a0'],
        ['Gold (ทอง)', '2,000 บาท + 500/ด. | 10%', '#e0a52e'],
        ['Platinum (แพลตินัม)', '10,000 บาท + 2,000/ด. | 15%', '#3fa6b8'],
        ['Diamond (เพชร)', '50,000 บาท + 5,000/ด. | 20%', '#5689b8'],
        ['Crown (มงกุฎ)', '100,000 บาท + 10,000/ด. | 25%', '#c08a2e'],
        ['Royal (รอยัล)', '300,000 บาท + 25,000/ด. | 30%', '#8d5fc7'],
        ['Legend (ตำนาน)', '1,000,000 บาท + 100,000/ด. | 35%', '#d65a8a'],
    ];

    // [ไอคอน, หัวข้อ, คำโปรย, หัวตาราง, แถว[[ซ้าย, ขวา]], หัวตัวอย่าง, บรรทัดตัวอย่าง, สรุปตัวอย่าง]
    $streams = [
        ['users', '1. ค่าคอมมิชชั่นตรง (Direct Commission)', 'รับค่าคอมทันทีจากยอดขายของลูกทีมโดยตรง ตามระบบ Unilevel', 'อัตราค่าคอมตามระดับ',
            [['Level 1 (ลูกทีมตรง)', '10%'], ['Level 2 (ลูกของลูก)', '5%'], ['Level 3', '3%'], ['Level 4-10', '1-2%']],
            'ตัวอย่าง', 'ลูกทีม Level 1 จำนวน 10 คน ซื้อสินค้าคนละ 5,000 บาท/เดือน', 'รายได้ของคุณ = 10 × 5,000 × 10% = 5,000 บาท/เดือน'],
        ['swap', '2. โบนัสจับคู่ (Binary Matching Bonus)', 'รับโบนัสพิเศษเมื่อทีมซ้าย-ขวาของคุณจับคู่กัน', 'วิธีการคำนวณ',
            [['ค่าโบนัสต่อคู่', '100 บาท/คู่ (Match 50%)'], ['การจับคู่', 'นับจาก PV ข้างที่น้อยกว่า (Weak Leg)'], ['ไม่จำกัดต่อวัน', 'ไม่จำกัดจำนวนคู่ (ขึ้นอยู่กับ PV ของทีม)']],
            'ตัวอย่าง', 'ทีมซ้าย 50,000 PV | ทีมขวา 30,000 PV', 'รายได้ = 30,000 × 50% × 100 ÷ 100 = 15,000 บาท'],
        ['award', '3. โบนัสยศ (Rank Bonus)', 'รับโบนัสพิเศษเมื่อขึ้นยศใหม่ และรายได้ประจำทุกเดือน', null, [],
            'ยศสูงสุด Legend', 'เมื่อถึงยศ Legend คุณจะได้รับโบนัส 1,000,000 บาท ทันที!', 'พร้อมรายได้ประจำเดือน 100,000 บาท'],
        ['gift', '4. โบนัสสปอนเซอร์ (Sponsorship Bonus)', 'รับโบนัสพิเศษเมื่อเชิญคนใหม่เข้ามาซื้อสินค้า', 'อัตราโบนัส',
            [['โบนัสต่อคน', '10-20% ของยอดซื้อครั้งแรก'], ['โปรโมชั่นพิเศษ', 'บางช่วงอาจมีโบนัสเพิ่ม เช่น เชิญ 5 คน รับเพิ่ม 5,000 บาท']],
            'ตัวอย่าง', 'เชิญเพื่อน 10 คน ซื้อสินค้าคนละ 10,000 บาท', 'รายได้ = 10 × 10,000 × 15% = 15,000 บาท'],
    ];

    $goldExample = [
        ['Unilevel Commission (ทีม 50 คน)', '5,000 บาท'],
        ['Binary Bonus (50,000 PV จับคู่)', '25,000 บาท'],
        ['Rank Monthly Bonus (Gold)', '500 บาท'],
        ['Direct Referral Bonus (5 คนใหม่)', '500 บาท'],
    ];

    // ── ยศ 8 ระดับ (บทที่ 4) — [ชื่อ, คำโปรย, สี, เงื่อนไข[], รางวัล[], สิทธิพิเศษ[]] ──
    $ranks = [
        ['Bronze (สำริด)', 'ยศเริ่มต้นสำหรับสมาชิกใหม่ทุกคน', '#b07a3c',
            ['ไม่มีเงื่อนไข', 'สมัครสมาชิกได้ทันที', 'เริ่มต้นทุกคนที่ Bronze'],
            ['โบนัสต้อนรับ 100 บาท', 'Commission Rate: 5%', 'Unilevel 3 ชั้น'],
            ['เข้าถึงระบบ MLM', 'ลิงก์แนะนำส่วนตัว', 'ถอนเงินได้ 2 ครั้ง/เดือน']],
        ['Silver (เงิน)', 'พันธมิตรที่กำลังเติบโต', '#8a93a0',
            ['รับคะแนน 100 แต้ม', 'แนะนำ 5 คน', 'ยอดขาย 10,000 บาท', 'รักษายอด 50 PV/เดือน'],
            ['โบนัส 500 บาท (ครั้งเดียว)', 'Commission Rate: 7.5%', 'Multiplier 1.25x', 'Unilevel 4 ชั้น'],
            ['VIP Support', 'ลดค่าธรรมเนียมถอน 5%', 'ถอนเงินได้ 4 ครั้ง/เดือน']],
        ['Gold (ทอง)', 'พันธมิตรที่ประสบความสำเร็จ คุณเป็นผู้นำแล้ว!', '#e0a52e',
            ['รับคะแนน 500 แต้ม', 'แนะนำ 20 คน', 'ยอดขาย 50,000 บาท', 'สมาชิก Active 10 คน', 'รักษายอด 100 PV/เดือน'],
            ['โบนัส 2,000 บาท (ครั้งเดียว)', 'โบนัสรายเดือน 500 บาท', 'Commission Rate: 10%', 'Multiplier 1.5x', 'Unilevel 5 ชั้น'],
            ['VIP Support', 'Priority Withdrawal', 'ลดค่าธรรมเนียมถอน 10%', 'ถอนเงินได้ 8 ครั้ง/เดือน']],
        ['Platinum (แพลตินัม)', 'พันธมิตรระดับยอดเยี่ยม ผู้สร้างธุรกิจตัวจริง!', '#3fa6b8',
            ['รับคะแนน 2,000 แต้ม', 'แนะนำ 50 คน', 'ยอดขาย 200,000 บาท', 'สมาชิก Active 25 คน', 'ยอดขายทีม 500,000 บาท'],
            ['โบนัส 10,000 บาท (ครั้งเดียว)', 'โบนัสรายเดือน 2,000 บาท', 'Commission Rate: 15%', 'Multiplier 2.0x', 'Unilevel 6 ชั้น'],
            ['Exclusive Products', 'Leadership Training', 'ลดค่าธรรมเนียมถอน 20%', 'ถอนเงินได้ 15 ครั้ง/เดือน']],
        ['Diamond (เพชร)', 'พันธมิตรระดับสูง ผลงานโดดเด่น คุณเจิดจรัส!', '#5689b8',
            ['รับคะแนน 10,000 แต้ม', 'แนะนำ 100 คน', 'ยอดขาย 1,000,000 บาท', 'สมาชิก Active 50 คน', 'ยอดขายทีม 2,000,000 บาท'],
            ['โบนัส 50,000 บาท (ครั้งเดียว)', 'โบนัสรายเดือน 5,000 บาท', 'Commission Rate: 20%', 'Multiplier 2.5x', 'Unilevel 7 ชั้น'],
            ['Travel Rewards', 'VIP Support', 'ลดค่าธรรมเนียมถอน 30%', 'ถอนเงินไม่จำกัด']],
        ['Crown (มงกุฎ)', 'ผู้นำระดับเอลิท สวมมงกุฎแห่งความสำเร็จ!', '#c08a2e',
            ['รับคะแนน 25,000 แต้ม', 'แนะนำ 200 คน', 'ยอดขาย 2,500,000 บาท', 'สมาชิก Active 100 คน', 'ยอดขายทีม 5,000,000 บาท', 'มีลูกทีม Diamond 2 คน'],
            ['โบนัส 100,000 บาท (ครั้งเดียว)', 'โบนัสรายเดือน 10,000 บาท', 'Commission Rate: 25%', 'Multiplier 3.0x', 'Unilevel 8 ชั้น'],
            ['Car Bonus (20,000 บาท/เดือน)', 'ลดค่าธรรมเนียมถอน 40%', 'ถอนเงินไม่จำกัด']],
        ['Royal (รอยัล)', 'ผู้นำระดับรอยัล คุณอยู่ในกลุ่มราชวงศ์แห่งความสำเร็จ!', '#8d5fc7',
            ['รับคะแนน 50,000 แต้ม', 'แนะนำ 350 คน', 'ยอดขาย 5,000,000 บาท', 'สมาชิก Active 200 คน', 'ยอดขายทีม 10,000,000 บาท', 'มีลูกทีม Crown 3 คน'],
            ['โบนัส 300,000 บาท (ครั้งเดียว)', 'โบนัสรายเดือน 25,000 บาท', 'Commission Rate: 30%', 'Multiplier 4.0x', 'Unilevel 9 ชั้น'],
            ['House Bonus (50,000 บาท/เดือน)', 'Personal Assistant', 'ลดค่าธรรมเนียมถอน 50%']],
        ['Legend (ตำนาน)', 'ความสำเร็จสูงสุด! คุณคือตำนาน', '#d65a8a',
            ['รับคะแนน 100,000 แต้ม', 'แนะนำ 500 คน', 'ยอดขาย 10,000,000 บาท', 'สมาชิก Active 300 คน', 'ยอดขายทีม 25,000,000 บาท', 'มีลูกทีม Royal 3 คน'],
            ['โบนัส 1,000,000 บาท!', 'โบนัสรายเดือน 100,000 บาท', 'Commission Rate: 35%', 'Multiplier 5.0x', 'Unilevel 10 ชั้น'],
            ['Global Bonus Pool (2% กำไรโลก)', 'ถอนเงินฟรี ไม่มีค่าธรรมเนียม', 'ถอนเงินไม่จำกัด', 'สิทธิพิเศษสูงสุดทุกอย่าง']],
    ];

    $rankTips = [
        ['สอนลูกทีม:', 'ช่วยลูกทีมของคุณขึ้นยศ พวกเขาจะช่วยดันคุณขึ้นไปด้วย'],
        ['สร้างทีมกว้าง:', 'อย่าพึ่งแค่คนเดียว กระจายทีมให้หลากหลาย'],
        ['ทำยอดส่วนตัว:', 'คุณต้องเป็นแบบอย่างที่ดี ขายด้วยตัวเองด้วย'],
        ['ติดตามทุกวัน:', 'เช็คความคืบหน้า กระตุ้นทีม และแก้ปัญหาทันที'],
    ];

    // ── 7 ขั้นตอนสร้างทีม (บทที่ 5) — [หัวข้อ, คำนำ, รายการ[], ข้อที่ควรเลี่ยง[]] ──
    $teamSteps = [
        ['หาคนที่เหมาะสม', 'ไม่ใช่ทุกคนที่จะเหมาะกับระบบ ให้มองหา:', [
            'คนที่อยากหารายได้เสริม มีเวลา มีความมุ่งมั่น',
            'คนที่มีเครือข่ายกว้าง ชอบพูดคุย ชอบแนะนำ',
            'คนที่เรียนรู้ได้เร็ว พัฒนาตัวเองอยู่เสมอ',
        ], ['หลีกเลี่ยงคนที่แค่อยากได้เงินด่วน ไม่อยากทำงาน']],
        ['อบรมและถ่ายทอด', 'ลูกทีมต้องเก่งไม่แพ้คุณ:', [
            'จัดอบรมประจำสัปดาห์ สอนระบบ เทคนิคขาย',
            'สร้างกลุ่มไลน์หรือเฟซบุ๊ค แบ่งปันเคล็ดลับ',
            'บันทึกวิดีโอสอน ให้ลูกทีมดูซ้ำได้',
            'ให้สคริปต์การขาย เทมเพลตโพสต์',
        ], []],
        ['สนับสนุนและกระตุ้น', 'อยู่เคียงข้างลูกทีมเสมอ:', [
            'ช่วยปิดการขายครั้งแรก เป็นกำลังใจ',
            'โทรเช็คความคืบหน้าสัปดาห์ละครั้ง',
            'มอบรางวัลให้ลูกทีมที่ทำผลงานดี',
            'ให้คำปรึกษา แก้ปัญหาอย่างจริงใจ',
        ], []],
        ['ติดตามและวิเคราะห์', 'ใช้ข้อมูลวางแผน:', [
            'ดู Dashboard ของทีมทุกวัน รู้ว่าใครทำดี ใครติดปัญหา',
            'ตั้งเป้าหมายรายสัปดาห์ รายเดือน',
            'วิเคราะห์ว่ากลยุทธ์ไหนได้ผล ไหนไม่ได้ผล',
            'หาสาเหตุที่ยอดขายลด แก้ไขทันที',
        ], []],
        ['สร้างวัฒนธรรมทีม', 'ทีมที่ดีต้องมีความรู้สึกเป็นครอบครัว:', [
            'จัดกิจกรรมทีมบิลดิ้ง ปาร์ตี้ฉลองความสำเร็จ',
            'มีระบบยกย่องคนทำดี ให้เกียรติ',
            'สร้างบรรยากาศแห่งการช่วยเหลือซึ่งกันและกัน',
            'มีวิสัยทัศน์ร่วม ทุกคนรู้ว่าทีมไปทางไหน',
        ], []],
        ['ทำให้ระบบทำงานอัตโนมัติ', 'ลดการพึ่งพาคุณคนเดียว:', [
            'ใช้ระบบอัตโนมัติ เช่น chatbot ตอบคำถามลูกค้า',
            'สร้างคลังความรู้ ให้ทีมหาข้อมูลเองได้',
            'ฝึกลูกทีมเก่งๆ ให้เป็นผู้นำรุ่นใหม่',
            'มีระบบรายงานผลอัตโนมัติ',
        ], []],
        ['ขยายทีมอย่างต่อเนื่อง', 'อย่าหยุดเติบโต:', [
            'หาสมาชิกใหม่ทุกสัปดาห์ อย่าหยุดหา',
            'ส่งเสริมลูกทีมให้หาคนของตัวเอง',
            'ขยายไปพื้นที่ใหม่ จังหวัดใหม่',
            'ใช้โซเชียลมีเดีย ทำ content marketing',
        ], []],
    ];

    $goldenRules = [
        ['1. เป็นแบบอย่างที่ดี', 'คุณต้องทำในสิ่งที่สอน ถ้าคุณไม่ขาย ลูกทีมก็จะไม่ขาย'],
        ['2. สอนให้เก่งกว่าคุณ', 'อย่ากลัวลูกทีมเก่งกว่า ยิ่งเขาเก่ง คุณยิ่งได้ประโยชน์'],
        ['3. ช่วยพวกเขาประสบความสำเร็จ', 'เมื่อทีมสำเร็จ คุณก็จะสำเร็จไปด้วย นี่คือ Win-Win แท้ๆ'],
    ];

    // ── บทที่ 6-9 (การ์ดสรุป) — [anchor, ไอคอน, CHAPTER, ชื่อบท, เคล็ดลับ[[หัวข้อ, คำอธิบาย]]] ──
    $shortChapters = [
        ['chapter-6', 'up', 'Chapter 6', 'กลยุทธ์ขายที่ได้ผล', [
            ['เข้าใจลูกค้า', 'ถามปัญหา ฟังให้ดี แล้วเสนอโซลูชั่น ไม่ใช่ยัดเยียดขาย'],
            ['ใช้โซเชียลมีเดีย', 'โพสต์ content สม่ำเสมอ เล่าเรื่องราวความสำเร็จ ไม่ใช่แค่โฆษณา'],
            ['สร้างความไว้วางใจ', 'รีวิวจากคนจริง ใช้ผลิตภัณฑ์เองก่อน มีหลักฐานการจ่ายเงิน'],
            ['Follow Up', 'ติดตามลูกค้าที่สนใจ ส่วนใหญ่ปิดการขายที่รอบ 3-5'],
        ]],
        ['chapter-7', 'wallet', 'Chapter 7', 'กระเป๋าเงินและถอนเงิน', [
            ['ยอดคงเหลือ', 'เช็คยอดเงินแบบ realtime มีทั้งเงินหลัก, โบนัส, คอมมิชชั่น'],
            ['ถอนเงิน', 'ขั้นต่ำ 500 บาท โอนภายใน 1-3 วันทำการ ผ่านธนาคารหรือ PromptPay'],
            ['ประวัติรายการ', 'ดูรายการรับ-จ่าย รายงานภาษี ดาวน์โหลด PDF ได้'],
            ['ความปลอดภัย', 'ใช้ PIN, 2FA ปกป้องบัญชี ข้อมูลเข้ารหัสทั้งหมด'],
        ]],
        ['chapter-8', 'user', 'Chapter 8', 'เป็นแม่ทีมมืออาชีพ', [
            ['เรียนรู้ไม่หยุด', 'เข้าอบรม อ่านหนังสือ ติดตามเทรนด์ใหม่ๆ'],
            ['พัฒนาทักษะพูด', 'นำเสนอได้ชัดเจน สอนได้น่าฟัง สร้างแรงบันดาลใจ'],
            ['มีวินัย', 'ทำงานสม่ำเสมอ ตรงต่อเวลา เป็นตัวอย่าง'],
            ['ใส่ใจทีม', 'อยู่เคียงข้าง แก้ปัญหา เป็นพี่เลี้ยงที่ดี'],
        ]],
        ['chapter-9', 'gift', 'Chapter 9', 'เคล็ดลับและคำแนะนำ', [
            ['เริ่มทำเลย', 'อย่ารอวันที่ "พร้อม" เพราะไม่มีวันนั้น เริ่มตอนนี้'],
            ['ตั้งเป้าชัดเจน', 'รายได้เท่าไร เมื่อไร ทำอะไรบ้าง เขียนลงกระดาษ'],
            ['อย่าท้อง่ายๆ', 'ปฏิเสธ 100 คน ถึงจะปิด 10 คน นี่เป็นเรื่องปกติ'],
            ['ใช้เครื่องมือ', 'ตัวคำนวณรายได้ กราฟทีม รายงานต่างๆ ช่วยได้มาก'],
        ]],
    ];

    // ── FAQ (บทที่ 10) ────────────────────────────────────────
    $faqs = [
        ['ต้องลงทุนเท่าไรถึงจะเริ่มได้?', 'สมัครฟรี! แต่ถ้าซื้อสินค้าเพื่อขาย หรือใช้เอง ราคาเริ่มต้น 500 บาท คุณสามารถเริ่มด้วยการแนะนำคนอื่นโดยไม่ต้องลงทุนเลยก็ได้'],
        ['ต้องใช้เวลาทำงานเท่าไร?', 'ขึ้นอยู่กับเป้าหมาย ถ้าอยากรายได้ 10,000 บาท อาจใช้ 2-3 ชั่วโมง/วัน แต่ถ้าอยากรายได้ 100,000 บาท อาจต้อง fulltime'],
        ['ถอนเงินได้จริงไหม? นานแค่ไหน?', 'ถอนได้จริง 100% โอนภายใน 1-3 วันทำการ มีหลักฐานการจ่ายเงินนับพัน-หมื่นรายการ'],
        ['ไม่เคยขายของ จะทำได้ไหม?', 'ได้แน่นอน! เรามีการอบรม สอนทุกอย่างตั้งแต่เริ่มต้น มีทีมสนับสนุน มีสคริปต์ขายให้ใช้ ไม่ต้องกังวล'],
        ['ขายได้จริงไหม? มีคนซื้อไหม?', 'ขายได้จริง! สินค้าของเรามีคุณภาพ ราคาดี มีคนซื้อจริง มีรีวิวนับพัน ที่สำคัญคือคุณต้องเรียนรู้วิธีขายที่ถูกต้อง'],
        ['ถ้าไม่มีคนในทีมเลย จะได้เงินไหม?', 'ได้! คุณสามารถขายเองและรับค่าคอมจากยอดขายตัวเอง แต่ถ้ามีทีม รายได้จะเพิ่มขึ้นทวีคูณ'],
        ['ระบบนี้ถูกกฎหมายไหม?', 'ถูกต้องตามกฎหมาย 100% เป็นระบบ MLM ที่ถูกต้อง มีการขายสินค้าจริง ไม่ใช่ระบบปิรามิดหลอกลวง'],
        ['ถ้าทำแล้วไม่ได้เงิน จะทำยังไง?', 'ถามตัวเองว่า: 1) ขายหรือยัง? 2) สอนทีมหรือยัง? 3) ทำสม่ำเสมอหรือเปล่า? ถ้าทำครบแล้วยังไม่ได้ ปรึกษาผู้สปอนเซอร์หรือทีมซัพพอร์ตเรา'],
    ];

@endphp

@section('content')

{{-- ── ฮีโร่ ─────────────────────────────────────────────── --}}
<section class="nvm-ph" aria-labelledby="nvm-um-wg-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>แดชบอร์ด</a>
            <div class="nvm-eyebrow nvm-latin">Wealth guide</div>
            <h1 id="nvm-um-wg-h">เส้นทางเศรษฐี</h1>
            <p class="nvm-ph__lead">คู่มือสู่ความร่ำรวย ด้วยระบบ Affiliate ของเรา — จากมือใหม่สู่แม่ทีม</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>10 บทเรียน</span>
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-user"/></svg>จากมือใหม่สู่แม่ทีม</span>
                <span class="nvm-pill nvm-pill--gold"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>8 ระดับยศ</span>
            </div>
            @if($nvR('user.wealth-guide-pro'))
                <div class="nvm-hero__actions">
                    <a class="nvm-btn nvm-btn--ghost" href="{{ route('user.wealth-guide-pro') }}"><svg class="nvm-ico"><use href="#nvm-i-up"/></svg>ฉบับ PRO</a>
                </div>
            @endif
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="200" height="200">
    </div>
</section>

{{-- ── สารบัญ ───────────────────────────────────────────── --}}
<nav class="nvm-card nvm-box" aria-labelledby="nvm-um-wg-toc">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Contents</small><h2 id="nvm-um-wg-toc">สารบัญ</h2></div></div>
    <div class="nvm-um-toc">
        @foreach($toc as [$anchor, $icon, $no, $name])
            <a href="#{{ $anchor }}">
                <span class="nvm-um-ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $icon }}"/></svg></span>
                <span><b>{{ $no }}</b><small>{{ $name }}</small></span>
            </a>
        @endforeach
    </div>
</nav>

{{-- ── บทที่ 1: เริ่มต้นอย่างไร ─────────────────────────── --}}
<section id="chapter-1" class="nvm-card nvm-box nvm-um-chap" aria-labelledby="ch1-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Chapter 1</small><h2 id="ch1-h">เริ่มต้นอย่างไร</h2></div></div>

    <div class="nvm-um-callout">
        <b>ยินดีต้อนรับสู่ระบบ Thaiprompt-Affiliate!</b>
        <p>ระบบของเราคือโอกาสทองที่จะเปลี่ยนความพยายามของคุณให้กลายเป็นรายได้ที่มั่นคง ไม่ว่าคุณจะเป็นมือใหม่หรือมืออาชีพ เรามีเครื่องมือและระบบที่จะช่วยให้คุณประสบความสำเร็จ</p>
    </div>

    <h3 class="nvm-um-h3">ขั้นตอนเริ่มต้น 5 ขั้นตอน</h3>
    <ol class="nvm-steps nvm-steps--lg">
        @foreach($startSteps as [$title, $desc])
            <li><div><b>{{ $title }}</b><p>{{ $desc }}</p></div></li>
        @endforeach
    </ol>

    <div class="nvm-um-callout is-tip">
        <b>เคล็ดลับสำหรับมือใหม่</b>
        <ul class="nvm-um-list">
            @foreach($newbieTips as [$bold, $rest])
                <li><strong>{{ $bold }}</strong> {{ $rest }}</li>
            @endforeach
        </ul>
    </div>
</section>

{{-- ── บทที่ 2: ระบบทำงานอย่างไร ────────────────────────── --}}
<section id="chapter-2" class="nvm-card nvm-box nvm-um-chap" aria-labelledby="ch2-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Chapter 2</small><h2 id="ch2-h">ระบบทำงานอย่างไร</h2></div></div>
    <p class="nvm-desc">ระบบของเราใช้โมเดล <strong>MLM (Multi-Level Marketing)</strong> แบบผสมผสาน ที่ให้คุณได้รับค่าตอบแทนทั้งจากยอดขายตัวเองและจากทีมงาน</p>

    <h3 class="nvm-um-h3">2 ระบบหลัก</h3>
    <div class="nvm-um-grid nvm-um-grid--wide">
        <div class="nvm-um-panel">
            <b class="nvm-um-panel__t">1. Unilevel (แนวตั้ง)</b>
            <p class="nvm-desc">คุณได้ค่าคอมจากยอดขายของทีมลูกสาขาลงไปหลายระดับ (ขึ้นอยู่กับแผน)</p>
            <div class="nvm-um-tree">
                <span class="nvm-um-node is-you">คุณ</span>
                <small>↓ Level 1 (10%)</small>
                <div><span class="nvm-um-node">A</span><span class="nvm-um-node">B</span></div>
                <small>↓ Level 2 (5%)</small>
                <div><span class="nvm-um-node is-sm">C</span><span class="nvm-um-node is-sm">D</span><span class="nvm-um-node is-sm">E</span></div>
            </div>
        </div>
        <div class="nvm-um-panel">
            <b class="nvm-um-panel__t">2. Binary (แนวคู่)</b>
            <p class="nvm-desc">คุณได้โบนัสเมื่อทีมซ้าย-ขวาจับคู่กัน (Pair Matching)</p>
            <div class="nvm-um-tree">
                <span class="nvm-um-node is-you">คุณ</span>
                <div class="nvm-um-legs">
                    <span><small>ซ้าย</small><span class="nvm-um-node">3 คน</span><small class="nvm-num">30,000 PV</small></span>
                    <span><small>ขวา</small><span class="nvm-um-node">5 คน</span><small class="nvm-num">50,000 PV</small></span>
                </div>
                <span class="nvm-chip nvm-chip--ok">จับคู่ 3 คู่ = 300 บาท</span>
            </div>
        </div>
    </div>

    <div class="nvm-um-callout is-tip">
        <b>ตัวอย่างการคำนวณ</b>
        <dl class="nvm-um-ex">
            <dt>สมมติ: ลูกทีม Level 1 ขายได้ 10,000 บาท (= 10,000 PV)</dt>
            <dd>คุณได้ค่าคอม Unilevel = 10,000 × 10% = <strong>1,000 บาท</strong></dd>
            <dt>สมมติ: ทีมซ้าย-ขวาจับคู่กันได้ 5 คู่</dt>
            <dd>คุณได้โบนัส Binary = 5 × 100 = <strong>500 บาท</strong></dd>
        </dl>
        <p class="nvm-um-sum">รวมรายได้ = 1,000 + 500 = <b class="nvm-num">1,500 บาท</b></p>
    </div>
</section>

{{-- ── บทที่ 3: 4 ช่องทางรายได้ ─────────────────────────── --}}
<section id="chapter-3" class="nvm-card nvm-box nvm-um-chap" aria-labelledby="ch3-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Chapter 3</small><h2 id="ch3-h">4 ช่องทางรายได้</h2></div></div>
    <p class="nvm-desc">คุณสามารถสร้างรายได้จาก <strong>4 ช่องทางหลัก</strong> ที่ทำงานไปพร้อมกัน ยิ่งคุณพัฒนาทีมมากเท่าไร รายได้ก็จะเพิ่มขึ้นทวีคูณ!</p>

    <div class="nvm-um-streams">
        @foreach($streams as $i => [$icon, $title, $lead, $tableHead, $rows, $exHead, $exLine, $exSum])
            <article class="nvm-um-panel">
                <div class="nvm-um-panel__h">
                    <span class="nvm-um-ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $icon }}"/></svg></span>
                    <div><b class="nvm-um-panel__t">{{ $title }}</b><p class="nvm-desc">{{ $lead }}</p></div>
                </div>
                @if($i === 2)
                    <ul class="nvm-um-ranklist" aria-label="โบนัสตามยศ 8 ระดับ">
                        @foreach($rankBonus as [$rk, $bonus, $clr])
                            <li style="--c: {{ $clr }}"><span>{{ $rk }}</span><b class="nvm-num">{{ $bonus }}</b></li>
                        @endforeach
                    </ul>
                @elseif(count($rows))
                    <dl class="nvm-kv nvm-um-kv">
                        <dt class="nvm-um-kv__h">{{ $tableHead }}</dt><dd></dd>
                        @foreach($rows as [$k, $v])
                            <dt>{{ $k }}</dt><dd class="nvm-num">{{ $v }}</dd>
                        @endforeach
                    </dl>
                @endif
                <div class="nvm-um-callout is-ok">
                    <b>{{ $exHead }}</b>
                    <p>{{ $exLine }}<br><strong>{{ $exSum }}</strong></p>
                </div>
            </article>
        @endforeach
    </div>

    <div class="nvm-um-total">
        <h3>ตัวอย่างรายได้รวม 1 เดือน (ยศ Gold)</h3>
        <div class="nvm-minis">
            @foreach($goldExample as [$lbl, $amt])
                <div class="nvm-mini"><small>{{ $lbl }}</small><b class="nvm-num">{{ $amt }}</b></div>
            @endforeach
        </div>
        <div class="nvm-um-total__sum">
            <small>รายได้รวมทั้งหมด</small>
            <b class="nvm-num">31,000 บาท</b>
            <small>*ตัวเลขขึ้นอยู่กับยอดขายและทีมงานของคุณ</small>
        </div>
    </div>
</section>

{{-- ── บทที่ 4: ระบบยศและอันดับ ─────────────────────────── --}}
<section id="chapter-4" class="nvm-card nvm-box nvm-um-chap" aria-labelledby="ch4-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Chapter 4</small><h2 id="ch4-h">ระบบยศและอันดับ</h2></div></div>
    <p class="nvm-desc">ระบบยศช่วยให้คุณมีเป้าหมายที่ชัดเจน และรับสิทธิพิเศษมากขึ้นเมื่อคุณพัฒนาทีม</p>

    <div class="nvm-um-ranks">
        @foreach($ranks as $i => [$name, $desc, $clr, $conds, $rewards, $perks])
            <article class="nvm-um-rank" style="--c: {{ $clr }}">
                <div class="nvm-um-rank__h">
                    <span class="nvm-um-rank__no nvm-num">{{ $i + 1 }}</span>
                    <div><b>{{ $name }}</b><small>{{ $desc }}</small></div>
                </div>
                <div class="nvm-um-rank__cols">
                    @foreach([['เงื่อนไข', $conds], ['รางวัล', $rewards], ['สิทธิพิเศษ', $perks]] as [$colTitle, $items])
                        <div>
                            <small class="nvm-um-rank__lbl">{{ $colTitle }}</small>
                            <ul class="nvm-um-list">
                                @foreach($items as $it)<li>{{ $it }}</li>@endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>

    <div class="nvm-um-callout is-tip">
        <b>เคล็ดลับการขึ้นยศเร็ว</b>
        <ol class="nvm-um-list is-num">
            @foreach($rankTips as [$bold, $rest])
                <li><strong>{{ $bold }}</strong> {{ $rest }}</li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ── บทที่ 5: สร้างทีมที่แข็งแกร่ง ────────────────────── --}}
<section id="chapter-5" class="nvm-card nvm-box nvm-um-chap" aria-labelledby="ch5-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Chapter 5</small><h2 id="ch5-h">สร้างทีมที่แข็งแกร่ง</h2></div></div>
    <p class="nvm-desc">ความสำเร็จในระบบ MLM ไม่ได้มาจากคุณคนเดียว แต่มาจาก<strong>ความแข็งแกร่งของทีม</strong> ยิ่งทีมแข็งแกร่ง คุณก็ยิ่งร่ำรวย!</p>

    <h3 class="nvm-um-h3">7 ขั้นตอนสร้างทีมแข็งแกร่ง</h3>
    <ol class="nvm-steps nvm-steps--lg">
        @foreach($teamSteps as [$title, $intro, $items, $avoid])
            <li>
                <div>
                    <b>{{ $title }}</b>
                    <p>{{ $intro }}</p>
                    <ul class="nvm-um-list is-check">
                        @foreach($items as $it)<li>{{ $it }}</li>@endforeach
                        @foreach($avoid as $it)<li class="is-no">{{ $it }}</li>@endforeach
                    </ul>
                </div>
            </li>
        @endforeach
    </ol>

    <h3 class="nvm-um-h3">กฎทอง 3 ข้อของการสร้างทีม</h3>
    <div class="nvm-um-grid">
        @foreach($goldenRules as [$gt, $gd])
            <div class="nvm-um-panel"><b class="nvm-um-panel__t">{{ $gt }}</b><p class="nvm-desc">{{ $gd }}</p></div>
        @endforeach
    </div>
</section>

{{-- ── บทที่ 6-9: การ์ดสรุป ─────────────────────────────── --}}
<div class="nvm-um-grid nvm-um-grid--wide">
    @foreach($shortChapters as [$anchor, $icon, $cno, $cname, $tips])
        <section id="{{ $anchor }}" class="nvm-card nvm-box nvm-um-chap" aria-labelledby="{{ $anchor }}-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">{{ $cno }}</small><h2 id="{{ $anchor }}-h">{{ $cname }}</h2></div><span class="nvm-um-ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $icon }}"/></svg></span></div>
            <dl class="nvm-um-tips">
                @foreach($tips as [$tt, $td])
                    <div><dt>{{ $tt }}</dt><dd>{{ $td }}</dd></div>
                @endforeach
            </dl>
        </section>
    @endforeach
</div>

{{-- ── บทที่ 10: FAQ ────────────────────────────────────── --}}
<section id="chapter-10" class="nvm-card nvm-box nvm-um-chap" aria-labelledby="ch10-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Chapter 10</small><h2 id="ch10-h">คำถามที่พบบ่อย (FAQ)</h2></div></div>
    <div class="nvm-um-faq">
        @foreach($faqs as $i => [$q, $a])
            <details @if($i === 0) open @endif>
                <summary><span class="nvm-num">Q{{ $i + 1 }}</span>{{ $q }}<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></summary>
                <p>{{ $a }}</p>
            </details>
        @endforeach
    </div>
</section>

{{-- ── ชวนเริ่มต้น ──────────────────────────────────────── --}}
<section class="nvm-card nvm-refer nvm-box nvm-um-cta" aria-labelledby="nvm-um-wg-cta">
    <img class="nvm-refer__coins" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="120" height="120" loading="lazy">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Ready?</small><h2 id="nvm-um-wg-cta">พร้อมเริ่มต้นแล้วหรือยัง?</h2></div></div>
    <p>คุณมีทุกอย่างที่ต้องการแล้ว ขาดแค่การเริ่มต้น! หากมีคำถาม ติดต่อทีมสนับสนุนได้ทันที</p>
    <div class="nvm-hero__actions">
        <a class="nvm-btn nvm-btn--ghost" href="{{ route('user.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-grid"/></svg>ไปที่แดชบอร์ด</a>
        @if($nvR('user.mlm.income-simulator'))
            <a class="nvm-btn nvm-btn--gold" href="{{ route('user.mlm.income-simulator') }}"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>คำนวณรายได้</a>
        @endif
    </div>
</section>

<p class="nvm-um-foot">© {{ date('Y') }} Thaiprompt-Affiliate · เส้นทางเศรษฐี เวอร์ชั่น 2.0 · อัปเดต: ระบบยศ 8 ระดับ + Commission Rates ใหม่<br>สงวนลิขสิทธิ์ ห้ามทำซ้ำหรือเผยแพร่โดยไม่ได้รับอนุญาต</p>
@endsection
