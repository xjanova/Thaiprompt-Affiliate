{{--
 | ใบเสร็จ POS (หน้าพิมพ์แบบ standalone — ไม่ใช้ layout)
 | (2026-09-25) GAP-26: เอา Tailwind Play CDN (cdn.tailwindcss.com) ออกจาก production
 |   → ใช้ CSS ในหน้าเพียงเล็กน้อยสำหรับเครื่องพิมพ์ความร้อน 58/80 มม.
 --}}
@php
    $store = $transaction->store;
    $posSettings = \App\Models\PosSetting::where('store_id', $transaction->store_id)->first();
    $paper = ($posSettings->receipt_size ?? '80mm') === '58mm' ? '58mm' : '80mm';
    $storeTitle = $posSettings->store_display_name ?? null ?: ($store->store_name ?? config('app.name'));
    $taxId = $posSettings->tax_id_number ?? null ?: ($store->tax_id ?? null);
    $payLabels = ['cash' => 'เงินสด', 'card' => 'บัตรเครดิต/เดบิต', 'qr' => 'QR พร้อมเพย์', 'bank_transfer' => 'โอนเงิน', 'e-wallet' => 'e-Wallet', 'credit' => 'เครดิต', 'multiple' => 'หลายช่องทาง', 'other' => 'อื่น ๆ'];
    $paid = $transaction->amount_paid ?? null;
    $autoPrint = (bool) ($posSettings->auto_print_receipt ?? false);
@endphp
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>ใบเสร็จ {{ $transaction->receipt_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --paper: {{ $paper }}; }
        * { box-sizing: border-box; }
        body { margin: 0; background: rgb(236, 232, 224); font-family: 'Anuphan', sans-serif; color: black; font-size: 12px; }
        .sheet { width: var(--paper); max-width: 100%; margin: 16px auto; background: white; padding: 4mm; box-shadow: 0 6px 20px rgba(0,0,0,.12); }
        .c { text-align: center; }
        .muted { color: rgba(0,0,0,.62); }
        .row { display: flex; justify-content: space-between; gap: 6px; }
        .dash { border-top: 1px dashed rgba(0,0,0,.55); margin: 6px 0; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 2px 0; vertical-align: top; }
        th { font-weight: 700; border-bottom: 1px solid rgba(0,0,0,.35); }
        .r { text-align: right; } .m { text-align: center; }
        .big { font-size: 15px; font-weight: 700; }
        .actions { display: flex; gap: 8px; width: var(--paper); max-width: 100%; margin: 0 auto 24px; }
        .actions button { flex: 1; font: inherit; font-weight: 700; padding: 10px; border-radius: 10px; border: 0; cursor: pointer; }
        .btn-print { background: rgb(217, 142, 63); color: white; }
        .btn-close { background: rgb(214, 207, 195); color: black; }
        @media print {
            @page { size: var(--paper) auto; margin: 0; }
            body { background: white; }
            .sheet { margin: 0; box-shadow: none; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="sheet" id="receipt">
        <div class="c">
            <h1>{{ $storeTitle }}</h1>
            @if($store && ($store->store_address ?? null))<div class="muted">{{ $store->store_address }}</div>@endif
            @if($store && ($store->store_phone ?? null))<div class="muted">โทร {{ $store->store_phone }}</div>@endif
            @if($taxId)<div class="muted">เลขประจำตัวผู้เสียภาษี {{ $taxId }}</div>@endif
            @if($posSettings && $posSettings->receipt_header)<div style="margin-top:4px;">{{ $posSettings->receipt_header }}</div>@endif
        </div>

        <div class="dash"></div>
        <div class="row"><span>เลขที่</span><span>{{ $transaction->receipt_number }}</span></div>
        <div class="row"><span>วันที่</span><span>{{ optional($transaction->transaction_date)->format('d/m/Y H:i') }}</span></div>
        <div class="row"><span>พนักงาน</span><span>{{ $transaction->user->name ?? '-' }}</span></div>
        @if($transaction->customer_name)<div class="row"><span>ลูกค้า</span><span>{{ $transaction->customer_name }}</span></div>@endif

        <div class="dash"></div>
        <table>
            <thead>
                <tr><th style="text-align:left;">รายการ</th><th class="m">จำนวน</th><th class="r">รวม</th></tr>
            </thead>
            <tbody>
                @foreach($transaction->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}<div class="muted">@ {{ number_format((float) $item->unit_price, 2) }}</div></td>
                        <td class="m">{{ $item->quantity }}</td>
                        <td class="r">{{ number_format((float) ($item->total ?? $item->unit_price * $item->quantity), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="dash"></div>
        <div class="row"><span>ยอดรวม</span><span>{{ number_format((float) $transaction->subtotal, 2) }}</span></div>
        @if($transaction->discount_amount > 0)<div class="row"><span>ส่วนลด</span><span>-{{ number_format((float) $transaction->discount_amount, 2) }}</span></div>@endif
        @if($transaction->tax_amount > 0)<div class="row"><span>VAT</span><span>{{ number_format((float) $transaction->tax_amount, 2) }}</span></div>@endif
        <div class="row big" style="margin-top:4px;"><span>รวมทั้งสิ้น</span><span>฿{{ number_format((float) $transaction->total_amount, 2) }}</span></div>

        <div class="dash"></div>
        <div class="row"><span>ชำระโดย</span><span>{{ $payLabels[$transaction->payment_method] ?? $transaction->payment_method }}</span></div>
        @if(! is_null($paid))<div class="row"><span>รับเงิน</span><span>{{ number_format((float) $paid, 2) }}</span></div>@endif
        @if($transaction->change_amount > 0)<div class="row" style="font-weight:700;"><span>เงินทอน</span><span>{{ number_format((float) $transaction->change_amount, 2) }}</span></div>@endif

        <div class="dash"></div>
        <div class="c muted">
            <div>{{ $posSettings && $posSettings->receipt_footer ? $posSettings->receipt_footer : 'ขอบคุณที่ใช้บริการ' }}</div>
            <div style="margin-top:2px;">{{ $transaction->transaction_code }}</div>
        </div>
    </div>

    <div class="actions no-print">
        <button type="button" class="btn-print" onclick="window.print()">🖨️ พิมพ์ใบเสร็จ</button>
        <button type="button" class="btn-close" onclick="window.close()">ปิด</button>
    </div>

    @if($autoPrint)
        <script>
            // ตั้งค่า POS: พิมพ์อัตโนมัติเมื่อเปิดหน้า
            window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });
        </script>
    @endif
</body>
</html>
