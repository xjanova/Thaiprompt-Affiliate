{{-- 🔑 กล่องเชื่อมบัญชีเจ้าของเพจ — ใช้ทั้งตอนยังไม่เชื่อม และตอนเปลี่ยนบัญชี --}}
{{-- 🔗 ทางหลัก — กดปุ่มเดียวจบ ไม่ต้องไปก็อป token เอง
     (ทางวาง token มือยังอยู่ข้างล่าง เผื่อ OAuth มีปัญหา) --}}
<a href="{{ route('admin.fortune.pages.connect-oauth') }}" class="tp-btn tp-btn-primary"
   style="display:inline-flex; align-items:center; gap:9px; background:#1877F2; border-color:#1877F2; color:#fff; font-weight:700; padding:12px 20px;">
    <i class="fab fa-facebook"></i> Connect Facebook Page
</a>
<div style="margin-top:8px; font-size:12.5px; color:var(--ink2); line-height:1.8;">
    กดแล้ว Facebook จะถามว่าอนุญาตให้แอปเข้าถึงเพจไหน — เลือกเพจแล้วกดอนุญาต ระบบดึงกุญแจของทุกเพจมาให้เอง<br>
    <span style="opacity:.85;">Grants the app access to your Page, then the system retrieves and stores each Page token automatically.</span>
</div>

<details style="margin-top:16px;">
    <summary style="cursor:pointer; font-size:12.5px; color:var(--ink2);">
        หรือวาง User Access Token เอง (ทางสำรอง)
    </summary>
<form method="POST" action="{{ route('admin.fortune.pages.connect') }}"
      style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-top:10px;">
    @csrf
    <div class="tp-well tp-input" style="padding:0; flex:1; min-width:260px;">
        <input type="password" name="facebook_user_token" required autocomplete="new-password"
               placeholder="วาง User Access Token ของเจ้าของเพจ"
               style="width:100%; background:transparent; border:0; outline:0; padding:12px 14px; color:var(--ink); font-size:14px; font-family:monospace;">
    </div>
    <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-link"></i> เชื่อมบัญชี</button>
</form>

<div style="margin-top:12px; font-size:12px; color:var(--ink2); line-height:1.95;">
    <strong>เอา token มาจากไหน:</strong>
    เข้า <a href="https://developers.facebook.com/tools/explorer/" target="_blank" rel="noopener noreferrer">Graph API Explorer</a>
    → เลือกแอปเดียวกับที่ใช้อยู่ → กด <em>Generate Access Token</em> → ติ๊กสิทธิ์
    <code>pages_show_list</code>, <code>pages_messaging</code>, <code>pages_manage_metadata</code>,
    <code>pages_read_engagement</code>,
    <code>pages_read_user_content</code>, <code>pages_manage_engagement</code>,
    <code>pages_manage_posts</code> → ก็อปมาวางที่ช่องนี้<br>
    🛡️ <strong>pages_read_user_content + pages_manage_engagement คือระบบกรองคอมเมนต์สแปม</strong> (อ่านคอมเมนต์ + ซ่อนคอมเมนต์) — ขาดไปจะตรวจเจอสแปมแต่ซ่อนไม่ได้<br>
    📅 <strong>pages_manage_posts คือการโพสดวงรายวัน/คอนเทนต์ลงหน้าเพจ</strong> — ขาดไปบอทยังตอบแชทได้ แต่เพจจะไม่มีโพสอัตโนมัติเลย<br>
    ⏳ <strong>ไม่ต้องไปกด Extend เองที่ Access Token Debugger</strong> — ระบบแลกเป็นตัวอายุยาว (60 วัน) ให้อัตโนมัติตอนกดเชื่อม
    (token สดจาก Explorer อายุแค่ ~1 ชม. และกุญแจของเพจที่ขอด้วยตัวสั้นก็จะอายุสั้นตามไปด้วย)<br>
    🔒 ระบบเก็บแบบเข้ารหัสและไม่แสดงค่ากลับออกมาอีก · ถ้าหมดอายุแค่มาวางใหม่
</div>
</details>
