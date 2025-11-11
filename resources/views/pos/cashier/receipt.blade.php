<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จ - {{ $transaction->receipt_number }}</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
        }

        .receipt {
            width: 80mm;
            margin: 0 auto;
            padding: 10mm;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .bold {
            font-weight: bold;
        }

        .large {
            font-size: 16px;
        }

        .small {
            font-size: 10px;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .divider-solid {
            border-top: 1px solid #000;
            margin: 8px 0;
        }

        .divider-double {
            border-top: 3px double #000;
            margin: 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table td {
            padding: 2px 0;
        }

        .items-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .mt-1 { margin-top: 4px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 12px; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 12px; }

        .barcode {
            text-align: center;
            margin: 10px 0;
        }

        .barcode svg {
            max-width: 100%;
            height: auto;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .receipt {
                width: 80mm;
                margin: 0;
                padding: 5mm;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: 80mm auto;
                margin: 0;
            }
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #06C755;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .print-button:hover {
            background: #05b34c;
        }

        .logo {
            max-width: 120px;
            height: auto;
            margin: 0 auto 10px;
            display: block;
        }
    </style>
</head>
<body>
    <!-- Print Button (No Print) -->
    <button class="print-button no-print" onclick="window.print()">
        <i class="fas fa-print"></i> พิมพ์ใบเสร็จ
    </button>

    <div class="receipt">
        <!-- Store Logo -->
        @if($transaction->store->logo ?? false)
            <img src="{{ asset($transaction->store->logo) }}" alt="Logo" class="logo">
        @endif

        <!-- Store Header -->
        <div class="text-center mb-2">
            <div class="large bold">{{ $transaction->store->name ?? 'ร้านค้า' }}</div>
            @if($transaction->store->address ?? false)
                <div class="small">{{ $transaction->store->address }}</div>
            @endif
            @if($transaction->store->phone ?? false)
                <div class="small">โทร: {{ $transaction->store->phone }}</div>
            @endif
            @if($transaction->store->tax_id ?? false)
                <div class="small">เลขประจำตัวผู้เสียภาษี: {{ $transaction->store->tax_id }}</div>
            @endif
        </div>

        <div class="divider-solid"></div>

        <!-- Receipt Info -->
        <div class="mb-2">
            <div class="text-center large bold mb-1">ใบเสร็จรับเงิน</div>
            <table class="small">
                <tr>
                    <td style="width: 40%">เลขที่:</td>
                    <td class="bold">{{ $transaction->receipt_number }}</td>
                </tr>
                <tr>
                    <td>วันที่:</td>
                    <td>{{ $transaction->transaction_date->locale('th')->isoFormat('DD/MM/YYYY HH:mm:ss') }}</td>
                </tr>
                <tr>
                    <td>แคชเชียร์:</td>
                    <td>{{ $transaction->user->name ?? '-' }}</td>
                </tr>
                @if($transaction->posDevice)
                <tr>
                    <td>เครื่อง:</td>
                    <td>{{ $transaction->posDevice->device_name }}</td>
                </tr>
                @endif
                @if($transaction->customer_name)
                <tr>
                    <td>ลูกค้า:</td>
                    <td>{{ $transaction->customer_name }}</td>
                </tr>
                @endif
                @if($transaction->customer_phone)
                <tr>
                    <td>เบอร์โทร:</td>
                    <td>{{ $transaction->customer_phone }}</td>
                </tr>
                @endif
            </table>
        </div>

        <div class="divider"></div>

        <!-- Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-left">รายการ</th>
                    <th class="text-right">จำนวน</th>
                    <th class="text-right">ราคา</th>
                    <th class="text-right">รวม</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->items as $item)
                <tr>
                    <td colspan="4" class="bold">{{ $item->product_name }}</td>
                </tr>
                <tr>
                    <td class="small">{{ $item->product_sku }}</td>
                    <td class="text-right">{{ number_format($item->quantity) }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right bold">{{ number_format($item->total, 2) }}</td>
                </tr>
                @if($item->discount_amount > 0)
                <tr>
                    <td colspan="3" class="text-right small">ส่วนลด:</td>
                    <td class="text-right small">-{{ number_format($item->discount_amount, 2) }}</td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>

        <div class="divider"></div>

        <!-- Totals -->
        <table>
            <tr>
                <td>ยอดรวม:</td>
                <td class="text-right">{{ number_format($transaction->subtotal, 2) }}</td>
            </tr>
            @if($transaction->discount_amount > 0)
            <tr>
                <td>ส่วนลด
                    @if($transaction->discount_percentage > 0)
                        ({{ number_format($transaction->discount_percentage, 2) }}%):
                    @else
                        :
                    @endif
                </td>
                <td class="text-right">-{{ number_format($transaction->discount_amount, 2) }}</td>
            </tr>
            @endif

            @if($settings && $settings->tax_enabled && $transaction->tax_amount > 0)
            <tr>
                <td>
                    @if($settings->tax_inclusive)
                        ภาษี (รวมใน) {{ number_format($transaction->tax_percentage, 2) }}%:
                    @else
                        ภาษี {{ number_format($transaction->tax_percentage, 2) }}%:
                    @endif
                </td>
                <td class="text-right">{{ number_format($transaction->tax_amount, 2) }}</td>
            </tr>
            @endif

            @if($transaction->service_charge > 0)
            <tr>
                <td>ค่าบริการ:</td>
                <td class="text-right">{{ number_format($transaction->service_charge, 2) }}</td>
            </tr>
            @endif
        </table>

        <div class="divider-double"></div>

        <!-- Grand Total -->
        <table class="large bold mb-2">
            <tr>
                <td>รวมทั้งสิ้น:</td>
                <td class="text-right">{{ number_format($transaction->total_amount, 2) }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        <!-- Payment Info -->
        <table>
            <tr>
                <td>วิธีชำระเงิน:</td>
                <td class="text-right bold">
                    @switch($transaction->payment_method)
                        @case('cash')
                            เงินสด
                            @break
                        @case('card')
                            บัตรเครดิต/เดบิต
                            @break
                        @case('qr')
                            QR Code
                            @break
                        @case('e-wallet')
                            E-Wallet
                            @break
                        @default
                            {{ ucfirst($transaction->payment_method) }}
                    @endswitch
                </td>
            </tr>
            @if($transaction->payment_method === 'cash')
            <tr>
                <td>รับเงิน:</td>
                <td class="text-right">{{ number_format($transaction->amount_paid, 2) }}</td>
            </tr>
            <tr>
                <td>เงินทอน:</td>
                <td class="text-right bold">{{ number_format($transaction->change_amount, 2) }}</td>
            </tr>
            @endif
        </table>

        <div class="divider"></div>

        <!-- Summary -->
        <table class="small">
            <tr>
                <td>จำนวนรายการ:</td>
                <td class="text-right">{{ number_format($transaction->total_items) }}</td>
            </tr>
            <tr>
                <td>จำนวนชิ้น:</td>
                <td class="text-right">{{ number_format($transaction->total_quantity) }}</td>
            </tr>
        </table>

        @if($transaction->notes)
        <div class="divider"></div>
        <div class="small">
            <div class="bold">หมายเหตุ:</div>
            <div>{{ $transaction->notes }}</div>
        </div>
        @endif

        <div class="divider-double mt-3"></div>

        <!-- Footer Message -->
        <div class="text-center small mt-2">
            @if($settings && $settings->receipt_footer_message)
                <div class="mb-1">{{ $settings->receipt_footer_message }}</div>
            @else
                <div class="mb-1">ขอบคุณที่ใช้บริการ</div>
                <div class="mb-1">โปรดเก็บใบเสร็จไว้เป็นหลักฐาน</div>
            @endif

            @if($settings && $settings->return_policy_days)
                <div class="mt-2">
                    สามารถเปลี่ยน/คืนสินค้าภายใน {{ $settings->return_policy_days }} วัน
                </div>
            @endif
        </div>

        <!-- Barcode -->
        @if($transaction->receipt_number)
        <div class="barcode mt-3">
            <svg id="barcode"></svg>
            <div class="small">{{ $transaction->receipt_number }}</div>
        </div>
        @endif

        <!-- System Info -->
        <div class="text-center small mt-3" style="color: #999;">
            <div>พิมพ์: {{ now()->locale('th')->isoFormat('DD/MM/YYYY HH:mm:ss') }}</div>
            <div>Powered by {{ config('app.name') }}</div>
        </div>
    </div>

    <!-- JsBarcode Library -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script>
        // Generate barcode
        @if($transaction->receipt_number)
        try {
            JsBarcode("#barcode", "{{ $transaction->receipt_number }}", {
                format: "CODE128",
                width: 2,
                height: 50,
                displayValue: false,
                margin: 0
            });
        } catch (e) {
            console.error('Barcode generation failed:', e);
        }
        @endif

        // Auto print on load (optional - comment out if not needed)
        // window.onload = function() {
        //     setTimeout(() => {
        //         window.print();
        //     }, 500);
        // };
    </script>
</body>
</html>
