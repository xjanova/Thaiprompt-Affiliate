@extends('layouts.seller-v4')

@section('title', 'รายการพิมพ์ฉลาก #'.$print->id)

@php
    $statusTone = ['completed' => 'ok', 'printing' => 'info', 'pending' => 'warn', 'failed' => 'bad', 'cancelled' => 'muted'];
    $items = collect(is_array($print->products) ? $print->products : []);
    // ลิงก์พิมพ์ซ้ำ → หน้า preview เดิม (ตรวจความเป็นเจ้าของสินค้าอีกครั้งฝั่งเซิร์ฟเวอร์)
    $reprintQuery = null;
    if ($print->template_id && $items->isNotEmpty()) {
        $reprintQuery = ['template_id' => $print->template_id, 'products' => $items->map(fn ($i) => [
            'product_id' => (int) ($i['product_id'] ?? 0),
            'quantity' => max(1, (int) ($i['quantity'] ?? 1)),
        ])->filter(fn ($i) => $i['product_id'] > 0)->values()->all()];
    }
    $rows = [
        ['ประเภท', $print->print_type_name],
        ['Template', $print->template->name ?? '—'],
        ['ขนาดกระดาษ', $print->paper_size ?: (($print->paper_width && $print->paper_height) ? rtrim(rtrim(number_format((float) $print->paper_width, 1), '0'), '.').'×'.rtrim(rtrim(number_format((float) $print->paper_height, 1), '0'), '.').' มม.' : '—')],
        ['จำนวนแผ่น', number_format((int) $print->sheets_count)],
        ['เครื่องพิมพ์', $print->printer_name ?: '—'],
        ['ผู้สั่งพิมพ์', $print->user->name ?? '—'],
        ['เวลา', optional($print->printed_at ?? $print->created_at)->format('d/m/Y H:i')],
    ];
    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink);';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header :title="'รายการพิมพ์ #'.$print->id" icon="🏷️" crumb="ร้านค้า · POS · ประวัติการพิมพ์ฉลาก"
                         :subtitle="number_format((int) $print->total_labels).' ดวง · '.$print->print_type_name">
        <x-seller-kit.pill :tone="$statusTone[$print->status] ?? 'muted'">{{ $print->status_name }}</x-seller-kit.pill>
        @if($reprintQuery)
            <a href="{{ route('seller.pos.labels.preview', $reprintQuery) }}" target="_blank" rel="noopener" class="tp-btn tp-btn-primary tp-btn-sm">🖨️ พิมพ์ซ้ำ</a>
        @endif
        <a href="{{ route('seller.pos.labels.history') }}" class="tp-btn tp-btn-sm">← ประวัติ</a>
    </x-seller-kit.header>

    @include('seller.pos.partials.nav')

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px;">
        <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
            <div class="tp-section-h">รายละเอียดการพิมพ์</div>
            @foreach($rows as [$rLabel, $rValue])
                <div style="display:flex; justify-content:space-between; gap:10px; font-size:13px;"><span style="color:var(--ink2);">{{ $rLabel }}</span><span style="text-align:right;">{{ $rValue }}</span></div>
            @endforeach
            @if($print->transaction)
                <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">รายการขาย</span>
                    <a href="{{ route('seller.pos.transactions.show', $print->transaction) }}" class="tp-num" style="font-weight:700; color:var(--deep1); text-decoration:none;">{{ $print->transaction->transaction_code }}</a>
                </div>
            @endif
            @if($print->error_message)
                <div style="font-size:12.5px; color:var(--tp-bad, #d9534f);">ข้อผิดพลาด: {{ $print->error_message }}</div>
            @endif
        </div>

        <div class="tp-card" style="display:flex; flex-direction:column; gap:10px; justify-content:space-between;">
            <div>
                <div class="tp-section-h">จัดการ</div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:6px; line-height:1.6;">พิมพ์ซ้ำจะเปิดหน้าตัวอย่างฉลากชุดเดิม (ราคาอัปเดตตามสินค้าปัจจุบัน)</div>
            </div>
            <form method="POST" action="{{ route('seller.pos.labels.destroy', $print) }}" onsubmit="return confirm('ลบรายการพิมพ์นี้ออกจากประวัติ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="tp-btn" style="width:100%; color:var(--tp-bad, #d9534f);">🗑️ ลบออกจากประวัติ</button>
            </form>
        </div>
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div class="tp-section-h" style="padding:16px 18px;">📦 สินค้าในชุดนี้</div>
        @if($items->isNotEmpty())
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:560px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="{{ $th }}">สินค้า</th>
                            <th style="{{ $th }}">บาร์โค้ด / SKU</th>
                            <th style="{{ $th }} text-align:right;">ราคา</th>
                            <th style="{{ $th }} text-align:right;">จำนวนดวง</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr style="{{ $row }}">
                                <td style="{{ $td }} font-weight:700;">{{ $item['product_name'] ?? '—' }}</td>
                                <td style="{{ $td }}" class="tp-num">{{ $item['barcode'] ?? ($item['sku'] ?? '—') }}</td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">฿{{ number_format((float) ($item['price'] ?? 0), 2) }}</td>
                                <td style="{{ $td }} text-align:right; font-weight:800;" class="tp-num">{{ number_format((int) ($item['quantity'] ?? 0)) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-seller-kit.empty icon="📦" title="ไม่มีข้อมูลสินค้าในรายการนี้" />
        @endif
    </div>
</div>
@endsection
