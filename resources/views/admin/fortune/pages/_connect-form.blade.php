{{-- 🔑 กล่องเชื่อมบัญชีเจ้าของเพจ — ใช้ทั้งตอนยังไม่เชื่อม และตอนเปลี่ยนบัญชี --}}
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
    <code>pages_read_user_content</code>, <code>pages_manage_engagement</code> → ก็อปมาวางที่ช่องนี้<br>
    🛡️ <strong>2 ตัวท้ายคือระบบกรองคอมเมนต์สแปม</strong> (อ่านคอมเมนต์ + ซ่อนคอมเมนต์) — ขาดไปจะตรวจเจอสแปมแต่ซ่อนไม่ได้<br>
    ⏳ <strong>ไม่ต้องไปกด Extend เองที่ Access Token Debugger</strong> — ระบบแลกเป็นตัวอายุยาว (60 วัน) ให้อัตโนมัติตอนกดเชื่อม
    (token สดจาก Explorer อายุแค่ ~1 ชม. และกุญแจของเพจที่ขอด้วยตัวสั้นก็จะอายุสั้นตามไปด้วย)<br>
    🔒 ระบบเก็บแบบเข้ารหัสและไม่แสดงค่ากลับออกมาอีก · ถ้าหมดอายุแค่มาวางใหม่
</div>
