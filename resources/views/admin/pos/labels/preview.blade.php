<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>พิมพ์ฉลาก - {{ $template->name }}</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        /* ===== BASE STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', 'Noto Sans Thai', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        /* ===== TOOLBAR (ซ่อนเมื่อพิมพ์) ===== */
        .print-toolbar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .print-toolbar h1 {
            font-size: 20px;
            color: #333;
        }

        .print-toolbar button {
            padding: 12px 24px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-print {
            background: #28a745;
            color: white;
        }

        .btn-print:hover {
            background: #218838;
        }

        .btn-close {
            background: #6c757d;
            color: white;
            margin-left: 10px;
        }

        .btn-close:hover {
            background: #5a6268;
        }

        /* ===== PRINT INFO ===== */
        .print-info {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 30px;
            font-size: 14px;
        }

        .print-info div {
            display: flex;
            gap: 5px;
        }

        .print-info strong {
            color: #666;
        }

        .print-info span {
            color: #333;
            font-weight: 600;
        }

        /* ===== PAPER CONTAINER ===== */
        .paper-container {
            background: white;
            margin: 0 auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            position: relative;
        }

        /* คำนวณขนาดกระดาษตาม template */
        .paper-container {
            width: {{ $template->paper_width }}mm;
            @if(!$template->is_continuous_roll)
            min-height: {{ $template->paper_height }}mm;
            @endif
            padding: {{ $template->margin_top }}mm {{ $template->margin_right }}mm {{ $template->margin_bottom }}mm {{ $template->margin_left }}mm;
        }

        /* ===== LABELS GRID ===== */
        .labels-grid {
            display: grid;
            grid-template-columns: repeat({{ $template->columns }}, 1fr);
            grid-template-rows: repeat({{ $template->rows }}, 1fr);
            gap: {{ $template->gap_vertical }}mm {{ $template->gap_horizontal }}mm;
        }

        /* ===== LABEL ITEM ===== */
        .label-item {
            position: relative;
            border: 1px dashed #ccc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2mm;
            background: {{ $template->settings['background_color'] ?? '#ffffff' }};
            @if(($template->settings['border_enabled'] ?? false))
            border: {{ $template->settings['border_width'] ?? 1 }}px solid {{ $template->settings['border_color'] ?? '#000000' }};
            @endif
        }

        /* คำนวณขนาดฉลากแต่ละชิ้น */
        @php
            $labelSize = $template->label_size;
        @endphp
        .label-item {
            width: {{ $labelSize['width'] }}mm;
            height: {{ $labelSize['height'] }}mm;
        }

        /* ===== LABEL CONTENT ===== */
        .label-product-name {
            font-size: 10pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 1mm;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 100%;
        }

        .label-price {
            font-size: 14pt;
            font-weight: bold;
            color: #d9534f;
            margin-bottom: 1mm;
        }

        .label-barcode-wrapper {
            margin-top: auto;
        }

        .label-barcode {
            max-width: 100%;
            height: auto;
        }

        /* ===== PRINT STYLES ===== */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }

            /* ซ่อน toolbar และ info */
            .print-toolbar,
            .print-info {
                display: none !important;
            }

            /* ปรับกระดาษให้พอดี */
            .paper-container {
                box-shadow: none;
                margin: 0;
                width: {{ $template->paper_width }}mm;
                @if(!$template->is_continuous_roll)
                height: {{ $template->paper_height }}mm;
                @endif
            }

            /* ซ่อนเส้น dashed */
            .label-item {
                border: none !important;
            }

            /* ห้ามแบ่งหน้าในฉลาก */
            .label-item {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            /* Force paper size */
            @page {
                size: {{ $template->paper_width }}mm {{ $template->paper_height }}mm;
                margin: 0;
            }
        }

        /* ===== RESPONSIVE (สำหรับหน้าจอ) ===== */
        @media screen and (max-width: 768px) {
            .print-toolbar {
                flex-direction: column;
                gap: 10px;
            }

            .print-info {
                flex-direction: column;
                gap: 10px;
            }

            .paper-container {
                transform: scale(0.5);
                transform-origin: top center;
            }
        }
    </style>
