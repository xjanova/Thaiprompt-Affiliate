@extends('layouts.user-v4')

@section('title', 'เส้นทางเศรษฐี - คู่มือสู่ความร่ำรวย')

@php
    // ── สารบัญด่วน (บทเรียน 10 บท) ────────────────────────────
    $toc = [
        ['#chapter-1',  '🚀', 'บทที่ 1',  'เริ่มต้นอย่างไร'],
        ['#chapter-2',  '💡', 'บทที่ 2',  'ระบบทำงานอย่างไร'],
        ['#chapter-3',  '💰', 'บทที่ 3',  '4 ช่องทางรายได้'],
        ['#chapter-4',  '⭐', 'บทที่ 4',  'ระบบยศและอันดับ'],
        ['#chapter-5',  '👥', 'บทที่ 5',  'สร้างทีมที่แข็งแกร่ง'],
        ['#chapter-6',  '📈', 'บทที่ 6',  'กลยุทธ์ขายที่ได้ผล'],
        ['#chapter-7',  '💳', 'บทที่ 7',  'กระเป๋าเงินและถอนเงิน'],
        ['#chapter-8',  '👑', 'บทที่ 8',  'เป็นแม่ทีมมืออาชีพ'],
        ['#chapter-9',  '🎓', 'บทที่ 9',  'เคล็ดลับและคำแนะนำ'],
        ['#chapter-10', '❓', 'บทที่ 10', 'คำถามที่พบบ่อย'],
    ];

    // ── ยศ 8 ระดับ (ข้อมูลเต็ม) ───────────────────────────────
    // [icon, ชื่อ, คำโปรย, สีหลัก, เงื่อนไข[], รางวัล[], สิทธิพิเศษ[]]
    $ranks = [
        ['🥉', 'Bronze (สำริด)', 'ยศเริ่มต้นสำหรับสมาชิกใหม่ทุกคน', '#b07a3c',
            ['ไม่มีเงื่อนไข', 'สมัครสมาชิกได้ทันที', 'เริ่มต้นทุกคนที่ Bronze'],
            ['โบนัสต้อนรับ 100 บาท', 'Commission Rate: 5%', 'Unilevel 3 ชั้น'],
            ['เข้าถึงระบบ MLM', 'ลิงก์แนะนำส่วนตัว', 'ถอนเงินได้ 2 ครั้ง/เดือน'],
        ],
        ['🥈', 'Silver (เงิน)', 'พันธมิตรที่กำลังเติบโต', '#8a93a0',
            ['รับคะแนน 100 แต้ม', 'แนะนำ 5 คน', 'ยอดขาย 10,000 บาท', 'รักษายอด 50 PV/เดือน'],
            ['โบนัส 500 บาท (ครั้งเดียว)', 'Commission Rate: 7.5%', 'Multiplier 1.25x', 'Unilevel 4 ชั้น'],
            ['VIP Support', 'ลดค่าธรรมเนียมถอน 5%', 'ถอนเงินได้ 4 ครั้ง/เดือน'],
        ],
        ['🥇', 'Gold (ทอง)', 'พันธมิตรที่ประสบความสำเร็จ คุณเป็นผู้นำแล้ว!', '#e0a52e',
            ['รับคะแนน 500 แต้ม', 'แนะนำ 20 คน', 'ยอดขาย 50,000 บาท', 'สมาชิก Active 10 คน', 'รักษายอด 100 PV/เดือน'],
            ['โบนัส 2,000 บาท (ครั้งเดียว)', 'โบนัสรายเดือน 500 บาท', 'Commission Rate: 10%', 'Multiplier 1.5x', 'Unilevel 5 ชั้น'],
            ['VIP Support', 'Priority Withdrawal', 'ลดค่าธรรมเนียมถอน 10%', 'ถอนเงินได้ 8 ครั้ง/เดือน'],
        ],
        ['💎', 'Platinum (แพลตินัม)', 'พันธมิตรระดับยอดเยี่ยม ผู้สร้างธุรกิจตัวจริง!', '#3fa6b8',
            ['รับคะแนน 2,000 แต้ม', 'แนะนำ 50 คน', 'ยอดขาย 200,000 บาท', 'สมาชิก Active 25 คน', 'ยอดขายทีม 500,000 บาท'],
            ['โบนัส 10,000 บาท (ครั้งเดียว)', 'โบนัสรายเดือน 2,000 บาท', 'Commission Rate: 15%', 'Multiplier 2.0x', 'Unilevel 6 ชั้น'],
            ['Exclusive Products', 'Leadership Training', 'ลดค่าธรรมเนียมถอน 20%', 'ถอนเงินได้ 15 ครั้ง/เดือน'],
        ],
        ['💠', 'Diamond (เพชร)', 'พันธมิตรระดับสูง ผลงานโดดเด่น คุณเจิดจรัส!', '#5689b8',
            ['รับคะแนน 10,000 แต้ม', 'แนะนำ 100 คน', 'ยอดขาย 1,000,000 บาท', 'สมาชิก Active 50 คน', 'ยอดขายทีม 2,000,000 บาท'],
            ['โบนัส 50,000 บาท (ครั้งเดียว)', 'โบนัสรายเดือน 5,000 บาท', 'Commission Rate: 20%', 'Multiplier 2.5x', 'Unilevel 7 ชั้น'],
            ['Travel Rewards', 'VIP Support', 'ลดค่าธรรมเนียมถอน 30%', 'ถอนเงินไม่จำกัด'],
        ],
        ['👑', 'Crown (มงกุฎ)', 'ผู้นำระดับเอลิท สวมมงกุฎแห่งความสำเร็จ!', '#c08a2e',
            ['รับคะแนน 25,000 แต้ม', 'แนะนำ 200 คน', 'ยอดขาย 2,500,000 บาท', 'สมาชิก Active 100 คน', 'ยอดขายทีม 5,000,000 บาท', 'มีลูกทีม Diamond 2 คน'],
            ['โบนัส 100,000 บาท (ครั้งเดียว)', 'โบนัสรายเดือน 10,000 บาท', 'Commission Rate: 25%', 'Multiplier 3.0x', 'Unilevel 8 ชั้น'],
            ['🚗 Car Bonus (20,000 บาท/เดือน)', 'ลดค่าธรรมเนียมถอน 40%', 'ถอนเงินไม่จำกัด'],
        ],
        ['🏆', 'Royal (รอยัล)', 'ผู้นำระดับรอยัล คุณอยู่ในกลุ่มราชวงศ์แห่งความสำเร็จ!', '#8d5fc7',
            ['รับคะแนน 50,000 แต้ม', 'แนะนำ 350 คน', 'ยอดขาย 5,000,000 บาท', 'สมาชิก Active 200 คน', 'ยอดขายทีม 10,000,000 บาท', 'มีลูกทีม Crown 3 คน'],
            ['โบนัส 300,000 บาท (ครั้งเดียว)', 'โบนัสรายเดือน 25,000 บาท', 'Commission Rate: 30%', 'Multiplier 4.0x', 'Unilevel 9 ชั้น'],
            ['🏠 House Bonus (50,000 บาท/เดือน)', 'Personal Assistant', 'ลดค่าธรรมเนียมถอน 50%'],
        ],
        ['🌟', 'Legend (ตำนาน)', 'ความสำเร็จสูงสุด! คุณคือตำนาน 🎉', '#d65a8a',
            ['รับคะแนน 100,000 แต้ม', 'แนะนำ 500 คน', 'ยอดขาย 10,000,000 บาท', 'สมาชิก Active 300 คน', 'ยอดขายทีม 25,000,000 บาท', 'มีลูกทีม Royal 3 คน'],
            ['🎉 โบนัส 1,000,000 บาท!', 'โบนัสรายเดือน 100,000 บาท', 'Commission Rate: 35%', 'Multiplier 5.0x', 'Unilevel 10 ชั้น'],
            ['Global Bonus Pool (2% กำไรโลก)', 'ถอนเงินฟรี ไม่มีค่าธรรมเนียม', 'ถอนเงินไม่จำกัด', 'สิทธิพิเศษสูงสุดทุกอย่าง'],
        ],
    ];

    // ── สรุปบท 6-9 (การ์ดย่อ) ─────────────────────────────────
    $shortChapters = [
        ['chapter-6', '📈', 'CHAPTER 6', 'กลยุทธ์ขายที่ได้ผล', '#5689b8', [
            ['🎯 เข้าใจลูกค้า', 'ถามปัญหา ฟังให้ดี แล้วเสนอโซลูชั่น ไม่ใช่ยัดเยียดขาย'],
            ['📱 ใช้โซเชียลมีเดีย', 'โพสต์ content สม่ำเสมอ เล่าเรื่องราวความสำเร็จ ไม่ใช่แค่โฆษณา'],
            ['🤝 สร้างความไว้วางใจ', 'รีวิวจากคนจริง ใช้ผลิตภัณฑ์เองก่อน มีหลักฐานการจ่ายเงิน'],
            ['💬 Follow Up', 'ติดตามลูกค้าที่สนใจ ส่วนใหญ่ปิดการขายที่รอบ 3-5'],
        ]],
        ['chapter-7', '💳', 'CHAPTER 7', 'กระเป๋าเงินและถอนเงิน', '#d9534f', [
            ['💰 ยอดคงเหลือ', 'เช็คยอดเงินแบบ realtime มีทั้งเงินหลัก, โบนัส, คอมมิชชั่น'],
            ['🏦 ถอนเงิน', 'ขั้นต่ำ 500 บาท โอนภายใน 1-3 วันทำการ ผ่านธนาคารหรือ PromptPay'],
            ['📊 ประวัติรายการ', 'ดูรายการรับ-จ่าย รายงานภาษี ดาวน์โหลด PDF ได้'],
            ['🔒 ความปลอดภัย', 'ใช้ PIN, 2FA ปกป้องบัญชี ข้อมูลเข้ารหัสทั้งหมด'],
        ]],
        ['chapter-8', '👑', 'CHAPTER 8', 'เป็นแม่ทีมมืออาชีพ', '#5aa07e', [
            ['📚 เรียนรู้ไม่หยุด', 'เข้าอบรม อ่านหนังสือ ติดตามเทรนด์ใหม่ๆ'],
            ['🎤 พัฒนาทักษะพูด', 'นำเสนอได้ชัดเจน สอนได้น่าฟัง สร้างแรงบันดาลใจ'],
            ['💪 มีวินัย', 'ทำงานสม่ำเสมอ ตรงต่อเวลา เป็นตัวอย่าง'],
            ['❤️ ใส่ใจทีม', 'อยู่เคียงข้าง แก้ปัญหา เป็นพี่เลี้ยงที่ดี'],
        ]],
        ['chapter-9', '🎓', 'CHAPTER 9', 'เคล็ดลับและคำแนะนำ', '#e0a52e', [
            ['⏰ เริ่มทำเลย', 'อย่ารอวันที่ "พร้อม" เพราะไม่มีวันนั้น เริ่มตอนนี้'],
            ['🎯 ตั้งเป้าชัดเจน', 'รายได้เท่าไร เมื่อไร ทำอะไรบ้าง เขียนลงกระดาษ'],
            ['🚫 อย่าท้อง่ายๆ', 'ปฏิเสธ 100 คน ถึงจะปิด 10 คน นี่เป็นเรื่องปกติ'],
            ['📊 ใช้เครื่องมือ', 'ตัวคำนวณรายได้ กราฟทีม รายงานต่างๆ ช่วยได้มาก'],
        ]],
    ];

    // ── FAQ (บทที่ 10) ────────────────────────────────────────
    $faqs = [
        ['Q1: ต้องลงทุนเท่าไรถึงจะเริ่มได้?', 'A: สมัครฟรี! แต่ถ้าซื้อสินค้าเพื่อขาย หรือใช้เอง ราคาเริ่มต้น 500 บาท คุณสามารถเริ่มด้วยการแนะนำคนอื่นโดยไม่ต้องลงทุนเลยก็ได้'],
        ['Q2: ต้องใช้เวลาทำงานเท่าไร?', 'A: ขึ้นอยู่กับเป้าหมาย ถ้าอยากรายได้ 10,000 บาท อาจใช้ 2-3 ชั่วโมง/วัน แต่ถ้าอยากรายได้ 100,000 บาท อาจต้อง fulltime'],
        ['Q3: ถอนเงินได้จริงไหม? นานแค่ไหน?', 'A: ถอนได้จริง 100% โอนภายใน 1-3 วันทำการ มีหลักฐานการจ่ายเงินนับพัน-หมื่นรายการ'],
        ['Q4: ไม่เคยขายของ จะทำได้ไหม?', 'A: ได้แน่นอน! เรามีการอบรม สอนทุกอย่างตั้งแต่เริ่มต้น มีทีมสนับสนุน มีสคริปต์ขายให้ใช้ ไม่ต้องกังวล'],
        ['Q5: ขายได้จริงไหม? มีคนซื้อไหม?', 'A: ขายได้จริง! สินค้าของเรามีคุณภาพ ราคาดี มีคนซื้อจริง มีรีวิวนับพัน ที่สำคัญคือคุณต้องเรียนรู้วิธีขายที่ถูกต้อง'],
        ['Q6: ถ้าไม่มีคนในทีมเลย จะได้เงินไหม?', 'A: ได้! คุณสามารถขายเองและรับค่าคอมจากยอดขายตัวเอง แต่ถ้ามีทีม รายได้จะเพิ่มขึ้นทวีคูณ'],
        ['Q7: ระบบนี้ถูกกฎหมายไหม?', 'A: ถูกต้องตามกฎหมาย 100% เป็นระบบ MLM ที่ถูกต้อง มีการขายสินค้าจริง ไม่ใช่ระบบปิรามิดหลอกลวง'],
        ['Q8: ถ้าทำแล้วไม่ได้เงิน จะทำยังไง?', 'A: ถามตัวเองว่า: 1) ขายหรือยัง? 2) สอนทีมหรือยัง? 3) ทำสม่ำเสมอหรือเปล่า? ถ้าทำครบแล้วยังไม่ได้ ปรึกษาผู้สปอนเซอร์หรือทีมซัพพอร์ตเรา'],
    ];

    // ── ขั้นตอนเริ่มต้น 5 ขั้น ─────────────────────────────────
    $startSteps = [
        ['สมัครสมาชิก', 'กรอกข้อมูลให้ครบถ้วน ใช้ลิงก์แนะนำจากผู้สปอนเซอร์ของคุณ (ถ้ามี) เพื่อเชื่อมโยงเข้ากับทีม'],
        ['ยืนยันตัวตน (KYC)', 'อัพโหลดบัตรประชาชนและรูปถ่ายเพื่อยืนยันตัวตน จะช่วยเพิ่มความน่าเชื่อถือและปลดล็อคฟีเจอร์เต็มรูปแบบ'],
        ['ทำความเข้าใจระบบ', 'อ่านคู่มือนี้ให้จบ ดูวิดีโอสอนใช้งาน และทดลองใช้เครื่องมือต่างๆ เช่น ตัวคำนวณรายได้'],
        ['เริ่มแชร์และขาย', 'คัดลอกลิงก์แนะนำของคุณ แชร์ให้เพื่อนและคนรู้จัก เริ่มสร้างยอดขายแรกของคุณ'],
        ['สร้างทีมและเติบโต', 'เชิญคนอื่นมาร่วมทีม สอนพวกเขาให้ประสบความสำเร็จ และรับค่าคอมจากทั้งทีม'],
    ];

    // ── 7 ขั้นตอนสร้างทีม ─────────────────────────────────────
    $teamSteps = [
        ['1. 🎣 หาคนที่เหมาะสม', 'ไม่ใช่ทุกคนที่จะเหมาะกับระบบ ให้มองหา:', [
            '✅ คนที่อยากหารายได้เสริม มีเวลา มีความมุ่งมั่น',
            '✅ คนที่มีเครือข่ายกว้าง ชอบพูดคุย ชอบแนะนำ',
            '✅ คนที่เรียนรู้ได้เร็ว พัฒนาตัวเองอยู่เสมอ',
            '❌ หลีกเลี่ยงคนที่แค่อยากได้เงินด่วน ไม่อยากทำงาน',
        ]],
        ['2. 🎓 อบรมและถ่ายทอด', 'ลูกทีมต้องเก่งไม่แพ้คุณ:', [
            '📚 จัดอบรมประจำสัปดาห์ สอนระบบ เทคนิคขาย',
            '📱 สร้างกลุ่มไลน์หรือเฟซบุ๊ค แบ่งปันเคล็ดลับ',
            '🎥 บันทึกวิดีโอสอน ให้ลูกทีมดูซ้ำได้',
            '📝 ให้สคริปต์การขาย เทมเพลตโพสต์',
        ]],
        ['3. 💪 สนับสนุนและกระตุ้น', 'อยู่เคียงข้างลูกทีมเสมอ:', [
            '🤝 ช่วยปิดการขายครั้งแรก เป็นกำลังใจ',
            '📞 โทรเช็คความคืบหน้าสัปดาห์ละครั้ง',
            '🏆 มอบรางวัลให้ลูกทีมที่ทำผลงานดี',
            '❤️ ให้คำปรึกษา แก้ปัญหาอย่างจริงใจ',
        ]],
        ['4. 📊 ติดตามและวิเคราะห์', 'ใช้ข้อมูลวางแผน:', [
            '📈 ดู Dashboard ของทีมทุกวัน รู้ว่าใครทำดี ใครติดปัญหา',
            '🎯 ตั้งเป้าหมายรายสัปดาห์ รายเดือน',
            '🔍 วิเคราะห์ว่ากลยุทธ์ไหนได้ผล ไหนไม่ได้ผล',
            '📉 หาสาเหตุที่ยอดขายลด แก้ไขทันที',
        ]],
        ['5. 🌟 สร้างวัฒนธรรมทีม', 'ทีมที่ดีต้องมีความรู้สึกเป็นครอบครัว:', [
            '🎉 จัดกิจกรรมทีมบิลดิ้ง ปาร์ตี้ฉลองความสำเร็จ',
            '🏅 มีระบบยกย่องคนทำดี ให้เกียรติ',
            '💬 สร้างบรรยากาศแห่งการช่วยเหลือซึ่งกันและกัน',
            '🎯 มีวิสัยทัศน์ร่วม ทุกคนรู้ว่าทีมไปทางไหน',
        ]],
        ['6. 🔄 ทำให้ระบบทำงานอัตโนมัติ', 'ลดการพึ่งพาคุณคนเดียว:', [
            '🤖 ใช้ระบบอัตโนมัติ เช่น chatbot ตอบคำถามลูกค้า',
            '📚 สร้างคลังความรู้ ให้ทีมหาข้อมูลเองได้',
            '👥 ฝึกลูกทีมเก่งๆ ให้เป็นผู้นำรุ่นใหม่',
            '⚙️ มีระบบรายงานผลอัตโนมัติ',
        ]],
        ['7. 🚀 ขยายทีมอย่างต่อเนื่อง', 'อย่าหยุดเติบโต:', [
            '🌱 หาสมาชิกใหม่ทุกสัปดาห์ อย่าหยุดหา',
            '🎓 ส่งเสริมลูกทีมให้หาคนของตัวเอง',
            '🌍 ขยายไปพื้นที่ใหม่ จังหวัดใหม่',
            '📱 ใช้โซเชียลมีเดีย ทำ content marketing',
        ]],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── HERO ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:18px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:56px; height:56px; border-radius:18px; font-size:26px;"><i class="fas fa-book-reader" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:220px;">
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,28px); font-weight:800; margin:0;">💰 เส้นทางเศรษฐี</h1>
                <div style="font-size:13px; color:var(--ink2); margin-top:3px;">คู่มือสู่ความร่ำรวย ด้วยระบบ Affiliate ของเรา</div>
            </div>
            <a href="{{ route('user.dashboard') }}" class="tp-icon-btn" title="กลับหน้าหลัก"><i class="fas fa-arrow-left"></i></a>
        </div>
        {{-- สถิติย่อ 3 ใบ --}}
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1px; box-shadow:var(--inset-sm);">
            @foreach([['📚 10','บทเรียน'],['🎯 100%','จากมือใหม่สู่แม่ทีม'],['💎 8','ระดับยศ']] as [$big, $small])
                <div style="text-align:center; padding:16px 8px;">
                    <div class="tp-num" style="font-size:clamp(16px,3.5vw,22px); font-weight:800; color:var(--deep1);">{{ $big }}</div>
                    <div style="font-size:11.5px; color:var(--ink2); margin-top:2px;">{{ $small }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── สารบัญด่วน ───────────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:14px;">🧭 สารบัญด่วน</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px;">
            @foreach($toc as [$href, $emoji, $no, $name])
                <a href="{{ $href }}" class="tp-card tp-card-hover" style="display:flex; align-items:center; gap:12px; padding:14px; text-decoration:none; color:inherit;">
                    <span class="tp-tile" style="width:44px; height:44px; border-radius:14px; font-size:22px;">{{ $emoji }}</span>
                    <div>
                        <div style="font-weight:700; font-size:13.5px; color:var(--deep1);">{{ $no }}</div>
                        <div style="font-size:12.5px; color:var(--ink2);">{{ $name }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- ── บทที่ 1: เริ่มต้นอย่างไร ─────────────────────────── --}}
    <div id="chapter-1" class="tp-card" style="padding:24px; scroll-margin-top:90px;">
        <div style="display:flex; align-items:center; gap:14px; margin-bottom:18px;">
            <span class="tp-tile" style="width:54px; height:54px; border-radius:16px; font-size:28px;">🚀</span>
            <div>
                <div style="font-size:11px; font-weight:700; letter-spacing:.5px; color:var(--ink2);">CHAPTER 1</div>
                <h2 class="tp-num" style="font-size:clamp(20px,4vw,28px); font-weight:800; margin:0; color:var(--deep1);">เริ่มต้นอย่างไร</h2>
            </div>
        </div>

        <div style="padding:18px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid var(--accent1); margin-bottom:20px;">
            <div style="font-weight:700; font-size:16px; margin-bottom:8px;">🎯 ยินดีต้อนรับสู่ระบบ Thaiprompt-Affiliate!</div>
            <p style="margin:0; color:var(--ink2); line-height:1.7; font-size:14px;">
                ระบบของเราคือโอกาสทองที่จะเปลี่ยนความพยายามของคุณให้กลายเป็นรายได้ที่มั่นคง
                ไม่ว่าคุณจะเป็นมือใหม่หรือมืออาชีพ เรามีเครื่องมือและระบบที่จะช่วยให้คุณประสบความสำเร็จ
            </p>
        </div>

        <h3 class="tp-section-h" style="margin-bottom:14px;">📝 ขั้นตอนเริ่มต้น 5 ขั้นตอน</h3>
        <div style="display:flex; flex-direction:column; gap:12px;">
            @foreach($startSteps as $i => [$title, $desc])
                <div style="display:flex; gap:14px; padding:16px; border-radius:14px; box-shadow:var(--inset-sm);">
                    <span class="tp-tile" style="flex-shrink:0; width:42px; height:42px; border-radius:50%; font-size:18px; font-weight:800;">{{ $i + 1 }}</span>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:700; font-size:15px; margin-bottom:4px;">{{ $title }}</div>
                        <p style="margin:0; color:var(--ink2); font-size:13.5px; line-height:1.6;">{{ $desc }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top:20px; padding:18px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
            <div style="font-weight:700; font-size:16px; margin-bottom:12px;">💡 เคล็ดลับสำหรับมือใหม่</div>
            <div style="display:flex; flex-direction:column; gap:8px; color:var(--ink2); font-size:13.5px; line-height:1.6;">
                <div><span style="color:#e0a52e; font-weight:800; margin-right:6px;">•</span><strong style="color:var(--ink);">เริ่มจากวงใกล้:</strong> ขายให้คนรู้จักก่อน เพราะพวกเขาเชื่อใจคุณ</div>
                <div><span style="color:#e0a52e; font-weight:800; margin-right:6px;">•</span><strong style="color:var(--ink);">เรียนรู้ผลิตภัณฑ์:</strong> ทดลองใช้เองก่อนแนะนำผู้อื่น</div>
                <div><span style="color:#e0a52e; font-weight:800; margin-right:6px;">•</span><strong style="color:var(--ink);">ตั้งเป้าหมาย:</strong> กำหนดเป้ารายได้รายเดือนและทำงานให้ถึงเป้า</div>
                <div><span style="color:#e0a52e; font-weight:800; margin-right:6px;">•</span><strong style="color:var(--ink);">สม่ำเสมอคือกุญแจ:</strong> ขายทุกวันดีกว่าขายเยอะวันเดียว</div>
            </div>
        </div>
    </div>

    {{-- ── บทที่ 2: ระบบทำงานอย่างไร ────────────────────────── --}}
    <div id="chapter-2" class="tp-card" style="padding:24px; scroll-margin-top:90px;">
        <div style="display:flex; align-items:center; gap:14px; margin-bottom:18px;">
            <span class="tp-tile" style="width:54px; height:54px; border-radius:16px; font-size:28px;">💡</span>
            <div>
                <div style="font-size:11px; font-weight:700; letter-spacing:.5px; color:var(--ink2);">CHAPTER 2</div>
                <h2 class="tp-num" style="font-size:clamp(20px,4vw,28px); font-weight:800; margin:0; color:var(--deep1);">ระบบทำงานอย่างไร</h2>
            </div>
        </div>

        <p style="font-size:15px; color:var(--ink2); line-height:1.7; margin:0 0 18px;">
            ระบบของเราใช้โมเดล <strong style="color:var(--ink);">MLM (Multi-Level Marketing)</strong> แบบผสมผสาน
            ที่ให้คุณได้รับค่าตอบแทนทั้งจากยอดขายตัวเองและจากทีมงาน
        </p>

        <h3 class="tp-section-h" style="margin-bottom:14px;">🔄 2 ระบบหลัก</h3>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px; margin-bottom:20px;">
            {{-- Unilevel --}}
            <div class="tp-card" style="padding:18px; box-shadow:var(--inset-sm);">
                <div style="font-weight:800; font-size:18px; color:#5689b8; margin-bottom:10px;">📊 1. Unilevel (แนวตั้ง)</div>
                <p style="color:var(--ink2); font-size:13.5px; line-height:1.6; margin:0 0 14px;">คุณได้ค่าคอมจากยอดขายของทีมลูกสาขาลงไปหลายระดับ (ขึ้นอยู่กับแผน)</p>
                <div class="tp-card" style="padding:14px; text-align:center;">
                    <span class="tp-pill" style="color:#fff; background:#5689b8; font-weight:700;">คุณ</span>
                    <div style="color:#5689b8; font-size:12.5px; margin:8px 0 6px;">↓ Level 1 (10%)</div>
                    <div style="display:flex; justify-content:center; gap:8px; margin-bottom:8px;">
                        <span class="tp-pill" style="color:#fff; background:color-mix(in srgb,#5689b8 70%,transparent);">A</span>
                        <span class="tp-pill" style="color:#fff; background:color-mix(in srgb,#5689b8 70%,transparent);">B</span>
                    </div>
                    <div style="color:#5689b8; font-size:12.5px; margin-bottom:6px;">↓ Level 2 (5%)</div>
                    <div style="display:flex; justify-content:center; gap:6px;">
                        <span class="tp-pill" style="color:#fff; background:color-mix(in srgb,#5689b8 45%,transparent); font-size:10.5px;">C</span>
                        <span class="tp-pill" style="color:#fff; background:color-mix(in srgb,#5689b8 45%,transparent); font-size:10.5px;">D</span>
                        <span class="tp-pill" style="color:#fff; background:color-mix(in srgb,#5689b8 45%,transparent); font-size:10.5px;">E</span>
                    </div>
                </div>
            </div>

            {{-- Binary --}}
            <div class="tp-card" style="padding:18px; box-shadow:var(--inset-sm);">
                <div style="font-weight:800; font-size:18px; color:var(--deep1); margin-bottom:10px;">⚖️ 2. Binary (แนวคู่)</div>
                <p style="color:var(--ink2); font-size:13.5px; line-height:1.6; margin:0 0 14px;">คุณได้โบนัสเมื่อทีมซ้าย-ขวาจับคู่กัน (Pair Matching)</p>
                <div class="tp-card" style="padding:14px;">
                    <div style="text-align:center; margin-bottom:12px;">
                        <span class="tp-pill tp-pill-gold" style="font-weight:700;">คุณ</span>
                    </div>
                    <div style="display:flex; justify-content:center; gap:24px;">
                        <div style="text-align:center;">
                            <div style="color:var(--ink2); font-size:12.5px; margin-bottom:6px;">ซ้าย</div>
                            <span class="tp-pill" style="color:#fff; background:var(--deep1);">3 คน</span>
                            <div class="tp-num" style="font-size:12px; color:var(--ink2); margin-top:5px;">30,000 PV</div>
                        </div>
                        <div style="text-align:center;">
                            <div style="color:var(--ink2); font-size:12.5px; margin-bottom:6px;">ขวา</div>
                            <span class="tp-pill" style="color:#fff; background:var(--deep1);">5 คน</span>
                            <div class="tp-num" style="font-size:12px; color:var(--ink2); margin-top:5px;">50,000 PV</div>
                        </div>
                    </div>
                    <div style="margin-top:14px; text-align:center; padding:10px; border-radius:12px; box-shadow:var(--inset-sm); color:#5aa07e; font-weight:700; font-size:13px;">✅ จับคู่ 3 คู่ = 300 บาท</div>
                </div>
            </div>
        </div>

        {{-- ตัวอย่างการคำนวณ --}}
        <div style="padding:18px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid var(--deep1);">
            <div style="font-weight:700; font-size:16px; margin-bottom:14px;">📈 ตัวอย่างการคำนวณ</div>
            <div style="display:flex; flex-direction:column; gap:12px;">
                <div class="tp-card" style="padding:14px;">
                    <div style="font-weight:700; font-size:13.5px; margin-bottom:6px;">สมมติ: ลูกทีม Level 1 ขายได้ 10,000 บาท (= 10,000 PV)</div>
                    <div style="color:var(--ink2); font-size:13.5px;">💰 คุณได้ค่าคอม Unilevel = 10,000 × 10% = <strong style="color:#5aa07e;">1,000 บาท</strong></div>
                </div>
                <div class="tp-card" style="padding:14px;">
                    <div style="font-weight:700; font-size:13.5px; margin-bottom:6px;">สมมติ: ทีมซ้าย-ขวาจับคู่กันได้ 5 คู่</div>
                    <div style="color:var(--ink2); font-size:13.5px;">💰 คุณได้โบนัส Binary = 5 × 100 = <strong style="color:#5aa07e;">500 บาท</strong></div>
                </div>
                <div style="padding:14px; border-radius:12px; background:linear-gradient(120deg, color-mix(in srgb,#5aa07e 22%,transparent), transparent 80%); font-weight:700; font-size:15px;">
                    🎉 รวมรายได้ = 1,000 + 500 = <span class="tp-num" style="font-size:22px; color:#5aa07e;">1,500 บาท</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── บทที่ 3: 4 ช่องทางรายได้ ─────────────────────────── --}}
    <div id="chapter-3" class="tp-card" style="padding:24px; scroll-margin-top:90px;">
        <div style="display:flex; align-items:center; gap:14px; margin-bottom:18px;">
            <span class="tp-tile" style="width:54px; height:54px; border-radius:16px; font-size:28px;">💰</span>
            <div>
                <div style="font-size:11px; font-weight:700; letter-spacing:.5px; color:var(--ink2);">CHAPTER 3</div>
                <h2 class="tp-num" style="font-size:clamp(20px,4vw,28px); font-weight:800; margin:0; color:var(--deep1);">4 ช่องทางรายได้</h2>
            </div>
        </div>

        <p style="font-size:15px; color:var(--ink2); line-height:1.7; margin:0 0 18px;">
            คุณสามารถสร้างรายได้จาก <strong style="color:var(--ink);">4 ช่องทางหลัก</strong> ที่ทำงานไปพร้อมกัน
            ยิ่งคุณพัฒนาทีมมากเท่าไร รายได้ก็จะเพิ่มขึ้นทวีคูณ!
        </p>

        <div style="display:flex; flex-direction:column; gap:16px;">
            {{-- 1. Direct Commission --}}
            <div style="padding:18px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid #5689b8;">
                <div style="display:flex; align-items:flex-start; gap:14px;">
                    <span style="font-size:36px; line-height:1;">📊</span>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:800; font-size:17px; color:#5689b8; margin-bottom:6px;">1. ค่าคอมมิชชั่นตรง (Direct Commission)</div>
                        <p style="color:var(--ink2); font-size:13.5px; line-height:1.6; margin:0 0 12px;">รับค่าคอมทันทีจากยอดขายของลูกทีมโดยตรง ตามระบบ Unilevel</p>
                        <div class="tp-card" style="padding:14px; margin-bottom:12px;">
                            <div style="font-weight:700; font-size:13.5px; margin-bottom:10px;">อัตราค่าคอมตามระดับ:</div>
                            @foreach([['Level 1 (ลูกทีมตรง)','10%'],['Level 2 (ลูกของลูก)','5%'],['Level 3','3%'],['Level 4-10','1-2%']] as [$lvl, $pct])
                                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-radius:10px; box-shadow:var(--inset-sm); margin-bottom:8px;">
                                    <span style="font-weight:600; font-size:13px;">{{ $lvl }}</span>
                                    <span class="tp-num" style="font-weight:800; color:#5689b8;">{{ $pct }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div style="padding:12px 14px; border-radius:12px; box-shadow:var(--inset-sm); border-left:4px solid #5aa07e;">
                            <div style="font-weight:700; color:#5aa07e; font-size:13.5px; margin-bottom:4px;">💡 ตัวอย่าง:</div>
                            <div style="color:var(--ink2); font-size:13px; line-height:1.6;">
                                ลูกทีม Level 1 จำนวน 10 คน ซื้อสินค้าคนละ 5,000 บาท/เดือน<br>
                                <strong style="color:var(--ink);">รายได้ของคุณ = 10 × 5,000 × 10% = 5,000 บาท/เดือน</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Binary Matching Bonus --}}
            <div style="padding:18px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid var(--deep1);">
                <div style="display:flex; align-items:flex-start; gap:14px;">
                    <span style="font-size:36px; line-height:1;">⚖️</span>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:800; font-size:17px; color:var(--deep1); margin-bottom:6px;">2. โบนัสจับคู่ (Binary Matching Bonus)</div>
                        <p style="color:var(--ink2); font-size:13.5px; line-height:1.6; margin:0 0 12px;">รับโบนัสพิเศษเมื่อทีมซ้าย-ขวาของคุณจับคู่กัน</p>
                        <div class="tp-card" style="padding:14px; margin-bottom:12px;">
                            <div style="font-weight:700; font-size:13.5px; margin-bottom:10px;">วิธีการคำนวณ:</div>
                            @foreach([['💎 ค่าโบนัสต่อคู่','100 บาท/คู่ (Match 50%)'],['📊 การจับคู่','นับจาก PV ข้างที่น้อยกว่า (Weak Leg)'],['⚡ ไม่จำกัดต่อวัน','ไม่จำกัดจำนวนคู่ (ขึ้นอยู่กับ PV ของทีม)']] as [$t, $d])
                                <div style="padding:10px 12px; border-radius:10px; box-shadow:var(--inset-sm); margin-bottom:8px;">
                                    <div style="font-weight:600; font-size:13px; margin-bottom:2px;">{{ $t }}</div>
                                    <div style="color:var(--ink2); font-size:12.5px;">{{ $d }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div style="padding:12px 14px; border-radius:12px; box-shadow:var(--inset-sm); border-left:4px solid #5aa07e;">
                            <div style="font-weight:700; color:#5aa07e; font-size:13.5px; margin-bottom:4px;">💡 ตัวอย่าง:</div>
                            <div style="color:var(--ink2); font-size:13px; line-height:1.6;">
                                ทีมซ้าย 50,000 PV | ทีมขวา 30,000 PV<br>
                                <strong style="color:var(--ink);">รายได้ = 30,000 × 50% × 100 ÷ 100 = 15,000 บาท</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Rank Bonus --}}
            <div style="padding:18px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
                <div style="display:flex; align-items:flex-start; gap:14px;">
                    <span style="font-size:36px; line-height:1;">⭐</span>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:800; font-size:17px; color:#e0a52e; margin-bottom:6px;">3. โบนัสยศ (Rank Bonus)</div>
                        <p style="color:var(--ink2); font-size:13.5px; line-height:1.6; margin:0 0 12px;">รับโบนัสพิเศษเมื่อขึ้นยศใหม่ และรายได้ประจำทุกเดือน</p>
                        <div class="tp-card" style="padding:14px; margin-bottom:12px;">
                            <div style="font-weight:700; font-size:13.5px; margin-bottom:10px;">โบนัสตามยศ (8 ระดับ):</div>
                            @foreach([
                                ['🥉 Bronze (สำริด)','100 บาท (ต้อนรับ) | Commission 5%','#b07a3c'],
                                ['🥈 Silver (เงิน)','500 บาท | Commission 7.5%','#8a93a0'],
                                ['🥇 Gold (ทอง)','2,000 บาท + 500/ด. | 10%','#e0a52e'],
                                ['💎 Platinum (แพลตินัม)','10,000 บาท + 2,000/ด. | 15%','#3fa6b8'],
                                ['💠 Diamond (เพชร)','50,000 บาท + 5,000/ด. | 20%','#5689b8'],
                                ['👑 Crown (มงกุฎ)','100,000 บาท + 10,000/ด. | 25%','#c08a2e'],
                                ['🏆 Royal (รอยัล)','300,000 บาท + 25,000/ด. | 30%','#8d5fc7'],
                                ['🌟 Legend (ตำนาน)','1,000,000 บาท + 100,000/ด. | 35%','#d65a8a'],
                            ] as [$rk, $bonus, $clr])
                                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; box-shadow:var(--inset-sm); border-left:3px solid {{ $clr }}; margin-bottom:8px;">
                                    <span style="font-weight:700; font-size:13px;">{{ $rk }}</span>
                                    <span class="tp-num" style="font-weight:700; font-size:12px; color:{{ $clr }}; text-align:right;">{{ $bonus }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div style="padding:12px 14px; border-radius:12px; box-shadow:var(--inset-sm); border-left:4px solid #5aa07e;">
                            <div style="font-weight:700; color:#5aa07e; font-size:13.5px; margin-bottom:4px;">💡 ยศสูงสุด Legend:</div>
                            <div style="color:var(--ink2); font-size:13px; line-height:1.6;">
                                เมื่อถึงยศ Legend คุณจะได้รับโบนัส <strong style="color:var(--ink);">1,000,000 บาท</strong> ทันที! พร้อมรายได้ประจำเดือน 100,000 บาท
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. Sponsorship Bonus --}}
            <div style="padding:18px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid #d65a8a;">
                <div style="display:flex; align-items:flex-start; gap:14px;">
                    <span style="font-size:36px; line-height:1;">🎁</span>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:800; font-size:17px; color:#d65a8a; margin-bottom:6px;">4. โบนัสสปอนเซอร์ (Sponsorship Bonus)</div>
                        <p style="color:var(--ink2); font-size:13.5px; line-height:1.6; margin:0 0 12px;">รับโบนัสพิเศษเมื่อเชิญคนใหม่เข้ามาซื้อสินค้า</p>
                        <div class="tp-card" style="padding:14px; margin-bottom:12px;">
                            <div style="font-weight:700; font-size:13.5px; margin-bottom:10px;">อัตราโบนัส:</div>
                            @foreach([['💰 โบนัสต่อคน','10-20% ของยอดซื้อครั้งแรก'],['🔥 โปรโมชั่นพิเศษ','บางช่วงอาจมีโบนัสเพิ่ม เช่น เชิญ 5 คน รับเพิ่ม 5,000 บาท']] as [$t, $d])
                                <div style="padding:10px 12px; border-radius:10px; box-shadow:var(--inset-sm); margin-bottom:8px;">
                                    <div style="font-weight:600; font-size:13px; margin-bottom:2px;">{{ $t }}</div>
                                    <div style="color:var(--ink2); font-size:12.5px;">{{ $d }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div style="padding:12px 14px; border-radius:12px; box-shadow:var(--inset-sm); border-left:4px solid #5aa07e;">
                            <div style="font-weight:700; color:#5aa07e; font-size:13.5px; margin-bottom:4px;">💡 ตัวอย่าง:</div>
                            <div style="color:var(--ink2); font-size:13px; line-height:1.6;">
                                เชิญเพื่อน 10 คน ซื้อสินค้าคนละ 10,000 บาท<br>
                                <strong style="color:var(--ink);">รายได้ = 10 × 10,000 × 15% = 15,000 บาท</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- รวมรายได้ตัวอย่าง --}}
        <div class="tp-card" style="margin-top:18px; padding:24px; text-align:center;
                    background:linear-gradient(135deg, color-mix(in srgb,#5aa07e 22%,transparent), color-mix(in srgb,var(--accent1) 18%,transparent) 90%);">
            <h3 class="tp-num" style="font-size:clamp(18px,4vw,24px); font-weight:800; margin:0 0 18px;">🎯 ตัวอย่างรายได้รวม 1 เดือน (ยศ Gold)</h3>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px; margin-bottom:16px;">
                @foreach([
                    ['Unilevel Commission (ทีม 50 คน)','5,000 บาท'],
                    ['Binary Bonus (50,000 PV จับคู่)','25,000 บาท'],
                    ['Rank Monthly Bonus (Gold)','500 บาท'],
                    ['Direct Referral Bonus (5 คนใหม่)','500 บาท'],
                ] as [$lbl, $amt])
                    <div class="tp-card" style="padding:14px; text-align:left;">
                        <div style="font-size:12px; color:var(--ink2); margin-bottom:4px;">{{ $lbl }}</div>
                        <div class="tp-num" style="font-size:20px; font-weight:800; color:var(--deep1);">{{ $amt }}</div>
                    </div>
                @endforeach
            </div>
            <div class="tp-card" style="padding:20px;">
                <div style="font-size:15px; color:var(--ink2); margin-bottom:6px;">💰 รายได้รวมทั้งหมด</div>
                <div class="tp-num" style="font-size:clamp(32px,8vw,52px); font-weight:800; color:#5aa07e;">31,000 บาท</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:6px;">*ตัวเลขขึ้นอยู่กับยอดขายและทีมงานของคุณ</div>
            </div>
        </div>
    </div>

    {{-- ── บทที่ 4: ระบบยศและอันดับ ─────────────────────────── --}}
    <div id="chapter-4" class="tp-card" style="padding:24px; scroll-margin-top:90px;">
        <div style="display:flex; align-items:center; gap:14px; margin-bottom:18px;">
            <span class="tp-tile" style="width:54px; height:54px; border-radius:16px; font-size:28px;">⭐</span>
            <div>
                <div style="font-size:11px; font-weight:700; letter-spacing:.5px; color:var(--ink2);">CHAPTER 4</div>
                <h2 class="tp-num" style="font-size:clamp(20px,4vw,28px); font-weight:800; margin:0; color:var(--deep1);">ระบบยศและอันดับ</h2>
            </div>
        </div>

        <p style="font-size:15px; color:var(--ink2); line-height:1.7; margin:0 0 18px;">
            ระบบยศช่วยให้คุณมีเป้าหมายที่ชัดเจน และรับสิทธิพิเศษมากขึ้นเมื่อคุณพัฒนาทีม
        </p>

        <div style="display:flex; flex-direction:column; gap:16px;">
            @foreach($ranks as [$icon, $name, $desc, $clr, $conds, $rewards, $perks])
                <div class="tp-card" style="padding:18px; box-shadow:var(--inset-sm); border-left:4px solid {{ $clr }};">
                    <div style="display:flex; align-items:flex-start; gap:14px; margin-bottom:14px;">
                        <span class="tp-tile" style="width:50px; height:50px; border-radius:14px; font-size:26px; background:color-mix(in srgb,{{ $clr }} 22%,transparent);">{{ $icon }}</span>
                        <div>
                            <h3 style="font-size:20px; font-weight:800; margin:0; color:{{ $clr }};">{{ $name }}</h3>
                            <p style="margin:2px 0 0; color:var(--ink2); font-size:13px;">{{ $desc }}</p>
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px;">
                        @foreach([['📊 เงื่อนไข', $conds],['💰 รางวัล', $rewards],['🎁 สิทธิพิเศษ', $perks]] as [$colTitle, $items])
                            <div class="tp-card" style="padding:14px;">
                                <div style="font-weight:700; font-size:13px; margin-bottom:8px;">{{ $colTitle }}</div>
                                <div style="display:flex; flex-direction:column; gap:5px; color:var(--ink2); font-size:12.5px; line-height:1.5;">
                                    @foreach($items as $it)
                                        <div>• {{ $it }}</div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top:20px; padding:18px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
            <div style="font-weight:700; font-size:16px; margin-bottom:12px;">🎯 เคล็ดลับการขึ้นยศเร็ว</div>
            <div style="display:flex; flex-direction:column; gap:8px; color:var(--ink2); font-size:13.5px; line-height:1.6;">
                @foreach([
                    ['1.','สอนลูกทีม:','ช่วยลูกทีมของคุณขึ้นยศ พวกเขาจะช่วยดันคุณขึ้นไปด้วย'],
                    ['2.','สร้างทีมกว้าง:','อย่าพึ่งแค่คนเดียว กระจายทีมให้หลากหลาย'],
                    ['3.','ทำยอดส่วนตัว:','คุณต้องเป็นแบบอย่างที่ดี ขายด้วยตัวเองด้วย'],
                    ['4.','ติดตามทุกวัน:','เช็คความคืบหน้า กระตุ้นทีม และแก้ปัญหาทันที'],
                ] as [$n, $bold, $rest])
                    <div><span style="color:#e0a52e; font-weight:800; margin-right:6px;">{{ $n }}</span><strong style="color:var(--ink);">{{ $bold }}</strong> {{ $rest }}</div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── บทที่ 5: สร้างทีมที่แข็งแกร่ง ────────────────────── --}}
    <div id="chapter-5" class="tp-card" style="padding:24px; scroll-margin-top:90px;">
        <div style="display:flex; align-items:center; gap:14px; margin-bottom:18px;">
            <span class="tp-tile" style="width:54px; height:54px; border-radius:16px; font-size:28px;">👥</span>
            <div>
                <div style="font-size:11px; font-weight:700; letter-spacing:.5px; color:var(--ink2);">CHAPTER 5</div>
                <h2 class="tp-num" style="font-size:clamp(20px,4vw,28px); font-weight:800; margin:0; color:var(--deep1);">สร้างทีมที่แข็งแกร่ง</h2>
            </div>
        </div>

        <p style="font-size:15px; color:var(--ink2); line-height:1.7; margin:0 0 18px;">
            ความสำเร็จในระบบ MLM ไม่ได้มาจากคุณคนเดียว แต่มาจาก<strong style="color:var(--ink);">ความแข็งแกร่งของทีม</strong>
            ยิ่งทีมแข็งแกร่ง คุณก็ยิ่งร่ำรวย!
        </p>

        <h3 class="tp-section-h" style="margin-bottom:14px;">🎯 7 ขั้นตอนสร้างทีมแข็งแกร่ง</h3>
        <div style="display:flex; flex-direction:column; gap:12px;">
            @foreach($teamSteps as $idx => [$title, $intro, $items])
                @php $accent = ['#5689b8','#8d5fc7','#5aa07e','#e0a52e','#d9534f','var(--deep1)','#3fa6b8'][$idx] ?? 'var(--deep1)'; @endphp
                <div style="padding:16px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid {{ $accent }};">
                    <div style="font-weight:800; font-size:16px; color:{{ $accent }}; margin-bottom:6px;">{{ $title }}</div>
                    <p style="color:var(--ink2); font-size:13.5px; margin:0 0 8px;">{{ $intro }}</p>
                    <div style="display:flex; flex-direction:column; gap:5px; color:var(--ink2); font-size:13px; line-height:1.55;">
                        @foreach($items as $it)
                            <div>{{ $it }}</div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top:20px; padding:18px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid #d65a8a;">
            <div style="font-weight:800; font-size:18px; margin-bottom:14px;">👑 กฎทอง 3 ข้อของการสร้างทีม</div>
            <div style="display:flex; flex-direction:column; gap:12px;">
                @foreach([
                    ['1. เป็นแบบอย่างที่ดี','คุณต้องทำในสิ่งที่สอน ถ้าคุณไม่ขาย ลูกทีมก็จะไม่ขาย'],
                    ['2. สอนให้เก่งกว่าคุณ','อย่ากลัวลูกทีมเก่งกว่า ยิ่งเขาเก่ง คุณยิ่งได้ประโยชน์'],
                    ['3. ช่วยพวกเขาประสบความสำเร็จ','เมื่อทีมสำเร็จ คุณก็จะสำเร็จไปด้วย นี่คือ Win-Win แท้ๆ'],
                ] as [$gt, $gd])
                    <div class="tp-card" style="padding:14px;">
                        <div style="font-weight:700; font-size:15px; color:#d65a8a; margin-bottom:4px;">{{ $gt }}</div>
                        <p style="margin:0; color:var(--ink2); font-size:13.5px; line-height:1.6;">{{ $gd }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── บทที่ 6-9: การ์ดสรุป ─────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:16px;">
        @foreach($shortChapters as [$id, $emoji, $cno, $cname, $clr, $tips])
            <div id="{{ $id }}" class="tp-card" style="padding:20px; scroll-margin-top:90px;">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <span class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:22px; background:color-mix(in srgb,{{ $clr }} 22%,transparent);">{{ $emoji }}</span>
                    <div>
                        <div style="font-size:10.5px; font-weight:700; letter-spacing:.5px; color:var(--ink2);">{{ $cno }}</div>
                        <h3 style="font-size:19px; font-weight:800; margin:0;">{{ $cname }}</h3>
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    @foreach($tips as [$tt, $td])
                        <div style="padding:12px 14px; border-radius:12px; box-shadow:var(--inset-sm); border-left:3px solid {{ $clr }};">
                            <div style="font-weight:700; font-size:13.5px; margin-bottom:3px;">{{ $tt }}</div>
                            <p style="margin:0; color:var(--ink2); font-size:12.5px; line-height:1.55;">{{ $td }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- ── บทที่ 10: FAQ ────────────────────────────────────── --}}
    <div id="chapter-10" class="tp-card" style="padding:24px; scroll-margin-top:90px;">
        <div style="display:flex; align-items:center; gap:14px; margin-bottom:18px;">
            <span class="tp-tile" style="width:54px; height:54px; border-radius:16px; font-size:28px;">❓</span>
            <div>
                <div style="font-size:11px; font-weight:700; letter-spacing:.5px; color:var(--ink2);">CHAPTER 10</div>
                <h2 class="tp-num" style="font-size:clamp(20px,4vw,28px); font-weight:800; margin:0; color:var(--deep1);">คำถามที่พบบ่อย (FAQ)</h2>
            </div>
        </div>

        <div style="display:flex; flex-direction:column; gap:12px;">
            @foreach($faqs as [$q, $a])
                <div style="padding:16px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid var(--accent1);">
                    <div style="font-weight:700; font-size:14.5px; margin-bottom:6px; color:var(--deep1);">{{ $q }}</div>
                    <p style="margin:0; color:var(--ink2); font-size:13.5px; line-height:1.65;">{{ $a }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── Call to Action ───────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="text-align:center; padding:40px 24px;
                    background:linear-gradient(135deg, color-mix(in srgb,var(--accent1) 20%,transparent), color-mix(in srgb,var(--deep1) 16%,transparent) 90%);">
            <div style="font-size:56px; margin-bottom:14px;">🚀</div>
            <h2 class="tp-num" style="font-size:clamp(24px,6vw,40px); font-weight:800; margin:0 0 10px;">พร้อมเริ่มต้นแล้วหรือยัง?</h2>
            <p style="font-size:clamp(15px,3vw,20px); color:var(--ink2); margin:0 0 24px;">
                คุณมีทุกอย่างที่ต้องการแล้ว ขาดแค่การเริ่มต้น!
            </p>
            <div style="display:flex; flex-wrap:wrap; gap:12px; justify-content:center;">
                <a href="{{ route('user.dashboard') }}" class="tp-btn"><i class="fas fa-chart-line"></i> 📊 ไปที่แดชบอร์ด</a>
                @if(\Illuminate\Support\Facades\Route::has('user.mlm.income-simulator'))
                    <a href="{{ route('user.mlm.income-simulator') }}" class="tp-btn tp-btn-primary"><i class="fas fa-calculator"></i> 💰 คำนวณรายได้</a>
                @endif
            </div>
            <div style="margin-top:22px; color:var(--ink2); font-size:14px;">หากมีคำถาม ติดต่อทีมสนับสนุนได้ทันที!</div>
        </div>
    </div>

    {{-- ── Footer ───────────────────────────────────────────── --}}
    <div style="text-align:center; color:var(--ink2); font-size:12px; line-height:1.7; padding:8px 0 16px;">
        <div>© {{ date('Y') }} Thaiprompt-Affiliate - เส้นทางเศรษฐี เวอร์ชั่น 2.0</div>
        <div style="font-size:11px; margin-top:2px;">อัปเดต: ระบบยศ 8 ระดับ + Commission Rates ใหม่</div>
        <div style="font-size:11px;">สงวนลิขสิทธิ์ ห้ามทำซ้ำหรือเผยแพร่โดยไม่ได้รับอนุญาต</div>
    </div>

</div>
@endsection
