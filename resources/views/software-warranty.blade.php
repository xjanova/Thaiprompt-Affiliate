@extends('layouts.frontend-v4')

@section('title', 'การรับประกันซอฟต์แวร์ · ไทยพร๊อมท์')
@section('meta_description', 'การรับประกันซอฟต์แวร์ไทยพร๊อมท์ โดย บริษัท เอ็กซ์แมน เอ็นเตอร์ไพรส์ จำกัด (XMAN ENTERPRISE CO., LTD.)')

@push('styles')
<style>
    .legal-doc { color: var(--ink); line-height: 1.85; font-size: 15px; }
    .legal-doc h2 { font-size: 1.22rem; font-weight: 700; color: var(--ink); margin: 28px 0 12px; display:flex; align-items:center; gap:8px; }
    .legal-doc h3 { font-size: 1.04rem; font-weight: 700; color: var(--deep1); margin: 18px 0 8px; }
    .legal-doc p { margin: 0 0 12px; color: var(--ink2); }
    .legal-doc ul, .legal-doc ol { margin: 0 0 14px; padding-left: 22px; color: var(--ink2); }
    .legal-doc li { margin: 6px 0; }
    .legal-doc a { color: var(--deep1); text-decoration: none; }
    .legal-doc a:hover { text-decoration: underline; }
    .legal-doc strong { color: var(--ink); }
    .legal-doc .company-box { background: var(--surf); box-shadow: var(--inset-sm); border-radius: 14px; padding: 18px 20px; margin: 8px 0 4px; }
</style>
@endpush

