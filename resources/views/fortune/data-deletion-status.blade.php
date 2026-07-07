{{-- 🗑️ หน้าเช็คสถานะการลบข้อมูลตาม PDPA (standalone — Facebook users เปิดจากลิงก์ callback) --}}
@php
    /** @var \App\Models\FortuneDataDeletionRequest|null $request */
    $status = $request?->status;
    $map = [
        'completed' => ['ลบข้อมูลเรียบร้อยแล้ว', 'ข้อมูลดูดวงและบทสนทนาทั้งหมดถูกลบออกจากระบบเรียบร้อยแล้ว', '#16a34a', '✅'],
        'processing' => ['กำลังดำเนินการลบข้อมูล', 'ระบบกำลังลบข้อมูลของคุณ กรุณากลับมาตรวจสอบอีกครั้งในภายหลัง', '#d97706', '⏳'],
        'pending' => ['ได้รับคำขอลบข้อมูลแล้ว', 'ระบบได้รับคำขอและกำลังจัดคิวดำเนินการลบ', '#d97706', '⏳'],
        'failed' => ['เกิดข้อผิดพลาด', 'ไม่สามารถลบข้อมูลได้ในขณะนี้ กรุณาติดต่อทีมงาน', '#dc2626', '⚠️'],
    ];
    [$title, $detail, $color, $icon] = $map[$status] ?? ['ไม่พบคำขอลบข้อมูล', 'ไม่พบคำขอที่ตรงกับรหัสอ้างอิงนี้ในระบบ', '#6b7280', '❓'];
@endphp
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>สถานะการลบข้อมูล — แม่หมอจันทรา</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Sarabun", "Kanit", sans-serif;
            background: #f3f4f6; color: #111827;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; padding: 20px; line-height: 1.6;
        }
        .card {
            background: #ffffff; border-radius: 20px; max-width: 480px; width: 100%;
            padding: 40px 32px; box-shadow: 0 10px 40px rgba(0,0,0,.08); text-align: center;
        }
        .icon { font-size: 56px; margin-bottom: 12px; }
        .badge {
            display: inline-block; font-size: 13px; font-weight: 600; color: #fff;
            padding: 4px 14px; border-radius: 999px; margin-bottom: 18px;
        }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 12px; }
        p.detail { font-size: 15px; color: #4b5563; margin-bottom: 24px; }
        .ref {
            font-size: 13px; color: #6b7280; background: #f9fafb; border: 1px solid #e5e7eb;
            border-radius: 10px; padding: 10px 14px; word-break: break-all; margin-bottom: 20px;
        }
        .note { font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 16px; }
        @media (prefers-color-scheme: dark) {
            body { background: #0f172a; color: #e5e7eb; }
            .card { background: #1e293b; box-shadow: 0 10px 40px rgba(0,0,0,.4); }
            p.detail { color: #cbd5e1; }
            .ref { background: #0f172a; border-color: #334155; color: #94a3b8; }
            .note { color: #64748b; border-color: #334155; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">{{ $icon }}</div>
        <span class="badge" style="background: {{ $color }}">สถานะ PDPA</span>
        <h1>{{ $title }}</h1>
        <p class="detail">{{ $detail }}</p>
        <div class="ref">รหัสอ้างอิง: <strong>{{ $code }}</strong></div>
        <p class="note">
            การลบข้อมูลนี้ครอบคลุมคำทำนาย บทสนทนา และข้อมูลส่วนบุคคลของบริการดูดวง (แม่หมอจันทรา)
            รวมถึงการยุติสิทธิ์เงินปันผล ส่วนแบ่งการตลาด และสายงาน นับตั้งแต่วันที่แจ้งลบ<br><br>
            หากมีข้อสงสัย กรุณาติดต่อทีมงานผ่านช่องทางแชทของแม่หมอจันทรา
        </p>
    </div>
</body>
</html>
