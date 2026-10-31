{{-- แจ้งอีเมลเดิมว่าอีเมลบัญชีถูกเปลี่ยน (AccountEmailChangedMail) --}}
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อีเมลบัญชีของคุณถูกเปลี่ยน</title>
</head>
<body style="margin:0; padding:20px; background:#f4f4f4; font-family:'Sarabun','Segoe UI',Tahoma,sans-serif; color:#333; line-height:1.7;">
    <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:10px; padding:28px;">
        <h1 style="margin:0 0 16px; font-size:20px; color:#b43c38;">อีเมลบัญชีของคุณถูกเปลี่ยน</h1>
        <p style="margin:0 0 12px;">สวัสดีคุณ {{ $userName }}</p>
        <p style="margin:0 0 12px;">
            อีเมลของบัญชี {{ config('app.name') }} ถูกเปลี่ยนจาก <strong>{{ $oldEmail }}</strong>
            เป็น <strong>{{ $newEmailMasked }}</strong> เมื่อ {{ $changedAt }} น.
            อีเมลนี้จะไม่ได้รับข่าวสารของบัญชีอีกต่อไป
        </p>
        <p style="margin:0 0 12px;">ถ้าคุณเป็นคนเปลี่ยนเอง ไม่ต้องทำอะไรเพิ่ม</p>
        <div style="margin:16px 0; padding:14px 16px; border-radius:8px; background:#fdecea; color:#7a2622;">
            <strong>ถ้าคุณไม่ได้เปลี่ยนอีเมล</strong> บัญชีของคุณอาจถูกผู้อื่นเข้าใช้งาน
            กรุณาติดต่อทีมงานทันทีที่ <a href="mailto:{{ $supportEmail }}" style="color:#7a2622;">{{ $supportEmail }}</a>
            และถ้ายังเข้าสู่ระบบได้ ให้เปลี่ยนรหัสผ่านที่ <a href="{{ $profileUrl }}" style="color:#7a2622;">หน้าโปรไฟล์</a>
        </div>
        <p style="margin:16px 0 0; font-size:12px; color:#888;">อีเมลนี้ส่งอัตโนมัติจากระบบ กรุณาอย่าตอบกลับ</p>
    </div>
</body>
</html>