@section('content')
<div style="max-width:920px; margin:0 auto; padding:40px clamp(16px,3vw,40px) 60px;">
    <div style="text-align:center; margin-bottom:28px;">
        <div class="tp-tile" style="width:64px; height:64px; border-radius:20px; font-size:30px; margin:0 auto 16px;">🛡️</div>
        <h1 style="font-size:clamp(26px,4vw,34px); font-weight:800; color:var(--ink); margin:0;">การรับประกันซอฟต์แวร์</h1>
        <p class="tp-muted" style="margin:8px 0 0; font-size:13px;">รับประกันโดย บริษัท เอ็กซ์แมน เอ็นเตอร์ไพรส์ จำกัด · เริ่มมีผล มิถุนายน 2569</p>
    </div>

    <div class="tp-card legal-doc" style="padding:clamp(20px,4vw,40px);">
        <p>เอกสารฉบับนี้ระบุเงื่อนไขการรับประกันซอฟต์แวร์ของแพลตฟอร์ม <strong>ไทยพร๊อมท์ (Thaiprompt / TP-Affiliate)</strong> ซึ่งพัฒนาและให้การรับประกันโดย <strong>บริษัท เอ็กซ์แมน เอ็นเตอร์ไพรส์ จำกัด</strong> (XMAN ENTERPRISE CO., LTD.) ผู้ใช้บริการที่ยอมรับข้อกำหนดการใช้งานถือว่าได้รับทราบและยอมรับเงื่อนไขการรับประกันนี้แล้ว</p>

        <h2><span>🛡️</span> 1. ขอบเขตการรับประกัน</h2>
        <p>บริษัทรับประกันว่าซอฟต์แวร์จะทำงานได้ตามคุณสมบัติหลักที่ระบุไว้ในเอกสารหรือหน้าบริการอย่างมีนัยสำคัญ ภายใต้การใช้งานตามปกติและตามวัตถุประสงค์ที่กำหนด การรับประกันครอบคลุม:</p>
        <ul>
            <li>การทำงานของฟังก์ชันหลักของระบบตามที่ประกาศไว้</li>
            <li>การแก้ไขข้อบกพร่อง (Bug Fixes) ที่กระทบต่อการใช้งานหลัก</li>
            <li>การปรับปรุงด้านความปลอดภัย (Security Updates) ตามความเหมาะสม</li>
            <li>ความพร้อมให้บริการของระบบ (Availability) ตามมาตรฐานที่บริษัทกำหนด</li>
        </ul>

        <h2><span>⏱️</span> 2. ระยะเวลารับประกัน</h2>
        <p>การรับประกันมีผลตลอดระยะเวลาที่ผู้ใช้มีสิทธิ์ใช้งานหรือสมัครใช้บริการ (ตามแพ็กเกจ/สัญญา/ใบอนุญาตที่ถือครอง) เว้นแต่จะมีการระบุระยะเวลาเฉพาะไว้เป็นอย่างอื่นในข้อตกลงแยกต่างหาก</p>

        <h2><span>✅</span> 3. สิ่งที่ครอบคลุม</h2>
        <ul>
            <li>การให้บริการแก้ไขปัญหาที่เกิดจากตัวซอฟต์แวร์โดยตรง</li>
            <li>การอัปเดตเวอร์ชันเพื่อแก้ไขข้อบกพร่องและช่องโหว่ความปลอดภัย</li>
            <li>การสนับสนุนทางเทคนิคผ่านช่องทางที่บริษัทกำหนด</li>
        </ul>

        <h2><span>🚫</span> 4. สิ่งที่ไม่ครอบคลุม</h2>
        <p>การรับประกันนี้ไม่ครอบคลุมความเสียหายหรือความบกพร่องอันเกิดจาก:</p>
        <ul>
            <li>การใช้งานผิดวิธี ดัดแปลง แก้ไขโค้ด หรือใช้งานนอกเหนือวัตถุประสงค์ที่กำหนด</li>
            <li>ปัญหาจากฮาร์ดแวร์ เครือข่าย ระบบปฏิบัติการ หรือซอฟต์แวร์ของบุคคลที่สาม</li>
            <li>บริการภายนอกของบุคคลที่สาม (เช่น ผู้ให้บริการชำระเงิน, AI, คลาวด์, บล็อกเชน) ที่อยู่นอกการควบคุมของบริษัท</li>
            <li>เหตุสุดวิสัย (Force Majeure) ภัยธรรมชาติ ไฟฟ้าดับ หรือเหตุที่อยู่นอกเหนือการควบคุม</li>
            <li>ข้อมูลหรือเนื้อหาที่ผู้ใช้นำเข้าเอง</li>
        </ul>

        <h2><span>🔧</span> 5. การสนับสนุนและการอัปเดต</h2>
        <p>บริษัทจะพิจารณาออกอัปเดตเพื่อแก้ไขข้อบกพร่องและปรับปรุงความปลอดภัยเป็นระยะ ผู้ใช้ควรใช้งานเวอร์ชันล่าสุดเพื่อให้ได้รับการรับประกันอย่างเต็มประสิทธิภาพ การร้องขอการสนับสนุนสามารถทำได้ผ่านช่องทางติดต่อด้านล่าง</p>

        <h2><span>⚠️</span> 6. ข้อจำกัดความรับผิด</h2>
        <p>ภายในขอบเขตสูงสุดที่กฎหมายอนุญาต ซอฟต์แวร์ให้บริการ "ตามสภาพที่เป็นอยู่" (AS IS) นอกเหนือจากการรับประกันที่ระบุไว้อย่างชัดแจ้งในเอกสารนี้ บริษัทไม่รับผิดต่อความเสียหายทางอ้อม ความเสียหายสืบเนื่อง การสูญเสียกำไรหรือข้อมูล และความรับผิดรวมของบริษัทจะไม่เกินจำนวนค่าบริการที่ผู้ใช้ได้ชำระจริงให้แก่บริษัทในช่วง 12 เดือนก่อนเกิดเหตุ</p>

        <h2><span>🏢</span> 7. ผู้ให้การรับประกัน</h2>
        <div class="company-box">
            <p style="margin:0 0 6px;"><strong>บริษัท เอ็กซ์แมน เอ็นเตอร์ไพรส์ จำกัด</strong><br>XMAN ENTERPRISE CO., LTD.</p>
            <p style="margin:0 0 6px;">เลขทะเบียนนิติบุคคล: <strong>0675563000120</strong></p>
            <p style="margin:0;">
                เว็บไซต์บริษัท: <a href="https://xman4289.com" target="_blank" rel="noopener noreferrer">xman4289.com</a><br>
                ตรวจสอบข้อมูลนิติบุคคล: <a href="https://www.dataforthai.com/company/0675563000120/" target="_blank" rel="noopener noreferrer">ทะเบียนนิติบุคคล (DataForThai)</a>
            </p>
        </div>

        <h2><span>📬</span> 8. ติดต่อเรื่องการรับประกัน</h2>
        <ul>
            <li>อีเมล: <strong>xjanovax@gmail.com</strong></li>
            <li>เว็บไซต์บริการ: <strong>main.thaiprompt.online</strong></li>
            <li>เว็บไซต์บริษัท: <strong>xman4289.com</strong></li>
        </ul>

        <p style="margin-top:18px; font-size:13px; color:var(--ink2);">บริษัทขอสงวนสิทธิ์ในการปรับปรุงเงื่อนไขการรับประกันนี้ตามความเหมาะสม โดยจะประกาศผ่านเว็บไซต์</p>
    </div>

    <div style="text-align:center; margin-top:28px;">
        <a href="{{ url('/') }}" class="tp-btn tp-btn-sm" style="text-decoration:none;"><i class="fas fa-arrow-left"></i> <span>กลับหน้าแรก</span></a>
    </div>
</div>
@endsection
