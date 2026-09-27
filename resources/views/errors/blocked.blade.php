{{--
 | 403 ที่อยู่ IP ถูกระงับ (CheckBlockedIp) — ธีมโนวา
 | middleware ส่ง $message (อังกฤษ) · $ip · $reason มา — หน้าแสดงข้อความไทยของเราเอง แล้วโชว์ IP/เหตุผลเป็นรายละเอียด
 | ⚠️ ห้ามลิงก์กลับเข้าเว็บเป็นปุ่มหลัก (IP นี้เปิดหน้าไหนก็โดนบล็อกซ้ำ) → ปุ่มหลัก = ติดต่อทีมงานทางอีเมล
 --}}
@php
    $nvBlockedDetails = [];
    if (! empty($ip)) {
        $nvBlockedDetails[] = ['label' => 'ที่อยู่ IP ของคุณ', 'value' => $ip, 'mono' => true];
    }
    if (! empty($reason)) {
        $nvBlockedDetails[] = ['label' => 'สาเหตุ', 'value' => $reason];
    }
    $nvBlockedDetails[] = ['label' => 'เวลา', 'value' => now()->format('d/m/Y H:i:s')];
@endphp
@include('errors.nova-page', [
    'nvCode' => '403',
    'nvTitle' => 'ระบบระงับการเข้าถึงชั่วคราว',
    'nvMessage' => 'ระบบรักษาความปลอดภัยระงับการเข้าถึงจากที่อยู่ IP นี้ หากใช้ VPN หรือพร็อกซีอยู่ ลองปิดแล้วเข้าใหม่ หากคิดว่าเป็นความผิดพลาด แจ้งทีมงานพร้อมที่อยู่ IP ด้านล่างได้เลย',
    'nvDeco' => 'bell.webp',
    'nvDetails' => $nvBlockedDetails,
    'nvActions' => [
        ['href' => 'mailto:'.\App\Support\ContactInfo::supportEmail(), 'label' => 'แจ้งทีมงานทางอีเมล', 'icon' => 'fa-envelope', 'primary' => true],
    ],
])
