{{--
 | ธีมโนวาสำหรับหน้าเข้าสู่ระบบ / สมัครสมาชิก (หน้าเต็มไม่มี layout)
 | ทับสีม่วง-ฟ้าเดิมด้วยกรมท่า-ทองของธีมโนวา — แตะเฉพาะสี/ฟอนต์ ไม่แตะฟอร์ม ช่องกรอก Turnstile หรือสคริปต์
 | ใส่ใน <head> หลัง <style> ของหน้า + ใส่คลาส nv-auth ที่ <body> (ปิดได้ด้วย config shop.nova_public)
 --}}
@if(config('shop.nova_public', true))
<link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@400;500;600;700&family=Trirong:wght@600;700&family=Cinzel:wght@600&display=swap" rel="stylesheet">
<style>
    body.nv-auth {
        font-family: 'Anuphan', 'Kanit', sans-serif;
        background: radial-gradient(120% 70% at 50% 112%, rgba(212, 166, 74, .24) 0%, rgba(212, 166, 74, 0) 55%),
                    radial-gradient(90% 60% at 50% -12%, #1d3676 0%, rgba(29, 54, 118, 0) 62%),
                    linear-gradient(180deg, #081230 0%, #0a1636 42%, #070d22 76%, #050916 100%) !important;
    }
    /* แสงฟุ้งพื้นหลัง: ฟ้าคราม/ม่วง/ชมพูเดิม → กรมท่าหลวง · ทอง · ม่วงราตรีจางๆ */
    .nv-auth .bg-indigo-600\/30, .nv-auth .bg-blue-600\/20 { background-color: rgba(64, 98, 200, .26) !important; }
    .nv-auth .bg-purple-600\/25, .nv-auth .bg-purple-600\/20 { background-color: rgba(212, 166, 74, .16) !important; }
    .nv-auth .bg-pink-500\/15, .nv-auth .bg-pink-600\/10 { background-color: rgba(116, 78, 170, .14) !important; }
    /* หิ่งห้อย (หน้าเข้าสู่ระบบ) เป็นประกายทอง */
    .nv-auth .firefly::before { background: radial-gradient(circle, rgba(255, 226, 150, .9) 0%, rgba(240, 201, 106, .55) 40%, transparent 70%) !important; }
    .nv-auth .firefly::after { background: radial-gradient(circle, #fff 0%, rgba(255, 240, 200, .85) 30%, transparent 60%) !important; }
    /* การ์ดกระจกกรมท่า ขอบทองบาง */
    .nv-auth .glass-card {
        background: linear-gradient(160deg, rgba(20, 36, 84, .78), rgba(7, 13, 32, .86)) !important;
        border: 1px solid rgba(245, 210, 127, .26) !important;
        box-shadow: 0 34px 80px -30px rgba(0, 0, 0, .85), inset 0 1px 0 rgba(255, 255, 255, .07) !important;
    }
    .nv-auth .glass-card.border-green-500\/30 { border-color: rgba(143, 220, 154, .35) !important; }
    .nv-auth h1, .nv-auth h2 { font-family: 'Trirong', 'Anuphan', serif; letter-spacing: 0; }
    .nv-auth h1 { color: #fbf6ea; }
    /* แสงหลังโลโก้ */
    .nv-auth .from-blue-500.to-purple-500 { background-image: radial-gradient(closest-side, rgba(240, 201, 106, .7), rgba(240, 201, 106, 0)) !important; }
    /* ไอคอน/ลิงก์ฟ้า-ม่วง → ทอง */
    .nv-auth .text-blue-400, .nv-auth .text-purple-400, .nv-auth .text-indigo-300 { color: #f0c96a !important; }
    .nv-auth a.text-blue-400:hover { color: #fbe3a8 !important; }
    .nv-auth .bg-indigo-500\/20 { background-color: rgba(240, 201, 106, .1) !important; border-color: rgba(240, 201, 106, .3) !important; }
    .nv-auth .bg-blue-500\/10 { background-color: rgba(64, 98, 200, .14) !important; }
    .nv-auth .bg-purple-500\/10, .nv-auth .bg-purple-500\/20, .nv-auth .bg-blue-500\/20 { background-color: rgba(240, 201, 106, .1) !important; }
    .nv-auth .bg-blue-500, .nv-auth .bg-purple-500 { background-image: linear-gradient(180deg, #fbe3a8, #d4a64a) !important; color: #1a1405 !important; }
    .nv-auth .bg-blue-500 i, .nv-auth .bg-purple-500 i { color: #1a1405 !important; }
    /* ช่องกรอก */
    .nv-auth .input-glow { background-color: rgba(255, 255, 255, .05) !important; border-color: rgba(245, 210, 127, .22) !important; }
    .nv-auth .input-glow:focus { border-color: #f0c96a !important; box-shadow: 0 0 0 3px rgba(240, 201, 106, .22), 0 0 22px rgba(240, 201, 106, .14) !important; }
    .nv-auth input[type="checkbox"] { accent-color: #d4a64a; }
    /* ปุ่มหลัก (ส่งฟอร์ม) = ทองโนวา ตัวอักษรเข้ม */
    .nv-auth button[type="submit"].btn-shine {
        background-image: linear-gradient(180deg, #fbe3a8 0%, #f0c96a 38%, #d4a64a 100%) !important;
        color: #1a1405 !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .7), inset 0 -2px 0 rgba(138, 100, 32, .35), 0 12px 28px -12px rgba(240, 201, 106, .8) !important;
    }
    .nv-auth button[type="submit"].btn-shine:disabled { filter: grayscale(.5); opacity: .6; }
</style>
@endif