</head>
<body>
    {{-- Toolbar --}}
    <div class="print-toolbar">
        <div>
            <h1>🏷️ พิมพ์ฉลาก: {{ $template->name }}</h1>
        </div>
        <div>
            <button class="btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> พิมพ์ฉลาก
            </button>
            <button class="btn-close" onclick="window.close()">
                <i class="fas fa-times"></i> ปิดหน้าต่าง
            </button>
        </div>
    </div>

    {{-- Print Info --}}
    <div class="print-info">
        <div>
            <strong>Template:</strong>
            <span>{{ $template->name }}</span>
        </div>
        <div>
            <strong>ขนาดกระดาษ:</strong>
            <span>{{ $template->paper_width }} x {{ $template->paper_height }} {{ $template->paper_unit }}</span>
        </div>
        <div>
            <strong>จำนวนฉลาก:</strong>
            <span>{{ $totalLabels }} ฉลาก</span>
        </div>
        @if(!$template->is_continuous_roll)
        <div>
            <strong>จำนวนแผ่น:</strong>
            <span>{{ $sheetsCount }} แผ่น</span>
        </div>
        @endif
        <div>
            <strong>ฉลากต่อแผ่น:</strong>
            <span>{{ $template->labels_per_sheet }} ฉลาก ({{ $template->columns }}x{{ $template->rows }})</span>
        </div>
    </div>

    {{-- Paper Container --}}
    <div class="paper-container">
        @php
            $labelsPerSheet = $template->labels_per_sheet;
            $sheets = $template->is_continuous_roll ? [collect($labelsData)] : collect($labelsData)->chunk($labelsPerSheet);
        @endphp

        @foreach($sheets as $sheetIndex => $sheetLabels)
            {{-- แต่ละแผ่น --}}
            @if($sheetIndex > 0)
                <div style="page-break-before: always;"></div>
            @endif

            <div class="labels-grid">
                @foreach($sheetLabels as $labelIndex => $label)
                    {{-- แต่ละฉลาก --}}
                    <div class="label-item">
                        {{-- ชื่อสินค้า --}}
                        @foreach($template->elements['text'] ?? [] as $textElement)
                            @if($textElement['id'] === 'product_name')
                                <div class="label-product-name" style="
                                    font-size: {{ $textElement['fontSize'] ?? 10 }}pt;
                                    font-weight: {{ $textElement['fontWeight'] ?? 'bold' }};
                                    text-align: {{ $textElement['textAlign'] ?? 'center' }};
                                    color: {{ $textElement['color'] ?? '#000000' }};
                                ">
                                    {{ Str::limit($label['product_name'], 30) }}
                                </div>
                            @endif
                        @endforeach

                        {{-- ราคา --}}
                        @foreach($template->elements['text'] ?? [] as $textElement)
                            @if($textElement['id'] === 'price')
                                <div class="label-price" style="
                                    font-size: {{ $textElement['fontSize'] ?? 14 }}pt;
                                    font-weight: {{ $textElement['fontWeight'] ?? 'bold' }};
                                    text-align: {{ $textElement['textAlign'] ?? 'center' }};
                                    color: {{ $textElement['color'] ?? '#d9534f' }};
                                ">
                                    ฿{{ $label['price'] }}
                                </div>
                            @endif
                        @endforeach

                        {{-- Barcode --}}
                        @foreach($template->elements['barcode'] ?? [] as $barcodeIndex => $barcodeElement)
                            <div class="label-barcode-wrapper">
                                <svg class="label-barcode barcode-{{ $sheetIndex }}-{{ $labelIndex }}-{{ $barcodeIndex }}"></svg>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                {{-- เติมช่องว่างถ้าฉลากไม่เต็มแผ่น --}}
                @if(!$template->is_continuous_roll)
                    @for($i = count($sheetLabels); $i < $labelsPerSheet; $i++)
                        <div class="label-item" style="border: 1px dashed #e0e0e0; background: #fafafa;"></div>
                    @endfor
                @endif
            </div>
        @endforeach
    </div>

    {{-- Barcode Generation Script --}}
    <script>
        // สร้าง Barcode ทั้งหมด
        document.addEventListener('DOMContentLoaded', function() {
            const labels = @json($labelsData);
            const template = @json($template);

            labels.forEach((label, labelIndex) => {
                // คำนวณว่าอยู่ใน sheet ไหน
                const labelsPerSheet = {{ $template->labels_per_sheet }};
                const sheetIndex = Math.floor(labelIndex / labelsPerSheet);
                const indexInSheet = labelIndex % labelsPerSheet;

                // สร้าง barcode ทุก element
                template.elements.barcode?.forEach((barcodeElement, barcodeIndex) => {
                    const selector = `.barcode-${sheetIndex}-${indexInSheet}-${barcodeIndex}`;
                    const svgElement = document.querySelector(selector);

                    if (svgElement && label.barcode) {
                        try {
                            JsBarcode(svgElement, label.barcode, {
                                format: barcodeElement.format || 'CODE128',
                                width: 2,
                                height: barcodeElement.height || 40,
                                displayValue: barcodeElement.showText ?? true,
                                fontSize: barcodeElement.textSize || 12,
                                margin: 0,
                            });
                        } catch (error) {
                            console.error('Barcode generation error:', error);
                            svgElement.parentElement.innerHTML = '<div style="font-size: 8pt; color: red;">Invalid Barcode</div>';
                        }
                    }
                });
            });

            console.log('✅ สร้าง Barcode สำเร็จ:', labels.length, 'ฉลาก');
        });

        // Auto print on load (optional - comment out if not needed)
        // window.onload = function() {
        //     setTimeout(() => window.print(), 1000);
        // };
    </script>
</body>
</html>
