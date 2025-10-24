<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #6366f1; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9fafb; }
        .order-details { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .item { border-bottom: 1px solid #e5e7eb; padding: 10px 0; }
        .total { font-size: 18px; font-weight: bold; margin-top: 15px; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ยืนยันคำสั่งซื้อ</h1>
        </div>

        <div class="content">
            <p>สวัสดีคุณ {{ $order->user->name }}</p>
            <p>ขอบคุณสำหรับคำสั่งซื้อของคุณ!</p>

            <div class="order-details">
                <h3>รายละเอียดคำสั่งซื้อ #{{ $order->order_number }}</h3>
                <p><strong>วันที่:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                <p><strong>สถานะ:</strong> {{ ucfirst($order->status) }}</p>

                <h4>รายการสินค้า:</h4>
                @foreach($items as $item)
                    <div class="item">
                        <p><strong>{{ $item->product_name }}</strong></p>
                        <p>จำนวน: {{ $item->quantity }} x ฿{{ number_format($item->price, 2) }} = ฿{{ number_format($item->subtotal, 2) }}</p>
                    </div>
                @endforeach

                <div class="total">
                    <p>ยอดรวมทั้งหมด: ฿{{ number_format($total, 2) }}</p>
                </div>
            </div>

            <p><strong>ที่อยู่จัดส่ง:</strong></p>
            <p>
                {{ $order->shipping_address }}<br>
                {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}
            </p>

            <p>คุณสามารถติดตามสถานะคำสั่งซื้อได้ที่ <a href="{{ route('customer.orders.show', $order->id) }}">คลิกที่นี่</a></p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
