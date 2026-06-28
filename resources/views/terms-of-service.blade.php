@extends('layouts.frontend-v4')

@section('title', "ข้อกำหนดการใช้งาน · ไทยพร๊อมท์")
@section('meta_description', "ข้อกำหนดการใช้งาน — ไทยพร๊อมท์ ThaiPrompt")

@push('styles')
<style>
    .legal-doc { color: var(--ink); line-height: 1.85; font-size: 15px; }
    .legal-doc h2 { font-size: 1.22rem; font-weight: 700; color: var(--ink); margin: 30px 0 12px; display:flex; align-items:center; gap:8px; }
    .legal-doc h3 { font-size: 1.04rem; font-weight: 700; color: var(--deep1); margin: 18px 0 8px; }
    .legal-doc p { margin: 0 0 12px; color: var(--ink2); }
    .legal-doc ul, .legal-doc ol { margin: 0 0 14px; padding-left: 22px; color: var(--ink2); }
    .legal-doc li { margin: 6px 0; }
    .legal-doc a { color: var(--deep1); text-decoration: none; }
    .legal-doc a:hover { text-decoration: underline; }
    .legal-doc strong, .legal-doc b { color: var(--ink); }
    .legal-doc .toc { background: var(--surf); box-shadow: var(--inset-sm); border-radius: 14px; padding: 16px 20px; margin: 0 0 26px; }
    .legal-doc .toc h2 { margin: 0 0 10px; font-size: 1.05rem; }
    .legal-doc .toc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 4px 18px; }
    .legal-doc .toc a { display: block; padding: 5px 0; font-size: 13.5px; }
    .legal-doc .num { display:inline-grid; place-items:center; width:22px; height:22px; border-radius:7px; background:linear-gradient(135deg,var(--accent1),var(--accent2)); color:#fff; font-size:11px; font-weight:700; margin-right:6px; }
    .legal-doc table { width: 100%; border-collapse: collapse; margin: 12px 0; }
    .legal-doc th, .legal-doc td { border: 1px solid color-mix(in srgb, var(--ink2) 25%, transparent); padding: 8px 11px; text-align: left; font-size: 13.5px; }
    .legal-doc th { background: var(--surf); font-weight: 700; color: var(--ink); }
    .legal-doc .container { max-width: none; padding: 0; margin: 0; }
    .legal-doc .hero, .legal-doc .hero-icon, .legal-doc .subtitle, .legal-doc .updated { display: none; }
</style>
@endpush

@section('content')
<div style="max-width:920px; margin:0 auto; padding:40px clamp(16px,3vw,40px) 60px;">
    {{-- หัวเรื่อง V4 --}}
    <div style="text-align:center; margin-bottom:28px;">
        <div class="tp-tile" style="width:64px; height:64px; border-radius:20px; font-size:30px; margin:0 auto 16px;">📜</div>
        <h1 style="font-size:clamp(26px,4vw,34px); font-weight:800; color:var(--ink); margin:0;">ข้อกำหนดการใช้งาน</h1>
        <p class="tp-muted" style="margin:8px 0 0; font-size:13px;">อัปเดตล่าสุด: 23 กุมภาพันธ์ 2026</p>
    </div>

    {{-- เนื้อหากฎหมาย (คงเดิม ห่อด้วยกรอบ V4) --}}
    <div class="tp-card legal-doc" style="padding:clamp(20px,4vw,40px);">
        @verbatim
<div class="container">

    <!-- สารบัญ -->
    <div class="toc">
        <h2>📋 สารบัญ</h2>
        <div class="toc-grid">
            <a href="#accept"><span class="num">01</span> การยอมรับข้อกำหนด</a>
            <a href="#services"><span class="num">02</span> บริการของเรา</a>
            <a href="#account"><span class="num">03</span> บัญชีผู้ใช้</a>
            <a href="#fortune"><span class="num">04</span> บริการดูดวง</a>
            <a href="#affiliate"><span class="num">05</span> ระบบ Affiliate</a>
            <a href="#messenger"><span class="num">06</span> การใช้งาน Messenger</a>
            <a href="#payment"><span class="num">07</span> การชำระเงิน</a>
            <a href="#prohibited"><span class="num">08</span> ข้อห้าม</a>
            <a href="#ip"><span class="num">09</span> ทรัพย์สินทางปัญญา</a>
            <a href="#limitation"><span class="num">10</span> การจำกัดความรับผิด</a>
            <a href="#termination"><span class="num">11</span> การระงับบริการ</a>
            <a href="#governing"><span class="num">12</span> กฎหมายที่ใช้บังคับ</a>
            <a href="#changes"><span class="num">13</span> การเปลี่ยนแปลงข้อกำหนด</a>
            <a href="#contact"><span class="num">14</span> ติดต่อเรา</a>
        </div>
    </div>

    <!-- 1 -->
    <div class="section" id="accept">
        <h2><span class="icon">✅</span> 1. การยอมรับข้อกำหนด</h2>
        <p>การเข้าใช้งานเว็บไซต์ main.thaiprompt.online, บริการ Facebook Messenger Bot, หรือบริการอื่นใดของ Thaiprompt / TP-Affiliate ("แพลตฟอร์ม") ถือว่าคุณได้อ่าน เข้าใจ และยอมรับข้อกำหนดและเงื่อนไขทั้งหมดในเอกสารนี้ หากคุณไม่ยอมรับข้อกำหนดเหล่านี้ กรุณาหยุดใช้บริการ</p>
        <div class="highlight-box">
            <p>ข้อกำหนดนี้ใช้ร่วมกับ <a href="/privacy-policy.html" style="color: #f9a8d4; text-decoration: underline;">นโยบายความเป็นส่วนตัว</a> ซึ่งอธิบายวิธีการจัดการข้อมูลส่วนบุคคลของคุณ</p>
        </div>
    </div>

    <!-- 2 -->
    <div class="section" id="services">
        <h2><span class="icon">🔮</span> 2. บริการของเรา</h2>
        <p>Thaiprompt ให้บริการดังต่อไปนี้:</p>
        <ul>
            <li><strong>บริการดูดวงออนไลน์</strong> — ดูดวงผ่าน Facebook Messenger ด้วยระบบ AI รองรับดวงพื้นฐาน (ฟรี) และดวงเชิงลึก (พรีเมียม)</li>
            <li><strong>ระบบ Affiliate Marketing</strong> — สร้างรายได้จากการแนะนำผู้ใช้ใหม่เข้าสู่ระบบ ด้วยโครงสร้าง Multi-Level Marketing (MLM)</li>
            <li><strong>ร้านค้าออนไลน์</strong> — จำหน่ายสินค้าที่เกี่ยวข้อง</li>
            <li><strong>เนื้อหาความรู้</strong> — บทความ วิดีโอ และเนื้อหาที่เกี่ยวข้องกับโหราศาสตร์และดวงชะตา</li>
        </ul>
    </div>

    <!-- 3 -->
    <div class="section" id="account">
        <h2><span class="icon">👤</span> 3. บัญชีผู้ใช้</h2>
        <h3>3.1 คุณสมบัติ</h3>
        <ul>
            <li>ผู้ใช้ต้องมีอายุไม่น้อยกว่า 13 ปี</li>
            <li>ผู้ใช้อายุ 13-20 ปี ต้องได้รับความยินยอมจากผู้ปกครอง</li>
            <li>ข้อมูลที่ให้ต้องถูกต้องและเป็นปัจจุบัน</li>
        </ul>
        <h3>3.2 ความรับผิดชอบ</h3>
        <ul>
            <li>คุณต้องรักษาความปลอดภัยของบัญชีและรหัสผ่าน</li>
            <li>คุณต้องแจ้งเราทันทีหากพบการใช้งานที่ไม่ได้รับอนุญาต</li>
            <li>คุณรับผิดชอบต่อกิจกรรมทั้งหมดที่เกิดขึ้นภายใต้บัญชีของคุณ</li>
        </ul>
    </div>

    <!-- 4 -->
    <div class="section" id="fortune">
        <h2><span class="icon">🌟</span> 4. บริการดูดวง</h2>
        <h3>4.1 ลักษณะของบริการ</h3>
        <p>บริการดูดวงของเราใช้ระบบปัญญาประดิษฐ์ (AI) ในการประมวลผลและสร้างคำทำนาย โดยอ้างอิงจากข้อมูลวันเกิด เวลาเกิด และคำถามที่ผู้ใช้ให้ข้อมูล</p>

        <div class="warning-box">
            <p>⚠️ คำทำนายทั้งหมดเป็นเพียงความบันเทิงและข้อมูลอ้างอิงเท่านั้น ไม่ใช่คำแนะนำทางการแพทย์ ทางกฎหมาย ทางการเงิน หรือทางจิตวิทยา กรุณาใช้วิจารณญาณของคุณเอง</p>
        </div>

        <h3>4.2 ระดับบริการ</h3>
        <ul>
            <li><strong>ดวงพื้นฐาน (ฟรี):</strong> จำกัดจำนวนครั้งต่อวันตามที่กำหนด คำทำนายแบบย่อ</li>
            <li><strong>ดวงเชิงลึก (พรีเมียม):</strong> จำกัดจำนวนครั้งฟรีต่อวัน หลังจากนั้นต้องชำระเงิน คำทำนายละเอียดพร้อมคำแนะนำ</li>
        </ul>

        <h3>4.3 ข้อจำกัด</h3>
        <ul>
            <li>เราไม่รับประกันความถูกต้องของคำทำนาย</li>
            <li>ผลลัพธ์อาจแตกต่างกันขึ้นอยู่กับข้อมูลที่ให้และเงื่อนไขของระบบ AI</li>
            <li>เราสงวนสิทธิ์ในการปรับเปลี่ยนจำนวนครั้งฟรีและราคาบริการ</li>
        </ul>
    </div>

    <!-- 5 -->
    <div class="section" id="affiliate">
        <h2><span class="icon">💰</span> 5. ระบบ Affiliate Marketing</h2>
        <h3>5.1 เงื่อนไขการเข้าร่วม</h3>
        <ul>
            <li>ต้องลงทะเบียนและยืนยันตัวตน</li>
            <li>ต้องปฏิบัติตามข้อกำหนดการตลาดที่กำหนด</li>
            <li>ห้ามใช้วิธีการที่ไม่เป็นธรรมในการหาสมาชิก (เช่น สแปม, ข้อมูลเท็จ)</li>
        </ul>
        <h3>5.2 ค่าคอมมิชชัน</h3>
        <ul>
            <li>อัตราค่าคอมมิชชันเป็นไปตามโครงสร้างที่ประกาศบนแพลตฟอร์ม</li>
            <li>การจ่ายค่าคอมมิชชันจะดำเนินการตามรอบที่กำหนด</li>
            <li>เราสงวนสิทธิ์ในการระงับหรือยกเลิกค่าคอมมิชชันหากพบการกระทำที่ไม่เป็นธรรม</li>
        </ul>
        <h3>5.3 การโปรโมท</h3>
        <ul>
            <li>ห้ามใช้ข้อความที่ทำให้เข้าใจผิดเกี่ยวกับรายได้ที่จะได้รับ</li>
            <li>ห้ามสร้างบัญชีปลอมหรือใช้วิธีฉ้อฉลเพื่อเพิ่มยอดค่าคอมมิชชัน</li>
            <li>ต้องปฏิบัติตามนโยบายการโฆษณาของ Facebook / Meta</li>
        </ul>
    </div>

    <!-- 6 -->
    <div class="section" id="messenger">
        <h2><span class="icon">💬</span> 6. การใช้งานผ่าน Facebook Messenger</h2>
        <p>เมื่อคุณส่งข้อความถึงเพจของเราผ่าน Facebook Messenger หรือคอมเม้นต์บนโพสต์ของเพจ:</p>
        <ul>
            <li>คุณยินยอมให้เราตอบกลับผ่าน Messenger รวมถึงข้อความอัตโนมัติ</li>
            <li>คุณยินยอมให้เราอ่านและประมวลผลข้อความที่คุณส่ง เพื่อให้บริการดูดวง</li>
            <li>คุณอาจได้รับข้อความชวนใช้บริการเมื่อคอมเม้นต์บนโพสต์ของเพจ</li>
            <li>คุณสามารถหยุดรับข้อความจากเราได้ตลอดเวลาโดยพิมพ์ "หยุด" หรือบล็อกเพจ</li>
        </ul>
        <div class="highlight-box">
            <p>การส่งข้อความถึงเพจถือเป็นการยินยอมให้เราตอบกลับและให้บริการตามนโยบายการส่งข้อความของ Facebook (Messaging Policy)</p>
        </div>
    </div>

    <!-- 7 -->
    <div class="section" id="payment">
        <h2><span class="icon">💳</span> 7. การชำระเงินและการคืนเงิน</h2>
        <h3>7.1 การชำระเงิน</h3>
        <ul>
            <li>ราคาบริการเป็นไปตามที่แสดงบนแพลตฟอร์ม ณ เวลาที่ทำรายการ</li>
            <li>การชำระเงินผ่านช่องทางที่เรากำหนด (โอนเงิน, PromptPay ฯลฯ)</li>
            <li>ราคาอาจเปลี่ยนแปลงได้โดยไม่ต้องแจ้งล่วงหน้า</li>
        </ul>
        <h3>7.2 การคืนเงิน</h3>
        <ul>
            <li>บริการดูดวงที่ให้บริการแล้วไม่สามารถคืนเงินได้ เนื่องจากเป็นบริการดิจิทัลที่ใช้แล้ว</li>
            <li>กรณีระบบขัดข้องจนไม่สามารถให้บริการได้ เราจะคืนเงินหรือให้เครดิตทดแทน</li>
            <li>คำร้องขอคืนเงินต้องยื่นภายใน 7 วันนับจากวันที่ทำรายการ</li>
        </ul>
    </div>

    <!-- 8 -->
    <div class="section" id="prohibited">
        <h2><span class="icon">🚫</span> 8. ข้อห้ามในการใช้งาน</h2>
        <p>ผู้ใช้ห้ามกระทำการดังต่อไปนี้:</p>
        <ul>
            <li>ใช้ระบบเพื่อวัตถุประสงค์ที่ผิดกฎหมาย</li>
            <li>ส่งเนื้อหาที่ไม่เหมาะสม หยาบคาย ข่มขู่ หรือเป็นการหมิ่นประมาท</li>
            <li>พยายามเจาะระบบ แฮ็ก หรือรบกวนการทำงานของแพลตฟอร์ม</li>
            <li>ใช้บอทหรือระบบอัตโนมัติเพื่อเข้าถึงบริการโดยไม่ได้รับอนุญาต</li>
            <li>สร้างบัญชีหลายบัญชีเพื่อหลีกเลี่ยงข้อจำกัด</li>
            <li>แอบอ้างเป็นบุคคลอื่นหรือให้ข้อมูลเท็จ</li>
            <li>ละเมิดลิขสิทธิ์หรือทรัพย์สินทางปัญญาของผู้อื่น</li>
            <li>ใช้วิธีฉ้อฉลเพื่อหาผลประโยชน์จากระบบ Affiliate</li>
        </ul>
    </div>

    <!-- 9 -->
    <div class="section" id="ip">
        <h2><span class="icon">©️</span> 9. ทรัพย์สินทางปัญญา</h2>
        <p>เนื้อหา การออกแบบ โลโก้ ซอฟต์แวร์ และส่วนประกอบทั้งหมดของแพลตฟอร์ม Thaiprompt เป็นทรัพย์สินทางปัญญาของเราหรือผู้ให้อนุญาต ห้ามคัดลอก ดัดแปลง แจกจ่าย หรือนำไปใช้ในเชิงพาณิชย์โดยไม่ได้รับอนุญาตเป็นลายลักษณ์อักษร</p>
        <p>คำทำนายที่สร้างโดยระบบ AI ของเราถือเป็นผลงานที่สร้างขึ้นสำหรับการใช้งานส่วนบุคคลของผู้ใช้แต่ละราย</p>
    </div>

    <!-- 10 -->
    <div class="section" id="limitation">
        <h2><span class="icon">⚠️</span> 10. การจำกัดความรับผิด</h2>
        <ul>
            <li>บริการดูดวงเป็นเพียงความบันเทิง เราไม่รับผิดชอบต่อการตัดสินใจที่อ้างอิงจากคำทำนาย</li>
            <li>เราไม่รับประกันว่าบริการจะทำงานได้อย่างต่อเนื่องหรือปราศจากข้อผิดพลาด</li>
            <li>เราไม่รับผิดชอบต่อความเสียหายทางอ้อม ความเสียหายพิเศษ หรือผลกำไรที่สูญเสีย</li>
            <li>ความรับผิดสูงสุดของเราจำกัดอยู่ที่จำนวนเงินที่คุณชำระให้เราในช่วง 12 เดือนล่าสุด</li>
        </ul>
        <div class="warning-box">
            <p>⚠️ ผลจากการดูดวงไม่ใช่คำแนะนำทางการแพทย์ ทางกฎหมาย หรือทางการเงิน หากคุณประสบปัญหาในด้านเหล่านี้ กรุณาปรึกษาผู้เชี่ยวชาญ</p>
        </div>
    </div>

    <!-- 11 -->
    <div class="section" id="termination">
        <h2><span class="icon">🔨</span> 11. การระงับและยกเลิกบริการ</h2>
        <p>เราสงวนสิทธิ์ในการระงับหรือยกเลิกบัญชีผู้ใช้โดยไม่ต้องแจ้งล่วงหน้า หากพบว่า:</p>
        <ul>
            <li>ละเมิดข้อกำหนดการใช้งาน</li>
            <li>กระทำการฉ้อฉลหรือไม่เป็นธรรม</li>
            <li>ใช้บริการในทางที่ผิดกฎหมาย</li>
            <li>สร้างความเสียหายต่อผู้ใช้รายอื่นหรือแพลตฟอร์ม</li>
        </ul>
        <p>คุณสามารถยกเลิกการใช้บริการได้ตลอดเวลาโดยลบบัญชีผู้ใช้หรือหยุดใช้งานแพลตฟอร์ม</p>
    </div>

    <!-- 12 -->
    <div class="section" id="governing">
        <h2><span class="icon">⚖️</span> 12. กฎหมายที่ใช้บังคับ</h2>
        <p>ข้อกำหนดการใช้งานนี้อยู่ภายใต้กฎหมายแห่งราชอาณาจักรไทย ข้อพิพาทที่เกิดจากหรือเกี่ยวข้องกับข้อกำหนดนี้ จะอยู่ภายใต้เขตอำนาจศาลไทย</p>
        <p>หากข้อกำหนดข้อใดไม่สามารถบังคับใช้ได้ตามกฎหมาย ข้อกำหนดข้ออื่นยังคงมีผลบังคับใช้ต่อไป</p>
    </div>

    <!-- 13 -->
    <div class="section" id="changes">
        <h2><span class="icon">🔄</span> 13. การเปลี่ยนแปลงข้อกำหนด</h2>
        <p>เราสงวนสิทธิ์ในการแก้ไขข้อกำหนดการใช้งานนี้เมื่อใดก็ได้ การเปลี่ยนแปลงจะมีผลทันทีเมื่อเผยแพร่บนเว็บไซต์ การใช้บริการต่อเนื่องหลังจากมีการเปลี่ยนแปลง ถือว่าคุณยอมรับข้อกำหนดที่แก้ไขแล้ว</p>
    </div>

    <!-- 14 -->
    <div class="section" id="contact">
        <h2><span class="icon">📬</span> 14. ติดต่อเรา</h2>
        <p>หากมีคำถามเกี่ยวกับข้อกำหนดการใช้งาน:</p>
        <ul>
            <li>อีเมล: <strong>xjanovax@gmail.com</strong></li>
            <li>เว็บไซต์: <strong>main.thaiprompt.online</strong></li>
        </ul>
    </div>

    <div class="footer">
        <p>&copy; 2026 Thaiprompt / TP-Affiliate. สงวนลิขสิทธิ์.</p>
        <p style="margin-top: 8px;">
            <a href="/privacy-policy.html">นโยบายความเป็นส่วนตัว</a> &middot;
            <a href="https://main.thaiprompt.online">กลับสู่หน้าหลัก</a>
        </p>
    </div>
</div>
        @endverbatim
    </div>

    <div style="text-align:center; margin-top:28px;">
        <a href="{{ url('/') }}" class="tp-btn tp-btn-sm" style="text-decoration:none;"><i class="fas fa-arrow-left"></i> <span>กลับหน้าแรก</span></a>
    </div>
</div>
@endsection
