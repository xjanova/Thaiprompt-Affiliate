<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสนอราคา {{ $quotation->quotation_number }}</title>
    <style>
        body {
            font-family: 'Sarabun', 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
        }
        .info-section {
            margin-bottom: 30px;
        }
        .info-section h3 {
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .info-row {
            display: flex;
            padding: 8px 0;
        }
        .info-label {
            font-weight: bold;
            width: 200px;
        }
        .info-value {
            flex: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        .option-list {
            margin: 10px 0;
            padding-left: 20px;
        }
        .option-item {
            padding: 5px 0;
            color: #666;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }
        .summary-row.total {
            font-size: 1.3em;
            font-weight: bold;
            color: #007bff;
            border-top: 2px solid #007bff;
            padding-top: 15px;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ใบเสนอราคา</h1>
        <p>หมายเลข: {{ $quotation->quotation_number }}</p>
        <p>วันที่: {{ $quotation->created_at->format('d/m/Y') }}</p>
    </div>

    <div class="info-section">
        <h3>ข้อมูลลูกค้า</h3>
        <div class="info-row">
            <div class="info-label">ชื่อ:</div>
            <div class="info-value">{{ $quotation->customer_name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">อีเมล:</div>
            <div class="info-value">{{ $quotation->customer_email }}</div>
        </div>
        @if($quotation->customer_phone)
        <div class="info-row">
            <div class="info-label">เบอร์โทร:</div>
            <div class="info-value">{{ $quotation->customer_phone }}</div>
        </div>
        @endif
        @if($quotation->company_name)
        <div class="info-row">
            <div class="info-label">บริษัท:</div>
            <div class="info-value">{{ $quotation->company_name }}</div>
        </div>
        @endif
    </div>

    <div class="info-section">
        <h3>รายการสินค้า</h3>
        <table>
            <thead>
                <tr>
                    <th>รายการ</th>
                    <th style="text-align: right;">จำนวน</th>
                    <th style="text-align: right;">ราคา/หน่วย</th>
                    <th style="text-align: right;">ยอดรวม</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->item_name }}</strong>
                        @if($item->selectedOptions->count() > 0)
                        <div class="option-list">
                            @foreach($item->selectedOptions as $option)
                            <div class="option-item">
                                • {{ $option->option_name }}: {{ $option->option_display_label }}
                                @if($option->price_modifier > 0)
                                    (+{{ number_format($option->price_modifier, 2) }} บาท)
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </td>
                    <td style="text-align: right;">{{ $item->quantity }}</td>
                    <td style="text-align: right;">{{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="summary">
        <div class="summary-row">
            <span>ยอดรวม:</span>
            <span>{{ number_format($quotation->subtotal, 2) }} บาท</span>
        </div>
        @if($quotation->discount_amount > 0)
        <div class="summary-row">
            <span>ส่วนลด ({{ $quotation->discount_percentage }}%):</span>
            <span>-{{ number_format($quotation->discount_amount, 2) }} บาท</span>
        </div>
        @endif
        <div class="summary-row">
            <span>ภาษี VAT ({{ $quotation->tax_rate }}%):</span>
            <span>{{ number_format($quotation->tax_amount, 2) }} บาท</span>
        </div>
        @if($quotation->setup_total > 0)
        <div class="summary-row">
            <span>ค่าติดตั้งและเซ็ตอัพ:</span>
            <span>{{ number_format($quotation->setup_total, 2) }} บาท</span>
        </div>
        @endif
        <div class="summary-row total">
            <span>ยอดรวมทั้งสิ้น:</span>
            <span>{{ number_format($quotation->total_amount, 2) }} บาท</span>
        </div>
        @if($quotation->monthly_total > 0)
        <div class="summary-row">
            <span>ค่าบริการรายเดือน:</span>
            <span>{{ number_format($quotation->monthly_total, 2) }} บาท/เดือน</span>
        </div>
        @endif
    </div>

    @if($quotation->notes)
    <div class="info-section">
        <h3>หมายเหตุ</h3>
        <p>{{ $quotation->notes }}</p>
    </div>
    @endif

    <div style="text-align: center;">
        <a href="{{ url('/my-quotations/' . $quotation->id) }}" class="btn">ดูใบเสนอราคาฉบับเต็ม</a>
    </div>

    <div class="footer">
        <p>ใบเสนอราคานี้มีอายุถึงวันที่ {{ $quotation->valid_until->format('d/m/Y') }}</p>
        <p>หากมีข้อสงสัยหรือต้องการข้อมูลเพิ่มเติม กรุณาติดต่อทีมงาน</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
