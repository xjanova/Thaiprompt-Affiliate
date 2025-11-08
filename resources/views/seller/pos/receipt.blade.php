<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จ #{{ $transaction->receipt_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page { size: 80mm auto; margin: 0; }
            body { margin: 0; padding: 10mm; }
            .no-print { display: none; }
        }
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="max-w-sm mx-auto my-4 bg-white shadow-lg">
        <!-- Receipt Content -->
        <div class="p-4" id="receipt">
            <!-- Store Header -->
            <div class="text-center mb-4 border-b-2 border-dashed pb-4">
                <h1 class="text-2xl font-bold">{{ $transaction->store->store_name }}</h1>
                @if($transaction->store->address)
                <p class="text-sm text-gray-600">{{ $transaction->store->address }}</p>
                @endif
                @if($transaction->store->phone)
                <p class="text-sm text-gray-600">Tel: {{ $transaction->store->phone }}</p>
                @endif
                @if($transaction->store->tax_id)
                <p class="text-sm text-gray-600">เลขที่ผู้เสียภาษี: {{ $transaction->store->tax_id }}</p>
                @endif
            </div>

            <!-- Receipt Info -->
            <div class="text-sm mb-4 space-y-1">
                <div class="flex justify-between">
                    <span class="font-semibold">เลขที่ใบเสร็จ:</span>
                    <span>{{ $transaction->receipt_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-semibold">วันที่:</span>
                    <span>{{ $transaction->transaction_date->format('d/m/Y H:i') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-semibold">พนักงานขาย:</span>
                    <span>{{ $transaction->user->name }}</span>
                </div>
                @if($transaction->customer_name)
                <div class="flex justify-between">
                    <span class="font-semibold">ลูกค้า:</span>
                    <span>{{ $transaction->customer_name }}</span>
                </div>
                @endif
                @if($transaction->customer_phone)
                <div class="flex justify-between">
                    <span class="font-semibold">เบอร์โทร:</span>
                    <span>{{ $transaction->customer_phone }}</span>
                </div>
                @endif
            </div>

            <!-- Items -->
            <div class="border-t-2 border-b-2 border-dashed py-3 mb-3">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left pb-2">รายการ</th>
                            <th class="text-center pb-2">จำนวน</th>
                            <th class="text-right pb-2">ราคา</th>
                            <th class="text-right pb-2">รวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transaction->items as $item)
                        <tr>
                            <td class="py-1">{{ $item->product_name }}</td>
                            <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                            <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-right">{{ number_format($item->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="space-y-2 text-sm mb-4">
                <div class="flex justify-between">
                    <span>ยอดรวม:</span>
                    <span>฿{{ number_format($transaction->subtotal, 2) }}</span>
                </div>
                @if($transaction->discount_amount > 0)
                <div class="flex justify-between text-red-600">
                    <span>ส่วนลด:</span>
                    <span>-฿{{ number_format($transaction->discount_amount, 2) }}</span>
                </div>
                @endif
                @if($transaction->tax_amount > 0)
                <div class="flex justify-between">
                    <span>VAT:</span>
                    <span>฿{{ number_format($transaction->tax_amount, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between font-bold text-lg pt-2 border-t-2 border-dashed">
                    <span>รวมทั้งสิ้น:</span>
                    <span>฿{{ number_format($transaction->total_amount, 2) }}</span>
                </div>
            </div>

            <!-- Payment Info -->
            <div class="border-t-2 border-dashed pt-3 space-y-1 text-sm mb-4">
                <div class="flex justify-between">
                    <span class="font-semibold">วิธีชำระเงิน:</span>
                    <span>
                        @switch($transaction->payment_method)
                            @case('cash') เงินสด @break
                            @case('card') บัตรเครดิต/เดบิต @break
                            @case('qr') QR Code @break
                            @case('bank_transfer') โอนเงิน @break
                            @default {{ $transaction->payment_method }}
                        @endswitch
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="font-semibold">ยอดที่รับ:</span>
                    <span>฿{{ number_format($transaction->payment_amount, 2) }}</span>
                </div>
                @if($transaction->change_amount > 0)
                <div class="flex justify-between font-bold text-green-600">
                    <span>เงินทอน:</span>
                    <span>฿{{ number_format($transaction->change_amount, 2) }}</span>
                </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="text-center text-xs text-gray-600 border-t-2 border-dashed pt-3 space-y-1">
                <p>ขอบคุณที่ใช้บริการ</p>
                <p>{{ $transaction->transaction_code }}</p>
                <p class="mt-2">Powered by {{ \App\Models\Setting::get('app_name', 'TP-Affiliate') }}</p>
            </div>
        </div>

        <!-- Print Button -->
        <div class="p-4 bg-gray-100 no-print flex gap-2">
            <button onclick="window.print()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg font-bold">
                พิมพ์ใบเสร็จ
            </button>
            <button onclick="window.close()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-3 px-6 rounded-lg font-bold">
                ปิด
            </button>
        </div>
    </div>

    <script>
        // Auto print on load (optional)
        // window.onload = () => window.print();
    </script>
</body>
</html>
