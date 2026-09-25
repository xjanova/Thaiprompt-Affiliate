@extends('layouts.frontend-v4')

@section('title', "นโยบายความเป็นส่วนตัว · ไทยพร๊อมท์")
@section('meta_description', "นโยบายความเป็นส่วนตัว — ไทยพร๊อมท์ ThaiPrompt")

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
        <div class="tp-tile" style="width:64px; height:64px; border-radius:20px; font-size:30px; margin:0 auto 16px;">🔒</div>
        <h1 style="font-size:clamp(26px,4vw,34px); font-weight:800; color:var(--ink); margin:0;">นโยบายความเป็นส่วนตัว</h1>
        <p class="tp-muted" style="margin:8px 0 0; font-size:13px;">อัปเดตล่าสุด: 25 กันยายน 2026</p>
    </div>

    {{-- เนื้อหากฎหมาย (คงเดิม ห่อด้วยกรอบ V4) --}}
    <div class="tp-card legal-doc" style="padding:clamp(20px,4vw,40px);">
        @verbatim
<div class="container">

    <!-- สารบัญ -->
    <div class="toc">
        <h2>📋 สารบัญ</h2>
        <div class="toc-grid">
            <a href="#intro"><span class="num">01</span> บทนำ</a>
            <a href="#collect"><span class="num">02</span> ข้อมูลที่เราเก็บรวบรวม</a>
            <a href="#facebook"><span class="num">03</span> การใช้ข้อมูลจาก Facebook</a>
            <a href="#purpose"><span class="num">04</span> วัตถุประสงค์การใช้ข้อมูล</a>
            <a href="#share"><span class="num">05</span> การแบ่งปันข้อมูล</a>
            <a href="#security"><span class="num">06</span> ความปลอดภัยของข้อมูล</a>
            <a href="#retention"><span class="num">07</span> ระยะเวลาการเก็บข้อมูล</a>
            <a href="#rights"><span class="num">08</span> สิทธิ์ของผู้ใช้</a>
            <a href="#cookies"><span class="num">09</span> นโยบาย Cookies</a>
            <a href="#children"><span class="num">10</span> ข้อมูลเด็กและเยาวชน</a>
            <a href="#delete"><span class="num">11</span> การลบข้อมูล</a>
            <a href="#changes"><span class="num">12</span> การเปลี่ยนแปลงนโยบาย</a>
            <a href="#contact"><span class="num">13</span> ติดต่อเรา</a>
        </div>
    </div>

    <!-- 1. บทนำ -->
    <div class="section" id="intro">
        <h2><span class="icon">📌</span> 1. บทนำ</h2>
        <p>Thaiprompt / TP-Affiliate ("เรา", "บริษัท", "แพลตฟอร์ม") ให้ความสำคัญสูงสุดกับการคุ้มครองข้อมูลส่วนบุคคลของผู้ใช้บริการทุกท่าน ("คุณ", "ผู้ใช้") นโยบายความเป็นส่วนตัวฉบับนี้อธิบายรายละเอียดเกี่ยวกับวิธีการเก็บรวบรวม ใช้ เปิดเผย จัดเก็บ และปกป้องข้อมูลส่วนบุคคลของคุณ ตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA) และกฎหมายคุ้มครองข้อมูลที่เกี่ยวข้อง</p>
        <div class="highlight-box">
            <p>นโยบายนี้ครอบคลุมบริการทั้งหมดของเรา ได้แก่: เว็บไซต์ main.thaiprompt.online, แอปพลิเคชัน Thaiprompt บน Android/iOS (ร้านค้า ตลาดสด ไรเดอร์ กระเป๋าเงิน), ระบบดูดวงผ่าน Facebook Messenger/LINE, ระบบ Affiliate Marketing, และบริการที่เกี่ยวข้องทั้งหมด</p>
        </div>
    </div>

    <!-- 2. ข้อมูลที่เราเก็บรวบรวม -->
    <div class="section" id="collect">
        <h2><span class="icon">📊</span> 2. ข้อมูลที่เราเก็บรวบรวม</h2>

        <div class="data-card">
            <h4>2.1 ข้อมูลที่คุณให้โดยตรง</h4>
            <ul>
                <li>ชื่อ-นามสกุล และชื่อเล่น</li>
                <li>ที่อยู่อีเมล</li>
                <li>หมายเลขโทรศัพท์</li>
                <li>ที่อยู่จัดส่ง</li>
                <li>ข้อมูลบัญชีธนาคาร (สำหรับการรับค่าคอมมิชชัน Affiliate)</li>
                <li>วันเดือนปีเกิด เวลาเกิด สถานที่เกิด (สำหรับบริการดูดวง)</li>
                <li>คำถามและข้อมูลที่ส่งผ่านระบบดูดวง</li>
            </ul>
        </div>

        <div class="data-card">
            <h4>2.2 ข้อมูลที่เก็บรวบรวมโดยอัตโนมัติ</h4>
            <ul>
                <li>ที่อยู่ IP (Internet Protocol)</li>
                <li>ประเภทเบราว์เซอร์และระบบปฏิบัติการ</li>
                <li>ข้อมูลอุปกรณ์ (รุ่น, ขนาดหน้าจอ)</li>
                <li>พฤติกรรมการใช้งานเว็บไซต์ (หน้าที่เข้าชม, ระยะเวลา, การคลิก)</li>
                <li>ข้อมูล Cookies และ Local Storage</li>
                <li>ข้อมูลตำแหน่งที่ตั้งโดยประมาณ (จาก IP Address)</li>
                <li>ข้อมูลการทำธุรกรรม (ประวัติการสั่งซื้อ, การชำระเงิน)</li>
            </ul>
        </div>

        <div class="data-card">
            <h4>2.3 ข้อมูลจากบุคคลที่สาม</h4>
            <ul>
                <li>ข้อมูลโปรไฟล์จาก Facebook (ชื่อ, รูปภาพ, เพศ)</li>
                <li>ข้อมูลโปรไฟล์จาก LINE (ชื่อที่แสดง, รูปโปรไฟล์, LINE User ID) เมื่อคุณเข้าสู่ระบบด้วย LINE</li>
                <li>ข้อมูลจากระบบชำระเงิน</li>
                <li>ข้อมูลจากผู้แนะนำ (Referrer) ในระบบ Affiliate</li>
            </ul>
        </div>

        <div class="data-card" id="app-data">
            <h4>2.4 ข้อมูลที่แอปพลิเคชันมือถือเก็บ (Android / iOS)</h4>
            <table>
                <tr><th>ข้อมูล / สิทธิ์</th><th>ใช้เมื่อไร และใช้ทำอะไร</th></tr>
                <tr>
                    <td><strong>ตำแหน่งแม่นยำ (GPS) ขณะใช้งานแอป</strong></td>
                    <td>ค้นหาร้านค้า/ตลาดสดใกล้คุณ ปักหมุดที่อยู่จัดส่ง และคำนวณค่าส่ง — ขอสิทธิ์ก่อนใช้ทุกครั้ง ปฏิเสธได้โดยยังใช้งานส่วนอื่นได้</td>
                </tr>
                <tr>
                    <td><strong>ตำแหน่งเบื้องหลัง (Background Location) — เฉพาะไรเดอร์</strong></td>
                    <td>เมื่อไรเดอร์เปิดรับงานหรือกำลังส่งของ แอปส่งตำแหน่งทุก 10–30 วินาทีแม้ปิดหน้าจอ (มีแจ้งเตือนค้างบนเครื่องตลอดเวลาที่ติดตาม) เพื่อจับคู่งานใกล้เคียงและให้ลูกค้าเห็นตำแหน่งไรเดอร์เฉพาะออเดอร์ของตนระหว่างจัดส่ง — หยุดติดตามทันทีเมื่อปิดรับงานหรืองานเสร็จ/ยกเลิก</td>
                </tr>
                <tr>
                    <td><strong>ตำแหน่งของลูกค้าระหว่างรอของ</strong></td>
                    <td>ส่งให้ไรเดอร์เฉพาะเมื่อคุณเลือกเปิดแชร์ตำแหน่งในออเดอร์นั้น และหยุดอัตโนมัติเมื่อจัดส่งเสร็จ</td>
                </tr>
                <tr>
                    <td><strong>แชร์ตำแหน่งให้ทีมงาน</strong></td>
                    <td>เฉพาะเมื่อคุณเปิดเองในหน้าตั้งค่า (เช่นขอความช่วยเหลือ) ส่งตำแหน่งและรุ่นอุปกรณ์ทุก 30 วินาทีจนกว่าจะปิด</td>
                </tr>
                <tr>
                    <td><strong>กล้อง / รูปภาพ</strong></td>
                    <td>ถ่ายบัตรประชาชนและใบหน้าเพื่อยืนยันตัวตน (KYC), เอกสารสมัครไรเดอร์ (ใบขับขี่ ทะเบียนรถ), รูปหลักฐานรับ-ส่งของของไรเดอร์ และรูปสินค้า/สลิปที่คุณเลือกอัปโหลด — เอกสารยืนยันตัวตนเก็บในพื้นที่ส่วนตัว ดูได้เฉพาะเจ้าหน้าที่ตรวจสอบ</td>
                </tr>
                <tr>
                    <td><strong>Push token และการแจ้งเตือน</strong></td>
                    <td>ส่งแจ้งเตือนสถานะออเดอร์ งานไรเดอร์ ข้อความแชท และธุรกรรมกระเป๋าเงิน — token ถูกถอดออกจากบัญชีเมื่อคุณออกจากระบบ</td>
                </tr>
                <tr>
                    <td><strong>ข้อมูลอุปกรณ์</strong></td>
                    <td>รหัสอุปกรณ์ที่แอปสร้างขึ้น รุ่น ยี่ห้อ ระบบปฏิบัติการ เวอร์ชันแอป ภาษา/เขตเวลา — ใช้ส่งแจ้งเตือนให้ถูกเครื่อง ป้องกันการทุจริต และนับสถิติการใช้งาน (ไม่เก็บ IMEI หรือหมายเลขซิม)</td>
                </tr>
                <tr>
                    <td><strong>ข้อมูลบัญชีธนาคาร</strong></td>
                    <td>ชื่อบัญชี เลขบัญชี ธนาคาร/พร้อมเพย์ ที่คุณกรอกเพื่อถอนเงิน รับรายได้จากการขาย หรือค่าส่งของไรเดอร์</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- 3. การใช้ข้อมูลจาก Facebook -->
    <div class="section" id="facebook">
        <h2><span class="icon">📱</span> 3. การใช้ข้อมูลจาก Facebook / Meta</h2>
        <p>แอปพลิเคชันของเราเชื่อมต่อกับ Facebook Platform เพื่อให้บริการดูดวงผ่าน Facebook Messenger โดยเราเข้าถึงข้อมูลดังนี้:</p>

        <div class="data-card">
            <h4>3.1 สิทธิ์การเข้าถึงที่ใช้</h4>
            <ul>
                <li><strong>pages_messaging</strong> — เพื่อรับและส่งข้อความผ่าน Messenger ของเพจ สำหรับบริการดูดวงอัตโนมัติ</li>
                <li><strong>pages_read_engagement</strong> — เพื่ออ่านคอมเม้นต์บนโพสต์ของเพจ และตอบกลับอัตโนมัติ</li>
                <li><strong>pages_show_list</strong> — เพื่อแสดงรายการเพจที่เชื่อมต่อในระบบจัดการ</li>
                <li><strong>pages_manage_metadata</strong> — เพื่อจัดการการตั้งค่า webhook และเมนูของ Messenger</li>
            </ul>
        </div>

        <div class="data-card">
            <h4>3.2 ข้อมูล Facebook ที่เราประมวลผล</h4>
            <ul>
                <li>ชื่อโปรไฟล์ Facebook ของผู้ใช้ที่ส่งข้อความหาเพจ</li>
                <li>รูปโปรไฟล์ (เพื่อแสดงในระบบจัดการ)</li>
                <li>ข้อความที่ส่งเข้ามาผ่าน Messenger (คำถามดูดวง, วันเดือนปีเกิด)</li>
                <li>ข้อความคอมเม้นต์บนโพสต์ของเพจ</li>
                <li>Facebook User ID (PSID) สำหรับระบุตัวตนในการให้บริการ</li>
            </ul>
        </div>

        <div class="highlight-box">
            <p>⚠️ เราไม่เข้าถึงรายชื่อเพื่อน, กลุ่ม, ไทม์ไลน์ส่วนตัว หรือข้อมูลอื่นใดบน Facebook นอกเหนือจากที่ระบุไว้ข้างต้น ข้อมูลทั้งหมดจะถูกใช้เพื่อให้บริการดูดวงและการตลาดของเพจเท่านั้น</p>
        </div>
    </div>

    <!-- 4. วัตถุประสงค์ -->
    <div class="section" id="purpose">
        <h2><span class="icon">🎯</span> 4. วัตถุประสงค์การใช้ข้อมูล</h2>
        <h3>4.1 การให้บริการหลัก</h3>
        <ul>
            <li>ให้บริการดูดวงผ่าน Facebook Messenger (ดวงพื้นฐานและดวงเชิงลึก)</li>
            <li>ประมวลผลข้อมูลวันเกิดเพื่อคำนวณดวงชะตา</li>
            <li>ตอบกลับคอมเม้นต์บนโพสต์ของเพจอัตโนมัติ</li>
            <li>ส่งข้อความชวนใช้บริการผ่าน Messenger</li>
            <li>จัดการระบบ Affiliate Marketing และคำนวณค่าคอมมิชชัน</li>
        </ul>
        <h3>4.2 การปรับปรุงบริการ</h3>
        <ul>
            <li>วิเคราะห์พฤติกรรมการใช้งานเพื่อปรับปรุงประสบการณ์</li>
            <li>พัฒนาความแม่นยำของระบบ AI ดูดวง</li>
            <li>ปรับแต่งเนื้อหาให้ตรงกับความสนใจของผู้ใช้</li>
        </ul>
        <h3>4.3 การสื่อสาร</h3>
        <ul>
            <li>แจ้งข่าวสารและโปรโมชั่น (เมื่อได้รับความยินยอม)</li>
            <li>ส่งการแจ้งเตือนเกี่ยวกับบัญชีและธุรกรรม</li>
            <li>ให้บริการลูกค้าและตอบข้อสงสัย</li>
        </ul>
        <h3>4.4 ความปลอดภัยและกฎหมาย</h3>
        <ul>
            <li>ป้องกันการฉ้อโกงและการใช้งานในทางที่ผิด</li>
            <li>ปฏิบัติตามข้อกำหนดทางกฎหมายที่เกี่ยวข้อง</li>
            <li>รักษาความปลอดภัยของระบบและข้อมูล</li>
        </ul>
    </div>

    <!-- 5. การแบ่งปัน -->
    <div class="section" id="share">
        <h2><span class="icon">🤝</span> 5. การแบ่งปันข้อมูล</h2>
        <p><strong>เราไม่ขาย ให้เช่า หรือแลกเปลี่ยนข้อมูลส่วนบุคคลของคุณเพื่อวัตถุประสงค์ทางการค้า</strong> เราอาจแบ่งปันข้อมูลกับบุคคลที่สามในกรณีต่อไปนี้เท่านั้น:</p>
        <ul>
            <li><strong>ผู้ให้บริการ AI (Groq/OpenAI)</strong> — เพื่อประมวลผลคำทำนายดวงชะตา โดยส่งเฉพาะข้อมูลที่จำเป็น (วันเกิด, คำถาม)</li>
            <li><strong>Facebook / Meta Platforms</strong> — เพื่อการสื่อสารผ่าน Messenger API ตามนโยบายของ Meta</li>
            <li><strong>ผู้ให้บริการชำระเงิน</strong> — เพื่อดำเนินการชำระเงินและโอนค่าคอมมิชชัน</li>
            <li><strong>ร้านค้าและไรเดอร์ที่เกี่ยวข้องกับออเดอร์ของคุณ</strong> — ชื่อ เบอร์โทร ที่อยู่/ตำแหน่งจัดส่ง เฉพาะออเดอร์นั้นและเท่าที่จำเป็นต่อการจัดส่ง (ไรเดอร์เห็นเบอร์และที่อยู่เต็มหลังรับงานแล้วเท่านั้น)</li>
            <li><strong>ผู้ให้บริการแจ้งเตือน (Expo, Google Firebase Cloud Messaging, Apple Push Notification service)</strong> — เพื่อส่งแจ้งเตือนเข้าอุปกรณ์ของคุณ</li>
            <li><strong>Google Maps Platform</strong> — เพื่อแปลงพิกัดเป็นที่อยู่และแสดงแผนที่</li>
            <li><strong>ผู้ให้บริการโฮสติ้งและเซิร์ฟเวอร์</strong> — เพื่อจัดเก็บข้อมูลอย่างปลอดภัย</li>
            <li><strong>หน่วยงานราชการ</strong> — เมื่อกฎหมายกำหนดหรือได้รับคำสั่งจากศาล</li>
        </ul>
    </div>

    <!-- 6. ความปลอดภัย -->
    <div class="section" id="security">
        <h2><span class="icon">🔒</span> 6. ความปลอดภัยของข้อมูล</h2>
        <p>เราใช้มาตรการรักษาความปลอดภัยตามมาตรฐานอุตสาหกรรมเพื่อปกป้องข้อมูลส่วนบุคคลของคุณ:</p>
        <ul>
            <li>การเข้ารหัสข้อมูล SSL/TLS สำหรับการรับส่งข้อมูลทั้งหมด</li>
            <li>การเข้ารหัสรหัสผ่านด้วย bcrypt</li>
            <li>ระบบ Webhook Signature Verification (X-Hub-Signature-256) สำหรับ Facebook</li>
            <li>การจำกัดสิทธิ์การเข้าถึงข้อมูลตามบทบาทหน้าที่ (RBAC)</li>
            <li>การสำรองข้อมูลอย่างสม่ำเสมอ</li>
            <li>ระบบตรวจจับและป้องกันการบุกรุก</li>
            <li>การตรวจสอบและอัปเดตระบบความปลอดภัยอย่างต่อเนื่อง</li>
        </ul>
    </div>

    <!-- 7. ระยะเวลา -->
    <div class="section" id="retention">
        <h2><span class="icon">⏱️</span> 7. ระยะเวลาการเก็บข้อมูล</h2>
        <ul>
            <li><strong>ข้อมูลบัญชีผู้ใช้:</strong> ตลอดระยะเวลาที่บัญชียังใช้งานอยู่ — เมื่อลบบัญชี ข้อมูลที่ระบุตัวตนได้ (ชื่อ อีเมล เบอร์โทร ที่อยู่ บัญชีธนาคาร เอกสาร KYC) จะถูกลบหรือปกปิดทันที</li>
            <li><strong>ประวัติตำแหน่งของไรเดอร์ระหว่างรับงาน:</strong> 30 วัน</li>
            <li><strong>ข้อมูลการดูดวง:</strong> เก็บรักษาไว้ 1 ปี เพื่อการอ้างอิงและปรับปรุงบริการ</li>
            <li><strong>ข้อมูลการสนทนา Messenger:</strong> เก็บรักษาไว้ 6 เดือน</li>
            <li><strong>ข้อมูลธุรกรรมการเงิน:</strong> เก็บรักษาตามที่กฎหมายกำหนด (5 ปี)</li>
            <li><strong>ข้อมูล Cookies:</strong> ตามอายุของ Cookie แต่ละประเภท (สูงสุด 1 ปี)</li>
            <li><strong>บันทึกการเข้าถึงระบบ (Logs):</strong> 90 วัน</li>
        </ul>
        <p>หลังจากพ้นระยะเวลาดังกล่าว ข้อมูลจะถูกลบหรือทำให้ไม่สามารถระบุตัวตนได้ (Anonymize)</p>
    </div>

    <!-- 8. สิทธิ์ -->
    <div class="section" id="rights">
        <h2><span class="icon">⚖️</span> 8. สิทธิ์ของผู้ใช้ (ตาม PDPA)</h2>
        <p>ภายใต้พระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 คุณมีสิทธิ์ดังต่อไปนี้:</p>
        <div class="rights-grid">
            <div class="right-item">
                <div class="right-icon">👁️</div>
                <div class="right-title">สิทธิ์ในการเข้าถึง</div>
                <div class="right-desc">ขอเข้าถึงและรับสำเนาข้อมูลส่วนบุคคลของคุณ</div>
            </div>
            <div class="right-item">
                <div class="right-icon">✏️</div>
                <div class="right-title">สิทธิ์ในการแก้ไข</div>
                <div class="right-desc">ขอแก้ไขข้อมูลที่ไม่ถูกต้องหรือไม่สมบูรณ์</div>
            </div>
            <div class="right-item">
                <div class="right-icon">🗑️</div>
                <div class="right-title">สิทธิ์ในการลบ</div>
                <div class="right-desc">ขอให้ลบข้อมูลส่วนบุคคลของคุณ</div>
            </div>
            <div class="right-item">
                <div class="right-icon">⏸️</div>
                <div class="right-title">สิทธิ์ในการระงับ</div>
                <div class="right-desc">ขอจำกัดการประมวลผลข้อมูลชั่วคราว</div>
            </div>
            <div class="right-item">
                <div class="right-icon">📦</div>
                <div class="right-title">สิทธิ์ในการโอนย้าย</div>
                <div class="right-desc">ขอรับข้อมูลในรูปแบบที่อ่านได้ด้วยเครื่อง</div>
            </div>
            <div class="right-item">
                <div class="right-icon">🚫</div>
                <div class="right-title">สิทธิ์ในการคัดค้าน</div>
                <div class="right-desc">คัดค้านการประมวลผลข้อมูลบางประเภท</div>
            </div>
            <div class="right-item">
                <div class="right-icon">↩️</div>
                <div class="right-title">สิทธิ์ในการถอนความยินยอม</div>
                <div class="right-desc">ถอนความยินยอมที่เคยให้ไว้เมื่อใดก็ได้</div>
            </div>
            <div class="right-item">
                <div class="right-icon">📢</div>
                <div class="right-title">สิทธิ์ในการร้องเรียน</div>
                <div class="right-desc">ร้องเรียนต่อสำนักงานคุ้มครองข้อมูลส่วนบุคคล</div>
            </div>
        </div>
        <p>หากต้องการใช้สิทธิ์ข้างต้น กรุณาติดต่อเราผ่านช่องทางที่ระบุในหัวข้อ "ติดต่อเรา" เราจะดำเนินการภายใน 30 วันนับจากได้รับคำร้อง</p>
    </div>

    <!-- 9. Cookies -->
    <div class="section" id="cookies">
        <h2><span class="icon">🍪</span> 9. นโยบาย Cookies</h2>
        <p>เราใช้ Cookies และเทคโนโลยีที่คล้ายคลึงเพื่อ:</p>
        <ul>
            <li><strong>Cookies ที่จำเป็น:</strong> เพื่อการทำงานพื้นฐานของเว็บไซต์ (การเข้าสู่ระบบ, ตะกร้าสินค้า, CSRF token)</li>
            <li><strong>Cookies เพื่อประสิทธิภาพ:</strong> เพื่อจดจำการตั้งค่าและปรับปรุงประสบการณ์ใช้งาน</li>
            <li><strong>Cookies เพื่อการวิเคราะห์:</strong> เพื่อเข้าใจพฤติกรรมการใช้งานและปรับปรุงบริการ</li>
        </ul>
        <p>คุณสามารถจัดการ Cookies ผ่านการตั้งค่าเบราว์เซอร์ของคุณได้ อย่างไรก็ตาม การปิด Cookies บางประเภทอาจส่งผลต่อการทำงานของเว็บไซต์</p>
    </div>

    <!-- 10. เด็ก -->
    <div class="section" id="children">
        <h2><span class="icon">👶</span> 10. ข้อมูลเด็กและเยาวชน</h2>
        <p>บริการของเราไม่ได้มุ่งเป้าไปที่บุคคลที่มีอายุต่ำกว่า 13 ปี เราจะไม่เก็บรวบรวมข้อมูลส่วนบุคคลจากเด็กอายุต่ำกว่า 13 ปีโดยเจตนา หากเราทราบว่ามีการเก็บข้อมูลดังกล่าว เราจะดำเนินการลบทันที</p>
        <p>สำหรับเยาวชนอายุ 13-20 ปี การใช้บริการต้องได้รับความยินยอมจากผู้ปกครองหรือผู้แทนโดยชอบธรรม</p>
    </div>

        @endverbatim
    {{-- หัวข้อ 11–13 อยู่นอกบล็อก verbatim เพื่อใช้ลิงก์/อีเมลติดต่อจากระบบ (ContactInfo) — PLAY-05 / PLAY-25 --}}
    @php($privacyContactEmail = \App\Support\ContactInfo::supportEmail())
    <!-- 11. การลบข้อมูล -->
    <div class="section" id="delete">
        <h2><span class="icon">🗑️</span> 11. การลบบัญชีและข้อมูล</h2>
        <p>คุณสามารถลบบัญชีและขอให้ลบข้อมูลส่วนบุคคลได้ตลอดเวลาผ่านช่องทางต่อไปนี้:</p>
        <ul>
            <li><strong>ในแอป Thaiprompt</strong> — ตั้งค่า → ลบบัญชี</li>
            <li><strong>บนเว็บไซต์</strong> — หน้า <a href="{{ route('account.delete') }}">{{ route('account.delete') }}</a> (เข้าสู่ระบบแล้วยืนยันการลบ)</li>
            <li><strong>ทางอีเมล</strong> — ส่งจากอีเมลที่ใช้สมัครไปที่ <a href="mailto:{{ $privacyContactEmail }}">{{ $privacyContactEmail }}</a> หัวข้อ "ขอลบบัญชี" (กรณีเข้าสู่ระบบไม่ได้)</li>
            <li><strong>ลูกค้าบริการดูดวงผ่าน Facebook Messenger / LINE</strong> — พิมพ์ขอลบข้อมูลในแชทของเพจ/บัญชีทางการได้โดยตรง</li>
        </ul>
        <div class="highlight-box">
            <p>เมื่อลบบัญชี ข้อมูลที่ระบุตัวตนได้ (ชื่อ อีเมล เบอร์โทร ที่อยู่ ข้อมูลธนาคาร เอกสารยืนยันตัวตน การเชื่อมต่อ LINE/Facebook และ push token) จะถูกลบหรือปกปิดทันที และออกจากระบบทุกอุปกรณ์ ส่วนข้อมูลธุรกรรมทางการเงินและคำสั่งซื้อจะเก็บไว้ตามระยะเวลาที่กฎหมายกำหนดโดยไม่ผูกกับตัวตนของคุณ คำขอทางอีเมลดำเนินการภายใน 30 วัน</p>
            <p>ก่อนลบบัญชี ยอดเงินในกระเป๋าต้องเป็น 0 และต้องไม่มีคำสั่งซื้อ งานส่งของ หรือคำขอถอนเงินที่ยังไม่เสร็จ</p>
        </div>
    </div>

    <!-- 12. การเปลี่ยนแปลง -->
    <div class="section" id="changes">
        <h2><span class="icon">🔄</span> 12. การเปลี่ยนแปลงนโยบาย</h2>
        <p>เราอาจปรับปรุงนโยบายความเป็นส่วนตัวนี้เป็นครั้งคราว เพื่อสะท้อนการเปลี่ยนแปลงในการดำเนินงานหรือข้อกำหนดทางกฎหมาย โดยจะแจ้งให้ทราบผ่าน:</p>
        <ul>
            <li>ประกาศบนเว็บไซต์</li>
            <li>แจ้งเตือนผ่าน Facebook Messenger (สำหรับการเปลี่ยนแปลงสำคัญ)</li>
            <li>อัปเดตวันที่ "อัปเดตล่าสุด" ด้านบนของนโยบายนี้</li>
        </ul>
    </div>

    <!-- 13. ติดต่อเรา -->
    <div class="section" id="contact">
        <h2><span class="icon">📬</span> 13. ติดต่อเรา</h2>
        <p>หากคุณมีคำถาม ข้อกังวล หรือต้องการใช้สิทธิ์เกี่ยวกับข้อมูลส่วนบุคคลของคุณ สามารถติดต่อเราได้ที่:</p>
        <div class="contact-grid">
            <div class="contact-item">
                <div class="c-icon">📧</div>
                <div class="c-label">อีเมล</div>
                <div class="c-value"><a href="mailto:{{ $privacyContactEmail }}">{{ $privacyContactEmail }}</a></div>
            </div>
            <div class="contact-item">
                <div class="c-icon">🌐</div>
                <div class="c-label">เว็บไซต์</div>
                <div class="c-value">main.thaiprompt.online</div>
            </div>
        </div>
        <p style="margin-top: 16px; color: #94a3b8; font-size: 0.9em;">ติดต่อเจ้าหน้าที่คุ้มครองข้อมูลส่วนบุคคล (DPO) ได้ผ่านอีเมลด้านบน</p>
    </div>

    <div class="footer">
        <p>&copy; 2026 Thaiprompt / TP-Affiliate. สงวนลิขสิทธิ์.</p>
        <p style="margin-top: 8px;"><a href="https://main.thaiprompt.online">กลับสู่หน้าหลัก</a></p>
    </div>
</div>
    </div>

    <div style="text-align:center; margin-top:28px;">
        <a href="{{ url('/') }}" class="tp-btn tp-btn-sm" style="text-decoration:none;"><i class="fas fa-arrow-left"></i> <span>กลับหน้าแรก</span></a>
    </div>
</div>
@endsection
