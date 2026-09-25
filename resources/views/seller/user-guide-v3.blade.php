@extends('layouts.seller-v4')

{{--
 | คู่มือการใช้งานสำหรับผู้ขาย (Theme V4)
 | (2026-09-25) เขียนเนื้อหาใหม่ให้ตรงกับระบบจริงตอนเปิดตัว — ตัดตัวเลขสมมติ (40+ บทเรียน / วิดีโอ / 24/7)
 |   และคำตอบ FAQ เก่าที่ไม่ตรงกับนโยบายปัจจุบัน (ค่าธรรมเนียม / รอบโอนเงิน / จำนวนสินค้า)
 | ลิงก์ทุกอันผ่าน Route::has() — ถ้าหน้าไหนยังไม่มีจะไม่แสดงลิงก์ (กันหน้า 500)
 --}}

@section('title', 'คู่มือผู้ขาย')

@php
    $link = fn (string $name) => \Illuminate\Support\Facades\Route::has($name) ? route($name) : null;
    $supportEmail = class_exists(\App\Support\ContactInfo::class) ? \App\Support\ContactInfo::supportEmail() : null;

    // [id, ไอคอน, หัวข้อแท็บ, รายการ [หัวข้อ, คำอธิบาย, ชื่อ route (ถ้ามี)]]
    $sections = [
        ['start', '🚀', 'เริ่มต้นขาย', [
            ['ตั้งค่าร้านค้า', 'ใส่ชื่อร้าน โลโก้ ที่อยู่ เบอร์โทร และช่องทางจัดส่ง ให้ลูกค้าเชื่อมั่นตั้งแต่เข้าร้าน', 'seller.store.settings'],
            ['เลือกแพ็กเกจร้าน', 'แพ็กเกจกำหนดจำนวนสินค้าและเครื่องมือที่ใช้ได้ อัปเกรดได้ทุกเมื่อ', 'seller.packages'],
            ['เพิ่มสินค้าชิ้นแรก', 'ใส่รูปชัด ๆ ชื่อที่ค้นหาเจอง่าย ราคา สต็อก และน้ำหนักสำหรับคำนวณค่าส่ง', 'seller.products.create'],
            ['แชร์ลิงก์หน้าร้าน', 'ส่งลิงก์ร้านให้ลูกค้าเก่าและโพสต์ในโซเชียล เพื่อเริ่มมียอดขายและรีวิว', 'seller.dashboard'],
        ]],
        ['products', '📦', 'สินค้า', [
            ['แก้ไขราคาและสต็อก', 'อัปเดตจำนวนคงเหลือให้ตรงเสมอ สินค้าที่สต็อกหมดจะไม่ถูกขายเกิน', 'seller.products.index'],
            ['ตั้งราคาให้มีกำไร', 'ดูต้นทุน ค่าธรรมเนียม และกำไรต่อชิ้นก่อนตั้งราคา', 'seller.pricing.planner'],
            ['รูปสินค้าที่ขายดี', 'พื้นหลังสะอาด แสงสว่าง ถ่ายหลายมุม และมีรูปใช้งานจริงอย่างน้อย 1 รูป', null],
            ['โปรโมทสินค้าใหม่ฟรี', 'ดันสินค้าใหม่ขึ้นหน้า Official Shop ได้ฟรีตามรอบสิทธิ์ของร้าน', 'seller.marketing.select-product'],
        ]],
        ['orders', '🚚', 'ออเดอร์และจัดส่ง', [
            ['รับและยืนยันออเดอร์', 'เมื่อลูกค้าชำระเงินแล้ว ออเดอร์จะเข้าหน้า “คำสั่งซื้อ” ให้ยืนยันและเริ่มแพ็ก', 'seller.orders.index'],
            ['ใส่เลขพัสดุ', 'จัดส่งแล้วใส่ขนส่งและเลขพัสดุ ลูกค้าจะติดตามสถานะได้เองและได้รับแจ้งเตือน', 'seller.orders.pending-shipping'],
            ['ส่งด้วยไรเดอร์', 'ร้านที่เปิดส่งด้วยไรเดอร์ เรียกไรเดอร์มารับของที่ร้านได้จากหน้ารายละเอียดออเดอร์', 'seller.store.settings'],
            ['คุยกับลูกค้า', 'ตอบแชทเร็วช่วยปิดการขายและลดการยกเลิก', 'seller.messages.index'],
        ]],
        ['money', '💰', 'รายได้และถอนเงิน', [
            ['รายได้เข้ากระเป๋าเมื่อไร', 'รายได้จากออเดอร์จะแสดงเป็น “รอโอน” และโอนเข้ากระเป๋าร้านหลังลูกค้าได้รับสินค้าและพ้นระยะรอตามที่ระบบกำหนด', 'seller.wallet.index'],
            ['ถอนเงินเข้าบัญชี', 'เพิ่มบัญชีรับเงินแล้วกดถอน ระบบแสดงขั้นต่ำและค่าธรรมเนียมก่อนยืนยันทุกครั้ง', 'seller.wallet.withdraw'],
            ['ค่าธรรมเนียมการขาย (GP)', 'อัตรา GP ขึ้นกับแพ็กเกจร้านและประกาศของแพลตฟอร์ม ดูอัตราที่ใช้กับร้านคุณได้ในหน้าวางแผนราคา', 'seller.pricing.planner'],
        ]],
        ['pos', '🏪', 'ขายหน้าร้าน (POS)', [
            ['เปิดหน้าขาย', 'ขายหน้าร้านผ่านเว็บได้ทันที ค้นหาหรือสแกนบาร์โค้ด แล้วรับเงินสด/QR/โอน', 'seller.pos.terminal'],
            ['ตั้งค่าใบเสร็จและภาษี', 'ใส่ชื่อร้านบนใบเสร็จ อัตรา VAT และวิธีชำระที่รับ', 'seller.pos.settings'],
            ['พิมพ์ฉลากบาร์โค้ด', 'พิมพ์ป้ายราคาติดสินค้า เพื่อให้สแกนขายได้เร็วขึ้น', 'seller.pos.labels.index'],
            ['เชื่อมเครื่อง POS', 'สร้าง API Key เพื่อเชื่อมโปรแกรม POS บนคอมพิวเตอร์หรือแท็บเล็ต', 'seller.pos.terminals'],
            ['พนักงานและกะ', 'เพิ่มพนักงาน ตั้ง PIN เข้าเครื่อง POS และกำหนดกะการทำงาน', 'seller.staff.index'],
        ]],
        ['marketing', '📢', 'การตลาด', [
            ['คูปองร้าน', 'แจกส่วนลดเป็นเปอร์เซ็นต์ บาท หรือส่งฟรี พร้อมกำหนดยอดขั้นต่ำและจำนวนสิทธิ์', 'seller.coupons.index'],
            ['ตอบรีวิวลูกค้า', 'รีวิวดีช่วยให้ร้านติดอันดับ ตอบทุกรีวิวอย่างสุภาพ', 'seller.store-rating.index'],
            ['วิเคราะห์ยอดขาย', 'ดูผู้เข้าชม อัตราการซื้อ สินค้าขายดี และคำแนะนำจาก AI', 'seller.analytics.index'],
            ['รางวัลและร้าน Premium', 'สะสม Trophy และคะแนนร้านเพื่อปลดล็อกสถานะร้าน Premium', 'seller.achievements.index'],
        ]],
    ];

    $faqs = [
        ['ต้องเสียค่าธรรมเนียมการขายเท่าไร?', 'อัตรา GP ขึ้นกับแพ็กเกจร้านและโปรโมชันของแพลตฟอร์มในช่วงนั้น ระบบแสดงอัตราที่ใช้กับร้านคุณและตัวอย่างการคำนวณ “ขาย − GP = รับจริง” ในหน้าตั้งราคา'],
        ['ได้รับเงินจากการขายเมื่อไร?', 'หลังลูกค้าได้รับสินค้า รายได้จะอยู่ในสถานะ “รอโอน” ตามระยะรอที่ระบบกำหนด แล้วโอนเข้ากระเป๋าร้านอัตโนมัติ จากนั้นถอนเข้าบัญชีธนาคารได้'],
        ['ลูกค้ายกเลิกหรือขอคืนเงินทำอย่างไร?', 'ออเดอร์ที่ยังไม่จัดส่งยกเลิกได้จากหน้าออเดอร์ ส่วนการคืนสินค้าหลังจัดส่งให้ติดต่อทีมงานผ่านระบบ Ticket เพื่อความถูกต้องของยอดเงิน'],
        ['ใช้ระบบ POS ต้องมีเครื่องพิเศษไหม?', 'ไม่จำเป็น เปิดหน้าขายบนมือถือหรือคอมพิวเตอร์ได้ทันที ถ้ามีเครื่องสแกนบาร์โค้ดแบบ USB/บลูทูธก็ใช้ร่วมกันได้'],
        ['ระบบพนักงานเสียเงินไหม?', 'ฟรีทุกร้าน เพิ่มพนักงาน แผนก ตำแหน่ง กะงาน และรหัส PIN สำหรับเครื่อง POS ได้'],
    ];
