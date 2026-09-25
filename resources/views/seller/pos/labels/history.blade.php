@extends('layouts.seller-v4')

@section('title', 'ประวัติการพิมพ์ฉลาก')

@php
    $statusTone = ['completed' => 'ok', 'printing' => 'info', 'pending' => 'warn', 'failed' => 'bad', 'cancelled' => 'muted'];
    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink);';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="ประวัติการพิมพ์ฉลาก" icon="📜" crumb="ร้านค้า · POS · ฉลากบาร์โค้ด">
        <a href="{{ route('seller.pos.labels.print-product') }}" class="tp-btn tp-btn-primary tp-btn-sm">🏷️ พิมพ์ฉลากใหม่</a>
    </x-seller-kit.header>

    @include('seller.pos.partials.nav')

    <form method="GET" action="{{ route('seller.pos.labels.history') }}" class="tp-card" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; align-items:end;">
        <div>
            <label for="h-type" style="font-size:12px; font-weight:700; color:var(--ink2);">ประเภท</label>
            <select id="h-type" name="print_type" class="tp-input" style="margin-top:6px;">
                <option value="">ทุกประเภท</option>
                <option value="product_label" @selected(request('print_type') === 'product_label')>ฉลากสินค้า</option>
                <option value="price_tag" @selected(request('print_type') === 'price_tag')>ป้ายราคา</option>
                <option value="barcode_only" @selected(request('print_type') === 'barcode_only')>บาร์โค้ด</option>
                <option value="shipping_label" @selected(request('print_type') === 'shipping_label')>ใบปะหน้า</option>
            </select>
        </div>
        <div>
            <label for="h-status" style="font-size:12px; font-weight:700; color:var(--ink2);">สถานะ</label>
            <select id="h-status" name="status" class="tp-input" style="margin-top:6px;">
                <option value="">ทุกสถานะ</option>
                <option value="completed" @selected(request('status') === 'completed')>สำเร็จ</option>
                <option value="failed" @selected(request('status') === 'failed')>ล้มเหลว</option>
            </select>
        </div>
        <div>
            <label for="h-from" style="font-size:12px; font-weight:700; color:var(--ink2);">ตั้งแต่วันที่</label>
            <input id="h-from" type="date" name="date_from" value="{{ request('date_from') }}" class="tp-input" style="margin-top:6px;">
        </div>
        <div>
            <label for="h-to" style="font-size:12px; font-weight:700; color:var(--ink2);">ถึงวันที่</label>
            <input id="h-to" type="date" name="date_to" value="{{ request('date_to') }}" class="tp-input" style="margin-top:6px;">
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;">🔍 กรอง</button>
            <a href="{{ route('seller.pos.labels.history') }}" class="tp-btn">ล้าง</a>
        </div>
    </form>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($prints->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:700px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="{{ $th }}">ประเภท</th>
                            <th style="{{ $th }}">Template</th>
                            <th style="{{ $th }} text-align:right;">จำนวน</th>
                            <th style="{{ $th }} text-align:center;">สถานะ</th>
                            <th style="{{ $th }}">วันที่</th>
                            <th style="{{ $th }} text-align:right;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prints as $print)
                            <tr style="{{ $row }}">
                                <td style="{{ $td }} font-weight:700;">{{ $print->print_type_name }}</td>
                                <td style="{{ $td }}">{{ $print->template->name ?? '—' }}</td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">{{ number_format($print->total_labels) }} ดวง</td>
                                <td style="{{ $td }} text-align:center;"><x-seller-kit.pill :tone="$statusTone[$print->status] ?? 'muted'">{{ $print->status_name }}</x-seller-kit.pill></td>
                                <td style="{{ $td }} white-space:nowrap;" class="tp-num">{{ $print->created_at->format('d/m/Y H:i') }}</td>
                                <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                    <a href="{{ route('seller.pos.labels.show', $print) }}" class="tp-btn tp-btn-sm">ดู</a>
                                    <form method="POST" action="{{ route('seller.pos.labels.destroy', $print) }}" style="display:inline;" onsubmit="return confirm('ลบรายการพิมพ์นี้ออกจากประวัติ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-btn tp-btn-sm" style="color:var(--tp-bad, #d9534f);">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($prints->hasPages())
                <div style="padding:14px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">{{ $prints->withQueryString()->links() }}</div>
            @endif
        @else
            <x-seller-kit.empty icon="📜" title="ไม่มีประวัติการพิมพ์" text="ยังไม่เคยพิมพ์ หรือลองปรับตัวกรองใหม่" />
        @endif
    </div>
</div>
@endsection
