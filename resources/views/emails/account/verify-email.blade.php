{{-- ลิงก์ยืนยันอีเมลบัญชี (VerifyAccountEmailMail) --}}
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันอีเมลบัญชีของคุณ</title>
</head>
<body style="margin:0; padding:20px; background:#f4f4f4; font-family:'Sarabun','Segoe UI',Tahoma,sans-serif; color:#333; line-height:1.7;">
    <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:10px; padding:28px;">
        <h1 style="margin:0 0 16px; font-size:20px; color:#8a6d1f;">ยืนยันอีเมลบัญชีของคุณ</h1>
        <p style="margin:0 0 12px;">สวัสดีคุณ {{ $userName }}</p>
        <p style="margin:0 0 20px;">กดปุ่มด้านล่างเพื่อยืนยันว่าอีเมลนี้เป็นของคุณ และใช้รับข่าวสารของบัญชี {{ config('app.name') }}</p>
        <p style="margin:0 0 20px; text-align:center;">
            <a href="{{ $verifyUrl }}" style="display:inline-block; padding:12px 28px; border-radius:8px; background:#c8a24a; color:#fff; text-decoration:none; font-weight:700;">ยืนยันอีเมล</a>
        </p>
        <p style="margin:0 0 8px; font-size:13px; color:#666;">ลิงก์นี้ใช้ได้ {{ $hours }} ชั่วโมง ถ้าหมดอายุ ขอลิงก์ใหม่ได้ที่หน้าโปรไฟล์</p>
        <p style="margin:0 0 8px; font-size:12px; color:#888; word-break:break-all;">กดปุ่มไม่ได้? คัดลอกลิงก์นี้ไปเปิดในเบราว์เซอร์: {{ $verifyUrl }}</p>
        <p style="margin:16px 0 0; font-size:12px; color:#888;">ถ้าคุณไม่ได้เปลี่ยนอีเมลบัญชี ไม่ต้องกดลิงก์ และเพิกเฉยต่ออีเมลนี้ได้</p>
    </div>
</body>
</html>