@endphp

@section('content')
<div x-data="{ tab: 'start', q: '' }" style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="คู่มือผู้ขาย" icon="📘" crumb="ร้านค้า · ช่วยเหลือ"
                         subtitle="ขั้นตอนสำคัญตั้งแต่เปิดร้าน ขายของ รับเงิน ไปจนถึงเครื่องมือการตลาด" />

    <div class="tp-card" style="padding:12px;">
        <div style="position:relative;">
            <span aria-hidden="true" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--ink2);"><i class="fas fa-magnifying-glass"></i></span>
            <input type="search" x-model="q" class="tp-input" style="padding-left:40px;" placeholder="ค้นหาในคู่มือ เช่น ถอนเงิน คูปอง เลขพัสดุ" aria-label="ค้นหาในคู่มือ">
        </div>
    </div>

    {{-- แท็บหมวด (ซ่อนเมื่อกำลังค้นหา — แสดงผลลัพธ์ทุกหมวดแทน) --}}
    <div x-show="q.trim() === ''">
        <nav class="tp-card" style="padding:6px; display:flex; gap:4px; overflow-x:auto; scrollbar-width:none;" aria-label="หมวดคู่มือ">
            @foreach($sections as [$sId, $sIcon, $sTitle])
                <button type="button" @click="tab = @js($sId)"
                        style="flex:none; border:0; cursor:pointer; font-family:inherit; padding:9px 13px; border-radius:12px; font-size:12.5px; font-weight:700; white-space:nowrap;"
                        :style="tab === @js($sId) ? { color: 'var(--tp-on-accent, #fff)', background: 'linear-gradient(135deg, var(--accent1), var(--accent2))', boxShadow: 'var(--raise)' } : { color: 'var(--ink2)', background: 'transparent' }">
                    {{ $sIcon }} {{ $sTitle }}
                </button>
            @endforeach
            <button type="button" @click="tab = 'faq'"
                    style="flex:none; border:0; cursor:pointer; font-family:inherit; padding:9px 13px; border-radius:12px; font-size:12.5px; font-weight:700; white-space:nowrap;"
                    :style="tab === 'faq' ? { color: 'var(--tp-on-accent, #fff)', background: 'linear-gradient(135deg, var(--accent1), var(--accent2))', boxShadow: 'var(--raise)' } : { color: 'var(--ink2)', background: 'transparent' }">❓ คำถามพบบ่อย</button>
            <button type="button" @click="tab = 'support'"
                    style="flex:none; border:0; cursor:pointer; font-family:inherit; padding:9px 13px; border-radius:12px; font-size:12.5px; font-weight:700; white-space:nowrap;"
                    :style="tab === 'support' ? { color: 'var(--tp-on-accent, #fff)', background: 'linear-gradient(135deg, var(--accent1), var(--accent2))', boxShadow: 'var(--raise)' } : { color: 'var(--ink2)', background: 'transparent' }">🎧 ติดต่อทีมงาน</button>
        </nav>
    </div>

    {{-- เนื้อหาแต่ละหมวด --}}
    @foreach($sections as [$sId, $sIcon, $sTitle, $sItems])
        <section x-show="q.trim() !== '' || tab === @js($sId)" aria-label="{{ $sTitle }}">
            <div class="tp-section-h" style="margin-bottom:10px;">{{ $sIcon }} {{ $sTitle }}</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:14px;">
                @foreach($sItems as $i => [$iTitle, $iText, $iRoute])
                    @php
                        $iUrl = $iRoute ? $link($iRoute) : null;
                        $haystack = mb_strtolower($iTitle.' '.$iText.' '.$sTitle);
                    @endphp
                    <div class="tp-card" x-show="q.trim() === '' || @js($haystack).includes(q.trim().toLowerCase())" style="padding:0;">
                        <div style="display:flex; gap:12px; align-items:flex-start; padding:16px;">
                            <span class="tp-tile tp-num" style="width:38px; height:38px; font-size:15px; border-radius:12px;" aria-hidden="true">{{ $i + 1 }}</span>
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:800; font-size:14px;">{{ $iTitle }}</div>
                                <div style="font-size:12.5px; color:var(--ink2); line-height:1.65; margin-top:4px;">{{ $iText }}</div>
                                @if($iUrl)
                                    <a href="{{ $iUrl }}" style="display:inline-block; margin-top:8px; font-size:12.5px; font-weight:700; color:var(--deep1); text-decoration:none;">ไปที่หน้านี้ →</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    {{-- คำถามพบบ่อย --}}
    <section x-show="q.trim() !== '' || tab === 'faq'" aria-label="คำถามพบบ่อย">
        <div class="tp-section-h" style="margin-bottom:10px;">❓ คำถามพบบ่อย</div>
        <div style="display:flex; flex-direction:column; gap:10px;">
            @foreach($faqs as $fi => [$question, $answer])
                <div class="tp-card" style="padding:0;" x-data="{ open: {{ $fi === 0 ? 'true' : 'false' }} }"
                     x-show="q.trim() === '' || @js(mb_strtolower($question.' '.$answer)).includes(q.trim().toLowerCase())">
                    <button type="button" @click="open = !open" :aria-expanded="open ? 'true' : 'false'"
                            style="width:100%; display:flex; justify-content:space-between; align-items:center; gap:10px; padding:14px 16px; border:0; background:none; cursor:pointer; font-family:inherit; color:var(--ink); text-align:left;">
                        <span style="font-weight:800; font-size:13.5px;">{{ $question }}</span>
                        <span aria-hidden="true" style="transition:transform .2s ease;" :style="{ transform: open ? 'rotate(180deg)' : 'none' }">⌄</span>
                    </button>
                    <div x-show="open" x-transition.opacity style="padding:0 16px 14px; font-size:13px; line-height:1.7; color:var(--ink2);">{{ $answer }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ติดต่อทีมงาน --}}
    <section x-show="q.trim() === '' && tab === 'support'" aria-label="ติดต่อทีมงาน">
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px;">
            @if($link('user.tickets.create'))
                <a href="{{ $link('user.tickets.create') }}" class="tp-card tp-card-hover" style="text-decoration:none; color:var(--ink); display:flex; gap:12px; align-items:center;">
                    <span class="tp-tile" style="width:46px; height:46px; font-size:21px;" aria-hidden="true">🎫</span>
                    <span><span style="display:block; font-weight:800;">แจ้งปัญหา / ขอความช่วยเหลือ</span><span style="display:block; font-size:12px; color:var(--ink2);">เปิด Ticket ทีมงานตอบกลับในระบบ</span></span>
                </a>
            @endif
            @if($supportEmail)
                <a href="mailto:{{ $supportEmail }}" class="tp-card tp-card-hover" style="text-decoration:none; color:var(--ink); display:flex; gap:12px; align-items:center;">
                    <span class="tp-tile" style="width:46px; height:46px; font-size:21px;" aria-hidden="true">✉️</span>
                    <span><span style="display:block; font-weight:800;">อีเมลทีมงาน</span><span style="display:block; font-size:12px; color:var(--ink2); overflow-wrap:anywhere;">{{ $supportEmail }}</span></span>
                </a>
            @endif
            <div class="tp-card" style="display:flex; gap:12px; align-items:center;">
                <span class="tp-tile" style="width:46px; height:46px; font-size:21px;" aria-hidden="true">🤖</span>
                <span><span style="display:block; font-weight:800;">ถามน้อง Eve</span><span style="display:block; font-size:12px; color:var(--ink2);">กดปุ่มผู้ช่วยมุมจอ ถามยอดขาย ออเดอร์ หรือสต็อกของร้านได้ทันที</span></span>
            </div>
        </div>
    </section>
</div>
@endsection
