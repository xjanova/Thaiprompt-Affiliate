<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบส่งของ - คำสั่งซื้อ #{{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "Sarabun", sans-serif;
            font-size: 14px;
            line-height: 1.6;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-box {
            border: 1px solid #ddd;
            padding: 15px;
        }
        .info-box h3 {
            font-size: 16px;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .info-box p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th,
        table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ใบส่งของ / PACKING SLIP</h1>
        <p>คำสั่งซื้อ #{{ $order->order_number }}</p>
        <p>วันที่: {{ $order->created_at->format('d/m/Y') }}</p>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <h3>จากผู้ขาย</h3>
            @php
                $seller = auth()->user();
                $store = \App\Models\VendorStore::where('user_id', $seller->id)->first();
            @endphp
            <p><strong>{{ $store ? $store->store_name : $seller->name }}</strong></p>
            @if($store && $store->phone)
                <p>โทร: {{ $store->phone }}</p>
            @endif
            @if($store && $store->email)
                <p>อีเมล: {{ $store->email }}</p>
            @endif
        </div>

        <div class="info-box">
            <h3>ถึงลูกค้า</h3>
            @if($order->shippingAddress)
                <p><strong>{{ $order->shippingAddress->full_name }}</strong></p>
                <p>{{ $order->shippingAddress->phone }}</p>
                <p>{{ $order->shippingAddress->address_line1 }}</p>
                @if($order->shippingAddress->address_line2)
                    <p>{{ $order->shippingAddress->address_line2 }}</p>
                @endif
                <p>{{ $order->shippingAddress->district }}, {{ $order->shippingAddress->city }}</p>
                <p>{{ $order->shippingAddress->province }} {{ $order->shippingAddress->postal_code }}</p>
            @else
                <p><strong>{{ $order->user->name }}</strong></p>
                <p>{{ $order->user->email }}</p>
                @if($order->user->phone)
                    <p>{{ $order->user->phone }}</p>
                @endif
            @endif
        </div>
    </div>

    @if($order->tracking_number)
    <div class="info-box" style="margin-bottom: 20px;">
        <h3>ข้อมูลการจัดส่ง</h3>
        <p><strong>บริษัทขนส่ง:</strong> {{ $order->shipping_provider }}</p>
        <p><strong>เลขพัสดุ:</strong> {{ $order->tracking_number }}</p>
        @if($order->shipped_at)
            <p><strong>วันที่จัดส่ง:</strong> {{ $order->shipped_at->format('d/m/Y H:i') }}</p>
        @endif
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">ลำดับ</th>
                <th style="width: 40%;">รายการสินค้า</th>
                <th style="width: 15%;">SKU</th>
                <th style="width: 10%;">จำนวน</th>
                <th style="width: 12%;">ราคา/หน่วย</th>
                <th style="width: 13%;">รวม</th>
            </tr>
        </thead>
        <tbody>
            @php
                $sellerItems = $order->items->where('seller_id', auth()->id());
                $totalAmount = 0;
            @endphp
            @foreach($sellerItems as $index => $item)
                @php
                    $totalAmount += $item->total;
                @endphp
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->product_sku ?? '-' }}</td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td style="text-align: right;">฿{{ number_format($item->price, 2) }}</td>
                    <td style="text-align: right;">฿{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5" style="text-align: right;">รวมทั้งหมด</td>
                <td style="text-align: right;">฿{{ number_format($totalAmount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 30px; padding: 15px; border: 1px solid #ddd; background-color: #f9f9f9;">
        <p><strong>หมายเหตุ:</strong></p>
        <p>กรุณาตรวจสอบสินค้าทันทีเมื่อได้รับ หากมีปัญหาโปรดติดต่อเราภายใน 7 วัน</p>
    </div>

    <div class="footer">
        <p>ขอบคุณที่ใช้บริการ</p>
        <p>พิมพ์เมื่อ: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background-color: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
            🖨️ พิมพ์
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background-color: #6b7280; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-left: 10px;">
            ปิด
        </button>
    </div>

    <script>
        // Auto print when page loads
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>
