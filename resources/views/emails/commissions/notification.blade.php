<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #10b981; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9fafb; }
        .commission-box { background: white; padding: 20px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #10b981; }
        .amount { font-size: 32px; font-weight: bold; color: #10b981; margin: 15px 0; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 คุณได้รับค่าคอมมิชชั่น!</h1>
        </div>

        <div class="content">
            <p>สวัสดีคุณ {{ $commission->user->name }}</p>

            <div class="commission-box">
                <h3>รายละเอียดค่าคอมมิชชั่น</h3>
                <p><strong>ประเภท:</strong> {{ $type }}</p>
                <p><strong>จาก:</strong> คำสั่งซื้อ #{{ $commission->order->order_number ?? 'N/A' }}</p>
                <p><strong>วันที่:</strong> {{ $commission->created_at->format('d/m/Y H:i') }}</p>

                <div class="amount">
                    ฿{{ number_format($amount, 2) }}
                </div>

                <p><strong>สถานะ:</strong> {{ ucfirst($commission->status) }}</p>
            </div>

            <p>ค่าคอมมิชชั่นจะถูกเพิ่มเข้ากระเป๋าเงินของคุณภายใน 24-48 ชั่วโมง</p>
            <p>ดูรายละเอียดเพิ่มเติมได้ที่ <a href="{{ route('mlm.commissions') }}">แดชบอร์ด MLM</a></p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
