<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสนอราคา {{ $quotation->quotation_number }}</title>
    <style>
        @font-face {
            font-family: 'Sarabun';
            font-style: normal;
            font-weight: normal;
            src: url("{{ public_path('fonts/Sarabun-Regular.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'Sarabun';
            font-style: normal;
            font-weight: bold;
            src: url("{{ public_path('fonts/Sarabun-Bold.ttf') }}") format('truetype');
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #007bff;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header .quotation-number {
            font-size: 16px;
            color: #666;
        }

        .company-info {
            margin-bottom: 30px;
        }

        .company-info h2 {
            font-size: 18px;
            color: #007bff;
            margin-bottom: 10px;
        }

        .info-section {
            margin-bottom: 30px;
        }

        .info-section h3 {
            font-size: 16px;
            color: #333;
            border-bottom: 2px solid #ddd;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }

        .info-grid {
            display: table;
            width: 100%;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 180px;
            padding: 6px 10px 6px 0;
        }

        .info-value {
            display: table-cell;
            padding: 6px 0;
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
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .option-list {
            margin: 8px 0 8px 20px;
            font-size: 12px;
        }

        .option-item {
            padding: 3px 0;
            color: #666;
        }

        .summary {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
        }

        .summary-table {
            width: 100%;
            border: none;
        }

        .summary-table td {
            border: none;
            padding: 8px 0;
        }

        .summary-table .label {
            text-align: right;
            padding-right: 30px;
            width: 70%;
        }

        .summary-table .amount {
            text-align: right;
            font-weight: bold;
            width: 30%;
        }

        .summary-table .total-row {
            border-top: 2px solid #007bff;
            font-size: 18px;
            color: #007bff;
        }

        .summary-table .total-row td {
            padding-top: 15px;
        }

        .notes {
            margin-top: 30px;
            padding: 15px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }

        .notes h4 {
            color: #856404;
            margin-bottom: 8px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ใบเสนอราคา</h1>
        <div class="quotation-number">เลขที่: {{ $quotation->quotation_number }}</div>
        <div class="quotation-number">วันที่: {{ $quotation->created_at->format('d/m/Y') }}</div>
        @if($quotation->valid_until)
        <div class="quotation-number">มีผลถึง: {{ $quotation->valid_until->format('d/m/Y') }}</div>
        @endif
    </div>

    <div class="company-info">
        <h2>{{ config('app.name') }}</h2>
        <div>ที่อยู่: {{ config('company.address', '-') }}</div>
        <div>เบอร์โทร: {{ config('company.phone', '-') }}</div>
        <div>อีเมล: {{ config('company.email', config('mail.from.address')) }}</div>
        @if(config('company.tax_id'))
        <div>เลขประจำตัวผู้เสียภาษี: {{ config('company.tax_id') }}</div>
        @endif
    </div>

    <div class="info-section">
        <h3>ข้อมูลลูกค้า</h3>
        <div class="info-grid">
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
            @if($quotation->company_address)
            <div class="info-row">
                <div class="info-label">ที่อยู่:</div>
                <div class="info-value">{{ $quotation->company_address }}</div>
            </div>
            @endif
            @if($quotation->tax_id)
            <div class="info-row">
                <div class="info-label">เลขประจำตัวผู้เสียภาษี:</div>
                <div class="info-value">{{ $quotation->tax_id }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="info-section">
        <h3>รายการสินค้าและบริการ</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">ลำดับ</th>
                    <th style="width: 50%;">รายการ</th>
                    <th style="width: 10%; text-align: center;">จำนวน</th>
                    <th style="width: 15%; text-align: right;">ราคา/หน่วย</th>
                    <th style="width: 20%; text-align: right;">ยอดรวม</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotation->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->item_name }}</strong>
                        @if($item->item_description)
                        <div style="font-size: 12px; color: #666; margin-top: 4px;">
                            {{ $item->item_description }}
                        </div>
                        @endif
                        @if($item->selectedOptions->count() > 0)
                        <div class="option-list">
                            @foreach($item->selectedOptions as $option)
                            <div class="option-item">
                                • {{ $option->option_name }}: {{ $option->option_display_label }}
                                @if($option->price_modifier > 0)
                                    <span style="color: #28a745;">(+{{ number_format($option->price_modifier, 2) }} บาท)</span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity) }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="summary">
        <table class="summary-table">
            <tr>
                <td class="label">ยอดรวม:</td>
                <td class="amount">{{ number_format($quotation->subtotal, 2) }} บาท</td>
            </tr>
            @if($quotation->discount_amount > 0)
            <tr>
                <td class="label">ส่วนลด ({{ number_format($quotation->discount_percentage, 2) }}%):</td>
                <td class="amount" style="color: #dc3545;">-{{ number_format($quotation->discount_amount, 2) }} บาท</td>
            </tr>
            @endif
            @if($quotation->setup_total > 0)
            <tr>
                <td class="label">ค่าติดตั้งและเซ็ตอัพ:</td>
                <td class="amount">{{ number_format($quotation->setup_total, 2) }} บาท</td>
            </tr>
            @endif
            <tr>
                <td class="label">ภาษี VAT ({{ number_format($quotation->tax_rate, 2) }}%):</td>
                <td class="amount">{{ number_format($quotation->tax_amount, 2) }} บาท</td>
            </tr>
            <tr class="total-row">
                <td class="label"><strong>ยอดรวมทั้งสิ้น:</strong></td>
                <td class="amount"><strong>{{ number_format($quotation->total_amount, 2) }} บาท</strong></td>
            </tr>
            @if($quotation->monthly_total > 0)
            <tr>
                <td class="label" style="padding-top: 10px;">ค่าบริการรายเดือน:</td>
                <td class="amount" style="color: #007bff; padding-top: 10px;">{{ number_format($quotation->monthly_total, 2) }} บาท/เดือน</td>
            </tr>
            @endif
        </table>
    </div>

    @if($quotation->notes)
    <div class="notes">
        <h4>หมายเหตุ:</h4>
        <p>{{ $quotation->notes }}</p>
    </div>
    @endif

    @if($quotation->admin_notes)
    <div class="notes">
        <h4>ข้อมูลเพิ่มเติม:</h4>
        <p>{{ $quotation->admin_notes }}</p>
    </div>
    @endif

    <div class="footer">
        <p><strong>ข้อกำหนดและเงื่อนไข:</strong></p>
        <p>1. ใบเสนอราคานี้มีผลถึงวันที่ {{ $quotation->valid_until ? $quotation->valid_until->format('d/m/Y') : '-' }}</p>
        <p>2. ราคาดังกล่าวยังไม่รวมค่าขนส่งและค่าติดตั้ง (ถ้ามี)</p>
        <p>3. การชำระเงินสามารถทำได้ผ่านช่องทางที่บริษัทกำหนด</p>
        <p>4. สินค้าและบริการอาจมีการเปลี่ยนแปลงตามความเหมาะสม</p>
        <br>
        <p>หากมีข้อสงสัยหรือต้องการข้อมูลเพิ่มเติม กรุณาติดต่อ</p>
        <p>อีเมล: {{ config('company.email', config('mail.from.address')) }} | โทร: {{ config('company.phone', '-') }}</p>
        <br>
        <p style="color: #999; font-size: 11px;">เอกสารนี้สร้างโดยระบบอัตโนมัติ | {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
